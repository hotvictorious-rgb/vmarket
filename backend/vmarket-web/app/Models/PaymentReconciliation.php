<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * [AI] Model PaymentReconciliation
 * 
 * Maps to the payment_reconciliations table for tracking abnormal captures,
 * late captures on expired checkouts, stock shortages, and financial reversals.
 */
class PaymentReconciliation extends Model
{
    protected $table = 'payment_reconciliations';

    protected $guarded = ['id'];

    protected $casts = [
        'audit_events' => 'array',
        'captured_amount' => 'decimal:4',
        'expected_amount' => 'decimal:4',
        'resolved_at' => 'datetime',
        'last_event_at' => 'datetime',
    ];

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
