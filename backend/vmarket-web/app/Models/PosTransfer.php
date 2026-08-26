<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [AI] Class PosTransfer
 * Manages inter-branch stock waybills, in-transit buffers, and theft variance detection.
 */
class PosTransfer extends Model
{
    use HasFactory;

    protected $table = 'pos_transfers';

    protected $fillable = [
        'seller_id',
        'waybill_number',
        'origin_branch_id',
        'destination_branch_id',
        'dispatched_by_id',
        'received_by_id',
        'driver_name',
        'driver_phone',
        'vehicle_number',
        'total_items_dispatched',
        'total_items_received',
        'variance_count',
        'status',
        'dispatched_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'origin_branch_id' => 'integer',
        'destination_branch_id' => 'integer',
        'dispatched_by_id' => 'integer',
        'received_by_id' => 'integer',
        'total_items_dispatched' => 'integer',
        'total_items_received' => 'integer',
        'variance_count' => 'integer',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'origin_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'destination_branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosTransferItem::class, 'transfer_id');
    }
}
