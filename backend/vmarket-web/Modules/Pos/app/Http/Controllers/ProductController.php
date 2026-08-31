<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Seller;
use App\Traits\FileManagerTrait;
use Modules\Pos\app\Traits\PosAuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * [AI] ProductController — Unified POS & Online Marketplace Product Management.
 * Operates on the unified Vmarket `products` table (user_id = seller_id).
 * Intelligently recognizes Verified vs. Unverified/Free-Tier merchants:
 * - Verified Merchants: Full In-Store POS + Online Marketplace listing with photo upload and publish toggle.
 * - Free-Tier Merchants: Full In-Store POS (physical barcode register) + preparatory photo staging for KYC approval.
 */
class ProductController extends Controller
{
    use PosAuthTrait, FileManagerTrait;

    public function index(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $search   = trim($request->get('search', ''));
        $category = $request->get('category');
        $status   = $request->get('stock_status');

        $query = Product::where('user_id', $sellerId)->where('status', '!=', 2);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('pos_barcode', 'like', "%{$search}%");
            });
        }
        if ($category) {
            $query->where('pos_category', $category);
        }

        $products = $query->orderBy('name')->get()->map(function ($p) {
            $p->code                 = $p->code ?? (string)$p->id;
            $p->product_code         = $p->code;
            $p->category             = $p->pos_category ?? 'General';
            $p->brand                = $p->brand_id ? 'Brand #' . $p->brand_id : 'Standard';
            $p->size                 = null;
            $p->unitPrice            = (float) $p->unit_price;
            $p->branch_stocks        = [];
            $p->total_physical_stock = max(0, (int) $p->current_stock);
            $p->currentStock         = $p->total_physical_stock;
            $p->minStockLevel        = (int) ($p->pos_reorder_level ?? 5);
            $p->physical_stock       = $p->total_physical_stock;
            $p->reorder_level        = $p->minStockLevel;
            return $p;
        });

        if ($status === 'OUT_OF_STOCK') {
            $products = $products->filter(fn($p) => $p->physical_stock <= 0)->values();
        } elseif ($status === 'LOW_STOCK') {
            $products = $products->filter(fn($p) => $p->physical_stock > 0 && $p->physical_stock <= $p->reorder_level)->values();
        }

        $categories = Product::where('user_id', $sellerId)->distinct()->pluck('pos_category')->filter()->values();
        $branches   = DB::table('shops')->where('seller_id', $sellerId)->get()->map(function ($b) {
            $b->code = $b->code ?? ('SHP-' . $b->id);
            return $b;
        });
        $warehouses = $branches;

        return view('pos::products.index', compact('products', 'categories', 'branches', 'warehouses', 'search', 'category', 'status'));
    }

    public function create()
    {
        $sellerId           = $this->resolveAuthSellerId();
        $seller             = Seller::find($sellerId);
        $isVerified         = ($seller && $seller->status === 'approved') || Auth::guard('admin')->check();
        $officialCategories = Category::with('childes')->where(['position' => 0])->orderBy('priority')->get();
        $posCategories      = Product::where('user_id', $sellerId)->distinct()->pluck('pos_category')->filter()->values();
        $units              = ['pc', 'kg', 'g', 'ltr', 'bag', 'carton', 'pack', 'bottle', 'box', 'roll', 'meter', 'pair'];

        return view('pos::products.create', compact('officialCategories', 'posCategories', 'units', 'isVerified', 'seller'));
    }

    /**
     * Store new product in the unified Vmarket products table.
     */
    public function store(Request $request)
    {
        $sellerId   = $this->resolveAuthSellerId();
        $seller     = Seller::find($sellerId);
        $isVerified = ($seller && $seller->status === 'approved') || Auth::guard('admin')->check();

        $request->validate([
            'name'                => 'required|string|max:255',
            'code'                => 'nullable|string|max:100',
            'unit_price'          => 'required|numeric|min:0',
            'purchase_price'      => 'nullable|numeric|min:0',
            'pos_wholesale_price' => 'nullable|numeric|min:0',
            'current_stock'       => 'required|integer|min:0',
            'unit'                => 'nullable|string|max:50',
            'category_id'         => 'nullable|integer',
            'pos_category'        => 'nullable|string|max:100',
            'pos_reorder_level'   => 'nullable|integer|min:0',
            'tax'                 => 'nullable|numeric|min:0',
            'tax_type'            => 'nullable|in:percent,flat',
            'discount'            => 'nullable|numeric|min:0',
            'discount_type'       => 'nullable|in:percent,flat',
            'minimum_order_qty'   => 'nullable|integer|min:1',
            'image'               => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $slug = Str::slug($request->name) . '-' . Str::lower(Str::random(5));
        $code = $request->code ?: ($request->pos_barcode ?: strtoupper(Str::random(8)));

        // Handle optional product photo upload for online storefront
        $thumbnail = 'def.png';
        $images = ['def.png'];
        if ($request->hasFile('image')) {
            try {
                $uploaded = $this->upload('product/thumbnail/', 'webp', $request->file('image'));
                if ($uploaded) {
                    $thumbnail = $uploaded;
                    $images = [$uploaded];
                }
            } catch (\Throwable $e) {
                $thumbnail = 'def.png';
            }
        }

        $isPublish     = $isVerified && $request->boolean('is_published');
        $status        = $isPublish ? 1 : 0;
        $requestStatus = $isPublish ? 1 : 0;
        $catId         = $request->category_id ?: 1;
        $categoryIds   = json_encode([['id' => (string)$catId, 'position' => 1]]);

        $catName = $request->pos_category;
        if (!$catName && $request->category_id) {
            $catName = Category::where('id', $request->category_id)->value('name');
        }

        $productId = DB::table('products')->insertGetId([
            'user_id'             => $sellerId,
            'added_by'            => 'seller',
            'name'                => $request->name,
            'code'                => $code,
            'slug'                => $slug,
            'pos_barcode'         => $code,
            'pos_category'        => $catName ?? 'General',
            'pos_reorder_level'   => (int) ($request->pos_reorder_level ?? 5),
            'category_id'         => $catId,
            'category_ids'        => $categoryIds,
            'unit_price'          => (float) $request->unit_price,
            'purchase_price'      => (float) ($request->purchase_price ?? 0),
            'pos_wholesale_price' => (float) ($request->pos_wholesale_price ?? $request->unit_price),
            'current_stock'       => (int) $request->current_stock,
            'minimum_order_qty'   => (int) ($request->minimum_order_qty ?? 1),
            'min_qty'             => (int) ($request->minimum_order_qty ?? 1),
            'unit'                => $request->unit ?? 'pc',
            'tax'                 => (float) ($request->tax ?? 0),
            'tax_type'            => $request->tax_type ?? 'percent',
            'discount'            => (float) ($request->discount ?? 0),
            'discount_type'       => $request->discount_type ?? 'flat',
            'product_type'        => 'physical',
            'status'              => $status,
            'request_status'      => $requestStatus,
            'published'           => $status,
            'thumbnail'           => $thumbnail,
            'images'              => json_encode($images),
            'color_image'         => json_encode([]),
            'colors'              => json_encode([]),
            'attributes'          => json_encode([]),
            'choice_options'      => json_encode([]),
            'variation'           => json_encode([]),
            'details'             => $request->details ?? ($request->description ?? ''),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Seed product_stocks row
        DB::table('product_stocks')->insert([
            'product_id' => $productId,
            'variant'    => null,
            'price'      => (float) $request->unit_price,
            'qty'        => (int) $request->current_stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.products.index')->with('success', "✓ Product [{$request->name}] saved and synced to unified catalog.");
    }

    public function edit(int $id)
    {
        $sellerId           = $this->resolveAuthSellerId();
        $seller             = Seller::find($sellerId);
        $isVerified         = ($seller && $seller->status === 'approved') || Auth::guard('admin')->check();
        $product            = Product::where('id', $id)->where('user_id', $sellerId)->firstOrFail();
        $officialCategories = Category::where(['position' => 0])->get();
        $posCategories      = Product::where('user_id', $sellerId)->distinct()->pluck('pos_category')->filter()->values();
        $units              = ['pc', 'kg', 'g', 'ltr', 'bag', 'carton', 'pack', 'bottle', 'box', 'roll', 'meter', 'pair'];

        return view('pos::products.edit', compact('product', 'officialCategories', 'posCategories', 'units', 'isVerified', 'seller'));
    }

    public function update(Request $request, int $id)
    {
        $sellerId   = $this->resolveAuthSellerId();
        $seller     = Seller::find($sellerId);
        $isVerified = ($seller && $seller->status === 'approved') || Auth::guard('admin')->check();
        $product    = Product::where('id', $id)->where('user_id', $sellerId)->firstOrFail();

        $request->validate([
            'name'                => 'required|string|max:255',
            'code'                => 'nullable|string|max:100',
            'unit_price'          => 'required|numeric|min:0',
            'purchase_price'      => 'nullable|numeric|min:0',
            'pos_wholesale_price' => 'nullable|numeric|min:0',
            'current_stock'       => 'required|integer|min:0',
            'unit'                => 'nullable|string|max:50',
            'category_id'         => 'nullable|integer',
            'pos_category'        => 'nullable|string|max:100',
            'pos_reorder_level'   => 'nullable|integer|min:0',
            'tax'                 => 'nullable|numeric|min:0',
            'tax_type'            => 'nullable|in:percent,flat',
            'discount'            => 'nullable|numeric|min:0',
            'discount_type'       => 'nullable|in:percent,flat',
            'minimum_order_qty'   => 'nullable|integer|min:1',
            'image'               => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $code = $request->code ?: ($request->pos_barcode ?: $product->code);

        $updateData = [
            'name'                => $request->name,
            'code'                => $code,
            'pos_barcode'         => $code,
            'pos_category'        => $request->pos_category ?? $product->pos_category,
            'pos_reorder_level'   => (int) ($request->pos_reorder_level ?? 5),
            'unit_price'          => (float) $request->unit_price,
            'purchase_price'      => (float) ($request->purchase_price ?? $product->purchase_price),
            'pos_wholesale_price' => (float) ($request->pos_wholesale_price ?? $request->unit_price),
            'current_stock'       => (int) $request->current_stock,
            'minimum_order_qty'   => (int) ($request->minimum_order_qty ?? $product->minimum_order_qty),
            'min_qty'             => (int) ($request->minimum_order_qty ?? $product->minimum_order_qty),
            'unit'                => $request->unit ?? $product->unit,
            'tax'                 => (float) ($request->tax ?? $product->tax),
            'tax_type'            => $request->tax_type ?? $product->tax_type,
            'discount'            => (float) ($request->discount ?? $product->discount),
            'discount_type'       => $request->discount_type ?? $product->discount_type,
            'details'             => $request->details ?? ($request->description ?? $product->details),
            'updated_at'          => now(),
        ];

        if ($request->has('category_id') && $request->category_id) {
            $updateData['category_id'] = (int) $request->category_id;
            $updateData['category_ids'] = json_encode([['id' => (string)$request->category_id, 'position' => 1]]);
        }

        if ($isVerified && $request->has('is_published')) {
            $isPublish = $request->boolean('is_published');
            $updateData['status'] = $isPublish ? 1 : 0;
            $updateData['published'] = $isPublish ? 1 : 0;
        }

        if ($request->hasFile('image')) {
            try {
                $uploaded = $this->upload('product/thumbnail/', 'webp', $request->file('image'));
                if ($uploaded) {
                    $updateData['thumbnail'] = $uploaded;
                    $updateData['images'] = json_encode([$uploaded]);
                }
            } catch (\Throwable $e) {}
        }

        DB::table('products')->where('id', $id)->where('user_id', $sellerId)->update($updateData);

        // Update product_stocks table
        DB::table('product_stocks')->where('product_id', $id)->update([
            'price'      => (float) $request->unit_price,
            'qty'        => (int) $request->current_stock,
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.products.index')->with('success', "✓ Product [{$request->name}] updated successfully.");
    }

    public function destroy(int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        // [AI] IDOR: must own the product
        $product  = Product::where('id', $id)->where('user_id', $sellerId)->firstOrFail();

        // [AI] Soft-archive instead of hard delete — preserves sale history references
        DB::table('products')->where('id', $id)->where('user_id', $sellerId)->update([
            'status'     => 2, // archived/inactive
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.products.index')->with('success', "Product [{$product->name}] archived from POS catalog.");
    }

    /**
     * Download CSV layout template for bulk POS product registration.
     */
    public function downloadCsvTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vmarket_pos_products_template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'code', 'pos_category', 'unit_price', 'purchase_price', 'current_stock', 'pos_reorder_level']);
            fputcsv($handle, ['Kings Vegetable Oil (25L)', 'KINGS-OIL-25L', 'Oils & Fats', '46500', '40000', '30', '5']);
            fputcsv($handle, ['Peak Milk Powder (900g)', 'PEAK-MILK-900G', 'Provisions', '7200', '6500', '100', '15']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Bulk import POS products from an uploaded CSV sheet.
     */
    public function importCsv(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);
        if (!$header) {
            return redirect()->route('pos.products.index')->with('error', 'Uploaded CSV file is empty or invalid.');
        }

        // Normalize header keys
        $headerMap = [];
        foreach ($header as $index => $col) {
            $cleaned = strtolower(trim(str_replace([' ', '_', '-'], '', $col)));
            $headerMap[$cleaned] = $index;
        }

        $importedCount = 0;
        $updatedCount = 0;

        try {
            DB::transaction(function () use ($handle, $headerMap, $sellerId, &$importedCount, &$updatedCount) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty(array_filter($row))) continue;

                    $name = $row[$headerMap['name'] ?? -1] ?? null;
                    if (!$name) continue;

                    $code = $row[$headerMap['code'] ?? $headerMap['sku'] ?? -1] ?? null;
                    if (!$code) {
                        $code = 'SKU-' . strtoupper(Str::random(6));
                    } else {
                        $code = strtoupper(trim($code));
                    }

                    $category = $row[$headerMap['poscategory'] ?? $headerMap['category'] ?? -1] ?? 'General';
                    $unitPrice = (float) ($row[$headerMap['unitprice'] ?? $headerMap['price'] ?? -1] ?? 0);
                    $purchasePrice = (float) ($row[$headerMap['purchaseprice'] ?? -1] ?? 0);
                    $stock = (int) ($row[$headerMap['currentstock'] ?? $headerMap['stock'] ?? $headerMap['quantity'] ?? -1] ?? 0);
                    $minStock = (int) ($row[$headerMap['posreorderlevel'] ?? $headerMap['reorderlevel'] ?? $headerMap['minstock'] ?? -1] ?? 5);

                    // Scope lookup strictly to current seller
                    $product = Product::where('user_id', $sellerId)->where('code', $code)->first();

                    if ($product) {
                        DB::table('products')->where('id', $product->id)->update([
                            'name'              => $name,
                            'pos_category'      => $category,
                            'pos_reorder_level' => $minStock,
                            'unit_price'        => $unitPrice > 0 ? $unitPrice : $product->unit_price,
                            'purchase_price'    => $purchasePrice > 0 ? $purchasePrice : $product->purchase_price,
                            'current_stock'     => $stock > 0 ? $stock : $product->current_stock,
                            'updated_at'        => now(),
                        ]);

                        DB::table('product_stocks')->where('product_id', $product->id)->update([
                            'price'      => $unitPrice > 0 ? $unitPrice : $product->unit_price,
                            'qty'        => $stock > 0 ? $stock : $product->current_stock,
                            'updated_at' => now(),
                        ]);

                        $updatedCount++;
                    } else {
                        $slug = Str::slug($name) . '-' . Str::lower(Str::random(5));
                        $productId = DB::table('products')->insertGetId([
                            'user_id'          => $sellerId,
                            'added_by'         => 'seller',
                            'name'             => $name,
                            'code'             => $code,
                            'slug'             => $slug,
                            'pos_barcode'      => $code,
                            'pos_category'     => $category,
                            'pos_reorder_level' => $minStock,
                            'category_id'      => 1,
                            'category_ids'     => json_encode([['id' => '1', 'position' => 1]]),
                            'unit_price'       => $unitPrice,
                            'purchase_price'   => $purchasePrice,
                            'current_stock'    => $stock,
                            'minimum_order_qty' => 1,
                            'min_qty'          => 1,
                            'unit'             => 'pc',
                            'status'           => 0,
                            'request_status'   => 0,
                            'published'        => 0,
                            'images'           => json_encode([]),
                            'color_image'      => json_encode([]),
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        DB::table('product_stocks')->insert([
                            'product_id' => $productId,
                            'sku'        => $code,
                            'price'      => $unitPrice,
                            'qty'        => $stock,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $importedCount++;
                    }
                }
            });
        } catch (\Throwable $e) {
            fclose($handle);
            return redirect()->route('pos.products.index')->with('error', 'Error during CSV import: ' . $e->getMessage());
        }

        fclose($handle);
        return redirect()->route('pos.products.index')->with('success', "✓ Bulk import complete! Added {$importedCount} new products, updated {$updatedCount} existing items.");
    }

    /**
     * Export master POS product catalog to CSV.
     */
    public function exportCsv(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $fileName = "vmarket_pos_products_catalog_" . date('Y_m_d_His') . ".csv";

        return response()->stream(function () use ($sellerId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU / Code', 'Product Name', 'Category', 'Selling Price (NGN)', 'Purchase Price (NGN)', 'Min Stock Alert', 'Current Stock', 'Asset Value (NGN)']);

            $products = Product::where('user_id', $sellerId)->where('status', '!=', 2)->orderBy('name')->get();
            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->code,
                    $p->name,
                    $p->pos_category ?? 'General',
                    $p->unit_price,
                    $p->purchase_price,
                    $p->pos_reorder_level ?? 5,
                    $p->current_stock,
                    $p->current_stock * (float) $p->unit_price
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Export master POS product catalog to JSON for AI analysis.
     */
    public function exportJson(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $fileName = "vmarket_pos_products_catalog_" . date('Y_m_d_His') . ".json";

        $products = Product::where('user_id', $sellerId)->where('status', '!=', 2)->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'pos_category' => $p->pos_category ?? 'General',
                'unit_price' => (float) $p->unit_price,
                'purchase_price' => (float) $p->purchase_price,
                'pos_reorder_level' => (int) ($p->pos_reorder_level ?? 5),
                'current_stock' => (int) $p->current_stock,
                'total_asset_value' => $p->current_stock * (float) $p->unit_price,
            ];
        });

        return response()->json([
            'metadata' => [
                'report' => 'Vmarket Products & Price Catalog',
                'generated_at' => now()->toIso8601String(),
                'total_skus' => $products->count(),
            ],
            'products' => $products,
        ], 200, [
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ], JSON_PRETTY_PRINT);
    }
}
