<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class VendorRole
 *
 * @property int $id
 * @property int $seller_id
 * @property string $name
 * @property string $module_access
 * @property bool $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 */
class VendorRole extends Model
{
    protected $casts = [
        'id' => 'integer',
        'seller_id' => 'integer',
        'name' => 'string',
        'module_access' => 'array',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'seller_id',
        'name',
        'module_access',
        'status',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(VendorEmployee::class, 'vendor_role_id');
    }
}
