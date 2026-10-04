<?php
namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

/** [AI] Exact immutable per-line funding allocations, shared by preview, request and refund execution. */
class OrderRefundAllocationService
{
    private function allocate(string $total, array $weights): array
    {
        $sum = '0.00'; foreach ($weights as $w) $sum = bcadd($sum, $w, 2);
        if (bccomp($total, '0', 2) < 0 || bccomp($total, $sum, 2) > 0) throw new \RuntimeException('Refund discount exceeds captured merchandise.');
        $allocated = []; $fractions = []; $used = '0.00';
        foreach ($weights as $id => $weight) {
            $exact = bccomp($sum, '0', 2) > 0 ? bcdiv(bcmul($total, $weight, 8), $sum, 8) : '0';
            $allocated[$id] = bcadd($exact, '0', 2);
            $fractions[$id] = bcsub($exact, $allocated[$id], 8);
            $used = bcadd($used, $allocated[$id], 2);
        }
        $ids = array_keys($weights);
        usort($ids, fn($a, $b) => bccomp($fractions[$b], $fractions[$a], 8) ?: ($a <=> $b));
        $left = bcsub($total, $used, 2);
        foreach ($ids as $id) {
            if (bccomp($left, '0', 2) <= 0) break;
            if (bccomp($allocated[$id], $weights[$id], 2) < 0) {
                $allocated[$id] = bcadd($allocated[$id], '0.01', 2); $left = bcsub($left, '0.01', 2);
            }
        }
        if (bccomp($left, '0', 2) !== 0) throw new \RuntimeException('Refund residual could not be allocated.');
        return $allocated;
    }

    public function forDetail(int $id): array
    {
        $detail = OrderDetail::findOrFail($id);
        return DB::transaction(function () use ($detail, $id) {
            $order = Order::whereKey($detail->order_id)->lockForUpdate()->firstOrFail();
            $details = OrderDetail::where('order_id', $order->id)->orderBy('id')->lockForUpdate()->get();
            $target = $details->firstWhere('id', $id);
            $existing = json_decode((string)$target->getRawOriginal('refund_allocation'), true);
            if (is_array($existing) && ($existing['refund_allocation_version'] ?? null) === 1) return $existing;
            $weights = [];
            foreach ($details as $d) {
                $weights[$d->id] = bcsub(bcmul((string)$d->getRawOriginal('price'), (string)$d->getRawOriginal('qty'), 2), (string)$d->getRawOriginal('discount'), 2);
                if (bccomp($weights[$d->id], '0', 2) < 0) throw new \RuntimeException('Invalid captured merchandise snapshot.');
            }
            $discount = (string)($order->getRawOriginal('discount_amount') ?? '0');
            $coupons = $this->allocate($order->discount_type === 'cashback' ? '0' : $discount, $weights);
            $net = []; foreach ($weights as $key => $weight) $net[$key] = bcsub($weight, $coupons[$key], 2);
            $referrals = $this->allocate((string)($order->getRawOriginal('refer_and_earn_discount') ?? '0'), $net);
            foreach ($net as $key => $weight) $net[$key] = bcsub($weight, $referrals[$key], 2);
            $rewards = $this->allocate($order->discount_type === 'cashback' ? $discount : '0', $net);
            $result = [];
            foreach ($details as $d) {
                $key = $d->id; $tax = bcadd((string)$d->getRawOriginal('tax'), '0', 2);
                $merchCash = bcsub($net[$key], $rewards[$key], 2);
                $result[$key] = ['refund_allocation_version' => 1, 'allocations_frozen' => true,
                    'product_price' => bcadd((string)$d->getRawOriginal('price'), '0', 2),
                    'product_discount' => bcadd((string)$d->getRawOriginal('discount'), '0', 2),
                    'tax' => $tax, 'sub_total' => $weights[$key], 'coupon_discount' => $coupons[$key],
                    'referral_discount' => $referrals[$key], 'refundable_merchandise_value' => $net[$key],
                    'refundable_merchandise_money' => $merchCash, 'refundable_tax_amount' => $tax,
                    'refundable_cashback_amount' => $rewards[$key], 'refundable_money_amount' => bcadd($merchCash, $tax, 2),
                    'total_refundable_amount' => bcadd($net[$key], $tax, 2)];
            }
            // [AI] Historical completed allocations are evidence, never silently rewritten to new proportions.
            foreach (\App\Models\RefundRequest::where('order_id', $order->id)->where('status', 'refunded')->get() as $prior) {
                $info = is_array($prior->payment_info) ? $prior->payment_info : json_decode((string)$prior->payment_info, true);
                $allocation = $result[$prior->order_details_id] ?? null;
                foreach (['refundable_merchandise_value', 'refundable_money_amount', 'refundable_cashback_amount'] as $field) {
                    if (!$allocation || !isset($info[$field]) || bccomp((string)$info[$field], $allocation[$field], 2) !== 0) {
                        throw new \RuntimeException('Historical refund allocation requires reconciliation.');
                    }
                }
            }
            foreach ($details as $d) {
                $d->refund_allocation = json_encode($result[$d->id], JSON_THROW_ON_ERROR); $d->save();
            }
            return $result[$id];
        });
    }
}
