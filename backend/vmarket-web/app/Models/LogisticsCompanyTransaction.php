<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LogisticsCompanyTransaction
 *
 * @property int $id
 * @property int $logistics_company_id
 * @property int|null $order_id
 * @property int|null $delivery_man_id
 * @property float $gross_delivery_fee
 * @property float $admin_commission_rate
 * @property float $admin_commission_amount
 * @property float $net_partner_amount
 * @property string $transaction_type
 * @property float $balance_before
 * @property float $balance_after
 * @property string|null $transaction_note
 */
class LogisticsCompanyTransaction extends Model
{
    protected $table = 'logistics_company_transactions';

    protected $fillable = [
        'logistics_company_id',
        'order_id',
        'delivery_man_id',
        'gross_delivery_fee',
        'admin_commission_rate',
        'admin_commission_amount',
        'net_partner_amount',
        'transaction_type',
        'balance_before',
        'balance_after',
        'transaction_note',
    ];

    protected $casts = [
        'gross_delivery_fee' => 'decimal:2',
        'admin_commission_rate' => 'decimal:2',
        'admin_commission_amount' => 'decimal:2',
        'net_partner_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }
}
