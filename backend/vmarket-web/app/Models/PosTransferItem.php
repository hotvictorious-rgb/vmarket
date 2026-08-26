<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Class PosTransferItem
 * Line items for inter-branch waybills.
 */
class PosTransferItem extends Model
{
    use HasFactory;

    protected $table = 'pos_transfer_items';

    protected $fillable = [
        'transfer_id',
        'product_id',
        'dispatched_quantity',
        'received_quantity',
        'variance_quantity',
        'unit_cost',
    ];

    protected $casts = [
        'transfer_id' => 'integer',
        'product_id' => 'integer',
        'dispatched_quantity' => 'integer',
        'received_quantity' => 'integer',
        'variance_quantity' => 'integer',
        'unit_cost' => 'float',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(PosTransfer::class, 'transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
