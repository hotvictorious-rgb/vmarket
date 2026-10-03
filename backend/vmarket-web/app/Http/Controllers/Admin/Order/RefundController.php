<?php

namespace App\Http\Controllers\Admin\Order;

use App\Contracts\Repositories\AdminWalletRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\OrderDetailRepositoryInterface;
use App\Contracts\Repositories\OrderDetailsRewardsRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\RefundRequestRepositoryInterface;
use App\Contracts\Repositories\RefundStatusRepositoryInterface;
use App\Contracts\Repositories\RefundTransactionRepositoryInterface;
use App\Contracts\Repositories\VendorWalletRepositoryInterface;
use App\Enums\ExportFileNames\Admin\RefundRequest as RefundRequestExportFile;
use App\Events\RefundEvent;
use App\Exports\RefundRequestExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\RefundStatusRequest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\RefundRequest;
use App\Services\RefundStatusService;
use App\Services\RefundTransactionService;
use App\Traits\CustomerTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundController extends BaseController
{
    use CustomerTrait;

    public function __construct(
        private readonly RefundRequestRepositoryInterface           $refundRequestRepo,
        private readonly CustomerRepositoryInterface                $customerRepo,
        private readonly OrderRepositoryInterface                   $orderRepo,
        private readonly OrderDetailRepositoryInterface             $orderDetailRepo,
        private readonly AdminWalletRepositoryInterface             $adminWalletRepo,
        private readonly VendorWalletRepositoryInterface            $vendorWalletRepo,
        private readonly RefundStatusRepositoryInterface            $refundStatusRepos,
        private readonly RefundTransactionRepositoryInterface       $refundTransactionRepo,
        private readonly OrderDetailsRewardsRepositoryInterface     $orderDetailsRewardsRepo,
    )
    {
    }

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $status = $type;
        $fromDate = $request['from_date'];
        $toDate = $request['to_date'];
        $walletStatus = getWebConfig(name: 'wallet_status');
        $walletAddRefund = getWebConfig(name: 'wallet_add_refund');
        $refundList = $this->refundRequestRepo->getListWhereHas(
            orderBy: ['id' => 'desc'],
            searchValue: $request['searchValue'],
            filters: [
                'status'    => $status,
                'from_date' => $fromDate,
                'to_date'   => $toDate
            ],
            whereHas: 'order',
            whereHasFilters: ['seller_is' => $request['type']],
            relations: ['order', 'order.seller', 'order.deliveryMan', 'product'],
            dataLimit: getWebConfig('pagination_limit'),
        );
        return view('admin-views.refund.list', compact('refundList', 'status','walletStatus','walletAddRefund'));
    }

    public function getDetailsView($id): View|RedirectResponse
    {
        $refund = $this->refundRequestRepo->getFirstWhere(params: ['id' => $id], relations: ['order.details']);
        if (!$refund || !$refund?->orderDetails) {
            ToastMagic::error(translate('Refund Details not found'));
            return back();
        }
        $order = $refund->order;
        $totalProductPrice = 0;
        foreach ($order->details as $or_d) {
            $totalProductPrice += ($or_d->qty * $or_d->price) + $or_d->tax - $or_d->discount;
        }
        $subtotal = ($refund?->orderDetails?->price * $refund?->orderDetails?->qty) - $refund?->orderDetails?->discount + $refund?->orderDetails?->tax;
        $couponDiscount = $order->discount_amount > 0 ? ($order->discount_amount * $subtotal) / $totalProductPrice : 0;
        $referralDiscount = $order?->refer_and_earn_discount ?? 0;
        $refundAmount = $subtotal - $couponDiscount - $referralDiscount;

        $walletStatus = getWebConfig(name: 'wallet_status');
        $walletAddRefund = getWebConfig(name: 'wallet_add_refund');

        return view('admin-views.refund.details',
            compact('refund',
                'order',
                'totalProductPrice',
                'subtotal',
                'couponDiscount',
                'referralDiscount',
                'refundAmount',
                'walletStatus',
                'walletAddRefund'
            ));
    }

    public function updateRefundStatus(RefundStatusRequest $request, RefundStatusService $refundStatusService, RefundTransactionService $refundTransactionService): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $lockedRequest = RefundRequest::where('id', $request['id'])->lockForUpdate()->first();
            if (!$lockedRequest) {
                return response()->json(['error' => translate('refund_request_not_found') . '.'], 404);
            }

            // Terminal Lock 1: Once refunded or execution succeeded, NO transitions are permitted
            if ($lockedRequest->status === 'refunded' || $lockedRequest->execution_status === 'succeeded') {
                return response()->json(['error' => translate('when_refund_status_refunded') . ',' . translate('then_you_can`t_change_refund_status') . '.'], 400);
            }

            // Terminal Lock 2: Once rejected, it cannot be approved or refunded
            if ($lockedRequest->status === 'rejected') {
                return response()->json(['error' => translate('refund_request_already_rejected') . '.'], 400);
            }

            $user = $this->customerRepo->getFirstWhere(params: ['id' => $lockedRequest->customer_id]);
            if (!isset($user)) {
                return response()->json(['error' => translate('this_account_has_been_deleted_you_can_not_modify_the_status') . '.'], 400);
            }

            // [AI] Reject Customer Wallet Refund Destination
            if ($request['payment_method'] === 'customer_wallet') {
                return response()->json(['error' => translate('Customer wallet is not a supported refund method.')], 400);
            }

            $lockedOrder = Order::where('id', $lockedRequest->order_id)->lockForUpdate()->first();
            if (!$lockedOrder) {
                return response()->json(['error' => translate('order_not_found') . '.'], 404);
            }

            $lockedDetail = OrderDetail::where('id', $lockedRequest->order_details_id)->lockForUpdate()->first();
            if (!$lockedDetail) {
                return response()->json(['error' => translate('order_details_not_found') . '.'], 404);
            }

            // Check if pure cashback/reward refund (zero money involved)
            $paymentInfo = json_decode($lockedRequest->payment_info ?? '{}', true) ?: [];
            $refundableMoney = $paymentInfo['refundable_money_amount'] ?? ($paymentInfo['money_amount'] ?? null);
            $isPureRewardRefund = ($refundableMoney !== null && bccomp((string)$refundableMoney, '0.00', 2) === 0)
                || ($lockedOrder && $lockedOrder->payment_method === 'cashback')
                || (bccomp((string)($lockedOrder->order_amount ?? '0.00'), '0.00', 2) === 0);

            // TRANSITION 1: APPROVAL
            if ($request['refund_status'] === 'approved') {
                // Must currently be in 'pending' status
                if ($lockedRequest->status !== 'pending') {
                    return response()->json(['error' => "Only pending refund requests can be approved. Current status: {$lockedRequest->status}."], 400);
                }

                if ($isPureRewardRefund) {
                    // Pure cashback refunds do not require offline manual bank transfers; complete internally
                    $paystackRefundService = app(\App\Services\PaystackRefundService::class);
                    $result = $paystackRefundService->finalizeManualPaymentConfirmation($lockedRequest, $lockedOrder, [
                        'payment_method' => 'cashback',
                        'approved_note' => $request['approved_note'] ?? 'Approved cashback refund',
                        'confirmed_by' => auth('admin')->id() ?? 1,
                    ]);

                    $this->refundStatusRepos->add(data: [
                        'refund_request_id' => $lockedRequest->id,
                        'change_by' => 'admin',
                        'change_by_id' => auth('admin')->id() ?? 1,
                        'status' => 'refunded',
                        'message' => $request['approved_note'] ?? 'Pure reward refund completed internally',
                    ]);

                    event(new RefundEvent(status: 'refunded', order: $lockedOrder, refund: $lockedRequest, orderDetails: $lockedDetail));
                    return response()->json(['message' => translate('cashback_refund_approved_and_completed_internally') . '.']);
                }

                // Money refund or mixed refund: Transition to approved / awaiting manual payment
                $lockedDetail->update(['refund_request' => 2]); // 2 = Approved
                $lockedRequest->update([
                    'status' => 'approved',
                    'execution_status' => 'awaiting_manual_payment',
                    'approved_note' => $request['approved_note'] ?? null,
                    'change_by' => 'admin',
                ]);

                $this->refundStatusRepos->add(data: [
                    'refund_request_id' => $lockedRequest->id,
                    'change_by' => 'admin',
                    'change_by_id' => auth('admin')->id() ?? 1,
                    'status' => 'approved',
                    'message' => $request['approved_note'] ?? 'Refund approved, awaiting manual payment',
                ]);

                event(new RefundEvent(status: 'approved', order: $lockedOrder, refund: $lockedRequest, orderDetails: $lockedDetail));
                return response()->json(['message' => translate('refund_request_approved_and_awaiting_manual_payment') . '.']);
            }

            // TRANSITION 2: PAYMENT CONFIRMATION (REFUNDED)
            if ($request['refund_status'] === 'refunded') {
                // Must currently be in 'approved' status awaiting payment
                if ($lockedRequest->status !== 'approved' || $lockedRequest->execution_status !== 'awaiting_manual_payment') {
                    return response()->json([
                        'error' => "Only approved refund requests awaiting manual payment can be confirmed as refunded. Current status: {$lockedRequest->status} ({$lockedRequest->execution_status})."
                    ], 400);
                }

                // Handle file upload for payment evidence if provided
                $evidencePath = null;
                if ($request->hasFile('payment_evidence')) {
                    $file = $request->file('payment_evidence');
                    $filename = 'refund_evidence_' . $lockedRequest->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                    $evidencePath = $file->storeAs('refund/evidence', $filename, 'public');
                }

                $paystackRefundService = app(\App\Services\PaystackRefundService::class);
                $result = $paystackRefundService->finalizeManualPaymentConfirmation($lockedRequest, $lockedOrder, [
                    'payment_method' => $request['payment_method'] ?? 'manual_offline',
                    'payment_info' => $request['payment_info'] ?? ($request['payment_reference'] ?? ''),
                    'payment_reference' => $request['payment_reference'] ?? ($request['payment_info'] ?? ''),
                    'amount' => $request['amount'] ?? null,
                    'payment_date' => $request['payment_date'] ?? now()->toDateString(),
                    'payment_evidence' => $evidencePath,
                    'confirmed_by' => auth('admin')->id() ?? 1,
                ]);

                if (!$result['status']) {
                    return response()->json(['error' => $result['message']], 400);
                }

                $refMsg = 'Payment confirmed via ' . ($request['payment_method'] ?? 'manual_offline');
                if (!empty($request['payment_reference'])) {
                    $refMsg .= ' (Ref: ' . $request['payment_reference'] . ')';
                }
                $this->refundStatusRepos->add(data: [
                    'refund_request_id' => $lockedRequest->id,
                    'change_by' => 'admin',
                    'change_by_id' => auth('admin')->id() ?? 1,
                    'status' => 'refunded',
                    'message' => $refMsg,
                ]);

                event(new RefundEvent(status: 'refunded', order: $lockedOrder, refund: $lockedRequest, orderDetails: $lockedDetail));
                return response()->json(['message' => translate('refund_payment_confirmed_and_completed_successfully') . '.']);
            }

            // TRANSITION 3: REJECTION
            if ($request['refund_status'] === 'rejected') {
                if (!in_array($lockedRequest->status, ['pending', 'approved'], true)) {
                    return response()->json(['error' => "Cannot reject refund request in status '{$lockedRequest->status}'."], 400);
                }

                $lockedDetail->update(['refund_request' => 3]); // 3 = Rejected
                $lockedRequest->update([
                    'status' => 'rejected',
                    'execution_status' => 'rejected',
                    'rejected_note' => $request['rejected_note'] ?? null,
                    'change_by' => 'admin',
                ]);

                $this->refundStatusRepos->add(data: [
                    'refund_request_id' => $lockedRequest->id,
                    'change_by' => 'admin',
                    'change_by_id' => auth('admin')->id() ?? 1,
                    'status' => 'rejected',
                    'message' => $request['rejected_note'] ?? 'Refund rejected',
                ]);

                event(new RefundEvent(status: 'rejected', order: $lockedOrder, refund: $lockedRequest, orderDetails: $lockedDetail));
                return response()->json(['message' => translate('refund_status_updated') . '.']);
            }

            return response()->json(['error' => translate('invalid_refund_status') . '.'], 400);
        });
    }

    public function exportList(Request $request, $status): BinaryFileResponse
    {
        $fromDate = $request['from_date'];
        $toDate = $request['to_date'];
        $refundList = $this->refundRequestRepo->getListWhereHas(
            orderBy: ['id' => 'desc'],
            searchValue: $request['searchValue'],
            filters: [
                'status'    => $status,
                'from_date' => $fromDate,
                'to_date'   => $toDate
            ],
            whereHas: 'order',
            whereHasFilters: ['seller_is' => $request['type']],
            relations: ['order', 'order.seller', 'order.deliveryMan', 'product'],
            dataLimit: 'all',
        );

        return Excel::download(new RefundRequestExport([
            'data-from' => 'admin',
            'refundList' => $refundList,
            'search' => $request['searchValue'],
            'status' => $status,
            'from' => $fromDate,
            'to'   => $toDate,
            'filter_By' => $request->get('type', 'all'),
        ]), RefundRequestExportFile::EXPORT_XLSX);
    }

}
