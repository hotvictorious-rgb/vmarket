<?php

namespace Modules\Delivery\app\Models;

use App\Models\DeliveryMan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery3plCompany extends Model
{
    protected $table = 'delivery_3pl_companies';

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'password',
        'address',
        'commission_rate',
        'status',
        'auth_token',
    ];

    protected $casts = [
        'commission_rate' => 'float',
    ];

    protected $hidden = [
        'password',
        'auth_token',
    ];

    public function riders(): HasMany
    {
        return $this->hasMany(DeliveryMan::class, 'company_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
