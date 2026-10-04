<?php

namespace App\Http\Controllers\Vendor;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\OrderDetailRepositoryInterface;
use App\Contracts\Repositories\OrderDetailsRewardsRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\RefundRequestRepositoryInterface;
use App\Contracts\Repositories\RefundStatusRepositoryInterface;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Enums\ExportFileNames\Admin\RefundRequest as RefundRequestExportFile;
use App\Enums\ViewPaths\Vendor\Refund;
use App\Events\RefundEvent;
use App\Exports\RefundRequestExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\RefundStatusRequest;
use App\Repositories\VendorRepository;
use App\Services\RefundStatusService;
use App\Traits\CustomerTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundController extends BaseController
{
    use CustomerTrait;

    public function __construct(
        private readonly RefundRequestRepositoryInterface       $refundRequestRepo,
        private readonly CustomerRepositoryInterface            $customerRepo,
        private readonly OrderDetailRepositoryInterface         $orderDetailRepo,
        private readonly RefundStatusRepositoryInterface        $refundStatusRepo,
        private readonly RefundStatusService                    $refundStatusService,
        private readonly OrderRepositoryInterface               $orderRepo,
        private readonly VendorRepositoryInterface              $vendorRepo,
        private readonly OrderDetailsRewardsRepositoryInterface $orderDetailsRewardsRepo,
    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View|Collection|LengthAwarePaginator|callable|RedirectResponse|null
     */
    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getList(request: $request, status: $type);
    }

    /**
     * @param object $request
     * @param string $status
     * @return View
     */
    public function getList(object $request, string $status): View
    {
        $vendorId = auth('seller')->id();
        $searchValue = $request['search'] ?? null;
        $fromDate = $request['from_date'];
        $toDate = $request['to_date'];
        $refundList = $this->refundRequestRepo->getListWhereHas(
            orderBy: ['id' => 'desc'],
            searchValue: $searchValue,
            filters: [
                'status'    => $status,
                'from_date' => $fromDate,
                'to_date'   => $toDate
            ],
            whereHas: 'order',
            whereHasFilters: ['seller_is' => 'seller', 'seller_id' => $vendorId],
            relations: ['customer'],
            dataLimit: getWebConfig('pagination_limit'),

        );
        return view('vendor-views.refund.index', compact('refundList', 'searchValue'));
    }

    /**
     * @param string|int $id
     * @return View|RedirectResponse
     */
    public function getDetailsView(string|int $id): View|RedirectResponse
    {
        $vendorId = auth('seller')->id();
        $refund = $this->refundRequestRepo->getFirstWhereHas(
            params: ['id' => $id],
            whereHas: 'order',
            whereHasFilters: ['seller_is' => 'seller', 'seller_id' => $vendorId],
            relations: ['order.details'],
        );
        if (!$refund || !$refund?->orderDetails || !$refund->order) {
            ToastMagic::error(translate('Refund Details not found'));
            return back();
        }
        $order = $refund->order;
        $totalProductPrice = 0;
        foreach ($order->details as $key => $orderDetails) {
            $totalProductPrice += ($orderDetails->qty * $orderDetails->price) + $orderDetails->tax - $orderDetails->discount;
        }
        $subtotal = $refund->orderDetails->price * $refund->orderDetails->qty - $refund->orderDetails->discount + $refund->orderDetails->tax;
        $couponDiscount = $order->discount_amount > 0 ? ($order->discount_amount * $subtotal) / $totalProductPrice : 0;

        $referralDiscount = $order?->refer_and_earn_discount ?? 0;
        $refundAmount = $subtotal - $couponDiscount - $referralDiscount;


        return view(Refund::DETAILS[VIEW], compact('refund', 'order', 'refundAmount', 'subtotal', 'couponDiscount', 'refundAmount', 'referralDiscount'));
    }

    /**
     * @param RefundStatusRequest $request
     * @return JsonResponse
     */
    public function updateStatus(RefundStatusRequest $request): JsonResponse
    {
        // [AI] Vendor Web shares the same order-locked recommendation boundary as Seller Mobile.
        $decision = (string)$request->input('refund_status');
        $result = app(\App\Services\VendorRefundDecisionService::class)->decide((int)auth('seller')->id(),
            (int)$request->input('id'), $decision, $request->input($decision === 'approved' ? 'approved_note' : 'rejected_note'));
        return response()->json(['message' => $result['message']], $result['status'] ? 200 : $result['code']);
    }

    public function exportList(Request $request, $status): BinaryFileResponse
    {
        $vendorId = auth('seller')->id();
        $vendor = $this->vendorRepo->getFirstWhere(params: ['id' => $vendorId]);
        $filter = [
          'status' => $request['status'],
          'from_date' => $request['from_date'],
          'to_date' => $request['to_date'],
        ];
        $refundList = $this->refundRequestRepo->getListWhereHas(
            orderBy: ['id' => 'desc'],
            searchValue: $request['search'],
            filters: $filter,
            whereHas: 'order',
            whereHasFilters: ['seller_is' => 'seller', 'seller_id' => $vendorId],
            relations: ['order', 'order.seller', 'order.deliveryMan', 'product'],
            dataLimit: 'all',
        );
        return Excel::download(new RefundRequestExport([
            'data-from' => 'vendor',
            'vendor' => $vendor,
            'refundList' => $refundList,
            'search' => $request['search'],
            'status' => $status,
            'from' => $request['from_date'],
            'to' => $request['to_date'],
            'filter_By' => $request->get('type', 'all'),
        ]), RefundRequestExportFile::EXPORT_XLSX);
    }
}
