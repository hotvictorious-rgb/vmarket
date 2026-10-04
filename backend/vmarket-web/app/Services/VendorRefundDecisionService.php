<?php

namespace App\Services;

use App\Events\RefundEvent;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\RefundRequest;
use App\Models\RefundStatus;
use Illuminate\Support\Facades\DB;

class VendorRefundDecisionService
{
    // [AI] Vendor Web/App submit recommendations; only Admin accounting can financially complete a refund.
    public function decide(int $vendorId, int $refundId, string $decision, ?string $note): array
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return ['status' => false, 'message' => 'Only approve/reject recommendations are supported.', 'code' => 422];
        }
        return DB::transaction(function () use ($vendorId, $refundId, $decision, $note) {
            $orderId = RefundRequest::whereKey($refundId)->value('order_id');
            $order = Order::whereKey($orderId)->where('seller_is', 'seller')->where('seller_id', $vendorId)->lockForUpdate()->first();
            if (!$order) return ['status' => false, 'message' => 'Unauthorized refund.', 'code' => 403];
            $refund = RefundRequest::whereKey($refundId)->lockForUpdate()->firstOrFail();
            if ($refund->change_by === 'admin' || $refund->execution_status === 'succeeded' || $refund->status === 'refunded') {
                return ['status' => false, 'message' => 'Admin or completed refunds cannot be changed.', 'code' => 409];
            }
            if ($refund->status !== 'pending') {
                return ['status' => $refund->status === $decision, 'message' => 'Recommendation already submitted.', 'code' => 409];
            }
            $detail = OrderDetail::whereKey($refund->order_details_id)->lockForUpdate()->firstOrFail();
            $detail->refund_request = $decision === 'approved' ? 2 : 3;
            $detail->save();
            $refund->status = $decision;
            $refund->change_by = 'seller';
            $refund->{$decision === 'approved' ? 'approved_note' : 'rejected_note'} = $note;
            $refund->save();
            RefundStatus::create(['refund_request_id' => $refundId, 'change_by' => 'seller', 'change_by_id' => $vendorId,
                'status' => $decision, 'message' => $note]);
            event(new RefundEvent(status: $decision, order: $order, refund: $refund, orderDetails: $detail));
            return ['status' => true, 'message' => 'Refund recommendation submitted for Admin review.', 'code' => 200];
        });
    }
}
