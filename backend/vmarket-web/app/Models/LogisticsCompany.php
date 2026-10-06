<?php

namespace App\Models;

use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class LogisticsCompany
 *
 * @property int $id
 * @property string $name
 * @property string $company_email
 * @property string $company_phone
 * @property string|null $contact_person_name
 * @property string|null $contact_person_phone
 * @property string $password
 * @property string|null $cac_number
 * @property string|null $address
 * @property int|null $state_id
 * @property int|null $lga_id
 * @property array|null $operating_lgas
 * @property string|null $logo
 * @property string|null $tin_document
 * @property string|null $cac_document
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property string|null $account_name
 * @property string $status
 * @property bool $is_active
 */
class LogisticsCompany extends Authenticatable
{
    use Notifiable, StorageTrait;

    protected $table = 'logistics_companies';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'name',
        'company_email',
        'company_phone',
        'contact_person_name',
        'contact_person_phone',
        'password',
        'cac_number',
        'address',
        'state_id',
        'lga_id',
        'operating_lgas',
        'logo',
        'tin_document',
        'cac_document',
        'bank_name',
        'account_number',
        'account_name',
        'status',
        'is_active',
    ];

    protected $casts = [
        'operating_lgas' => 'array',
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lga_id');
    }

    public function deliveryMen(): HasMany
    {
        return $this->hasMany(DeliveryMan::class, 'logistics_company_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'logistics_company_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(LogisticsCompanyWallet::class, 'logistics_company_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LogisticsCompanyTransaction::class, 'logistics_company_id');
    }

    public function withdrawRequests(): HasMany
    {
        return $this->hasMany(LogisticsCompanyWithdrawRequest::class, 'logistics_company_id');
    }
}
