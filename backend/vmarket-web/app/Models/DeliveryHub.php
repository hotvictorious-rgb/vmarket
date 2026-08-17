<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryHub extends Model
{
    use HasFactory;

    protected $table = 'delivery_hubs';

    protected $fillable = [
        'city_id',
        'name',
        'type',
        'base_shipping_cost',
        'estimated_delivery_time',
        'is_active',
    ];

    protected $casts = [
        'city_id' => 'integer',
        'base_shipping_cost' => 'float',
        'is_active' => 'boolean',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(DeliveryCity::class, 'city_id');
    }
}
