<?php

namespace App\Models;

use App\Traits\StorageTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class VendorEmployee
 *
 * @property int $id
 * @property int $seller_id
 * @property int $vendor_role_id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string $password
 * @property string $image
 * @property bool $status
 * @property string $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 */
class VendorEmployee extends Authenticatable
{
    use Notifiable, StorageTrait;

    protected $table = 'vendor_employees';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'id' => 'integer',
        'seller_id' => 'integer',
        'shop_id' => 'integer',
        'vendor_role_id' => 'integer',
        'name' => 'string',
        'phone' => 'string',
        'email' => 'string',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'seller_id',
        'shop_id',
        'vendor_role_id',
        'name',
        'phone',
        'email',
        'password',
        'image',
        'status',
        'auth_token',
        'remember_token',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(VendorRole::class, 'vendor_role_id');
    }

    /**
     * [AI] Scope employee queries to a specific physical branch/shop.
     */
    public function scopeForShop($query, ?int $shopId)
    {
        if ($shopId) {
            return $query->where('shop_id', $shopId);
        }
        return $query;
    }

    /**
     * [AI] Branch Security Isolation:
     * Check if employee is authorized for a specific physical shop/branch.
     * If employee's shop_id is null, they are an organization-wide master employee of the seller.
     * If employee's shop_id is set, they strictly only have access to that specific physical branch.
     */
    public function canAccessShop(?int $targetShopId): bool
    {
        if (empty($this->shop_id)) {
            return true; // Organization-wide seller staff
        }
        if (empty($targetShopId)) {
            return false;
        }
        return (int) $this->shop_id === (int) $targetShopId;
    }

    /**
     * Check if employee has access to a specific module
     */
    public function hasModuleAccess(string $module): bool
    {
        if (!$this->role || !$this->role->status) {
            return false;
        }

        $accessList = $this->role->module_access ?? [];
        if (is_string($accessList)) {
            $accessList = json_decode($accessList, true) ?? [];
        }

        return in_array($module, $accessList);
    }
}
