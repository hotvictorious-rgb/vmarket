<?php

namespace App\Http\Controllers\Admin\Product;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Http\Controllers\BaseController;
use App\Models\Category;
use App\Models\Product;
use App\Services\PricingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalPortalController extends BaseController
{
    public function __construct(
        private readonly ProductRepositoryInterface  $productRepo,
        private readonly CategoryRepositoryInterface $categoryRepo,
        private readonly PricingService              $pricingService,
    ) {
    }

    /**
     * Display the Dedicated Product Approval & Pricing Gateway Portal.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $categoryId = $request->get('category_id');
        $searchValue = $request->get('searchValue');

        $query = Product::with(['seller.shop', 'category', 'brand'])
            ->where('added_by', 'seller')
            ->where('request_status', 0);

        if ($categoryId && $categoryId != 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($searchValue) {
            $query->where('name', 'like', "%{$searchValue}%")
                ->orWhere('code', 'like', "%{$searchValue}%");
        }

        $pendingProducts = $query->orderBy('id', 'desc')->paginate(getWebConfig(name: 'pagination_limit') ?? 20);

        // Pre-calculate suggested retail prices for all pending items
        $pendingProducts->getCollection()->transform(function ($product) {
            $pricing = $this->pricingService->calculateRetailPrice(
                (float) ($product->purchase_price > 0 ? $product->purchase_price : $product->unit_price),
                $product->category_id
            );
            $product->suggested_unit_price = $pricing['unit_price'];
            $product->markup_rate = $pricing['markup_rate'];
            $product->markup_type = $pricing['markup_type'];
            $product->markup_amount = $pricing['markup_amount'];
            return $product;
        });

        $categories = Category::where(['position' => 0])->orderBy('name')->get();
        $totalPendingCount = Product::where('added_by', 'seller')->where('request_status', 0)->count();

        return view('admin-views.product.approval-portal', [
            'products' => $pendingProducts,
            'categories' => $categories,
            'categoryId' => $categoryId,
            'searchValue' => $searchValue,
            'totalPendingCount' => $totalPendingCount,
        ]);
    }

    /**
     * Approve a single product and set its authoritative customer-facing retail price.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function approveAndSetPrice(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_price' => 'required|numeric|min:0.01',
        ]);

        $product = Product::findOrFail($request->product_id);
        $finalUnitPrice = currencyConverter(amount: (float)$request->unit_price);

        $product->unit_price = $finalUnitPrice;
        $product->status = 1;
        $product->request_status = 1;
        $product->denied_note = null;
        $product->price_updated_at = now();
        $product->save();

        if (function_exists('clearWebConfigCacheKeys')) {
            clearWebConfigCacheKeys();
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => translate('product_approved_and_published_successfully'),
                'product_id' => $product->id,
                'final_price' => $product->unit_price,
            ]);
        }

        ToastMagic::success(translate('product_approved_and_published_successfully'));
        return redirect()->back();
    }

    /**
     * Batch approve selected products applying auto-calculated category markups.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function batchApprove(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        $approvedCount = 0;
        foreach ($request->product_ids as $productId) {
            $product = Product::find($productId);
            if (!$product || $product->request_status != 0) {
                continue;
            }

            // Calculate auto price if custom price not provided in batch payload
            $customPrice = $request->input("custom_price_{$productId}");
            if ($customPrice && (float)$customPrice > 0) {
                $finalPrice = currencyConverter(amount: (float)$customPrice);
            } else {
                $pricing = $this->pricingService->calculateRetailPrice(
                    (float) ($product->purchase_price > 0 ? $product->purchase_price : $product->unit_price),
                    $product->category_id
                );
                $finalPrice = currencyConverter(amount: $pricing['unit_price']);
            }

            $product->unit_price = $finalPrice;
            $product->status = 1;
            $product->request_status = 1;
            $product->denied_note = null;
            $product->price_updated_at = now();
            $product->save();
            $approvedCount++;
        }

        if (function_exists('clearWebConfigCacheKeys')) {
            clearWebConfigCacheKeys();
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $approvedCount . ' ' . translate('products_approved_and_published_successfully'),
            ]);
        }

        ToastMagic::success($approvedCount . ' ' . translate('products_approved_and_published_successfully'));
        return redirect()->back();
    }

    /**
     * Deny product with feedback note to the vendor.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function deny(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'denied_note' => 'required|string|max:1000',
        ]);

        $product = Product::findOrFail($request->product_id);
        $product->request_status = 2;
        $product->status = 0;
        $product->denied_note = $request->denied_note;
        $product->save();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => translate('product_request_denied_with_feedback_note'),
            ]);
        }

        ToastMagic::warning(translate('product_request_denied_with_feedback_note'));
        return redirect()->back();
    }
}
