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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'id' => 'integer',
        'seller_id' => 'integer',
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
        'vendor_role_id',
        'name',
        'phone',
        'email',
        'password',
        'image',
        'status',
        'remember_token',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(VendorRole::class, 'vendor_role_id');
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
