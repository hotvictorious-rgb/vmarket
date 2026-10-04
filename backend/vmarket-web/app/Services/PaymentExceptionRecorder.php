<?php

namespace App\Services;

use App\Models\PaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** [AI] Durable exceptions for signed provider events that cannot enter canonical accounting. */
class PaymentExceptionRecorder
{
    public static function record(?string $reference, array $data, string $reason, ?PaymentRequest $payment = null, string $event = 'charge.success'): void
    {
        $reference = $reference ?: 'uncorrelated-' . hash('sha256', json_encode([$event, $data]));
        $audit = ['type' => $reason, 'event' => $event, 'provider_id' => $data['id'] ?? null,
            'amount_kobo' => $data['amount'] ?? null, 'currency' => $data['currency'] ?? null,
            'received_at' => now()->toIso8601String()];
        DB::transaction(function () use ($reference, $data, $reason, $payment, $audit) {
            // [AI] Unique gateway reference arbitrates parallel deliveries; preserve one open case.
            DB::table('payment_reconciliations')->insertOrIgnore([
                'case_number' => 'REC-' . Str::uuid(), 'gateway_reference' => $reference,
                'payment_request_id' => $payment?->id, 'payment_domain' => $payment?->payment_domain ?: 'unclassified',
                'order_group_id' => $payment?->order_group_id, 'customer_id' => $payment?->payer_id,
                'gateway_name' => 'paystack', 'captured_amount' => bcdiv((string) max(0, (int) ($data['amount'] ?? 0)), '100', 4),
                'expected_amount' => $payment?->payment_amount ?? '0.00', 'currency' => $data['currency'] ?? 'NGN',
                'initial_anomaly_type' => 'other', 'current_status' => 'open', 'audit_events' => '[]',
                'last_event_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $case = DB::table('payment_reconciliations')->where('gateway_reference', $reference)->lockForUpdate()->first();
            if (!$case) {
                throw new \RuntimeException('Unable to persist payment exception.');
            }
            $events = json_decode($case->audit_events, true) ?: [];
            // [AI] Replayed notifications preserve the first evidence without an unbounded duplicate trail.
            foreach ($events as $existing) {
                if (($existing['type'] ?? '') === $reason && ($existing['event'] ?? '') === $audit['event']
                    && ($existing['provider_id'] ?? null) === $audit['provider_id']) {
                    return;
                }
            }
            $events[] = $audit;
            DB::table('payment_reconciliations')->where('id', $case->id)->update([
                'audit_events' => json_encode($events), 'last_event_at' => now(), 'updated_at' => now(),
            ]);
        });
    }
}
