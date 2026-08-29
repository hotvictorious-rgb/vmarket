<?php

namespace Modules\Delivery\app\Models;

use App\Models\DeliveryHub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRoute extends Model
{
    protected $table = 'delivery_routes';

    protected $fillable = [
        'origin_hub_id',
        'destination_hub_id',
        'customer_fee',
        'rider_payout',
        'logistics_partner_margin',
        'estimated_hours',
        'transit_type',
        'is_active',
    ];

    protected $casts = [
        'customer_fee' => 'float',
        'rider_payout' => 'float',
        'logistics_partner_margin' => 'float',
        'estimated_hours' => 'float',
        'is_active' => 'boolean',
    ];

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(DeliveryHub::class, 'origin_hub_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(DeliveryHub::class, 'destination_hub_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
