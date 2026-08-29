<?php

namespace Modules\Delivery\app\Models;

use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBatch extends Model
{
    protected $table = 'delivery_batches';

    protected $fillable = [
        'batch_no',
        'origin_hub_id',
        'destination_hub_id',
        'driver_id',
        'vehicle_no',
        'package_count',
        'total_weight_kg',
        'transit_otp',
        'status',
        'dispatched_at',
        'received_at',
    ];

    protected $casts = [
        'package_count' => 'integer',
        'total_weight_kg' => 'float',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(DeliveryHub::class, 'origin_hub_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(DeliveryHub::class, 'destination_hub_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'driver_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'delivery_batch_orders', 'batch_id', 'order_id')
            ->withPivot('status')
            ->withTimestamps();
    }
}
