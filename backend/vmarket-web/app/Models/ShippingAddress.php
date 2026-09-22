<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class ShippingAddress
 *
 * @property int $id
 * @property string|null $customer_id
 * @property bool $is_guest
 * @property string|null $contact_person_name
 * @property string|null $email
 * @property string $address_type
 * @property string|null $address
 * @property string|null $city
 * @property string|null $zip
 * @property string|null $phone
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $state
 * @property string|null $country
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $is_billing
 *
 * @package App\Models
 */
class ShippingAddress extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'is_guest',
        'contact_person_name',
        'email',
        'address_type',
        // Canonical geography (Phase 5)
        'country_id',
        'state_id',
        'lga_id',
        // Text fields (kept for display/history)
        'address',
        'city',
        'zip',
        'phone',
        'state',
        'country',
        'latitude',
        'longitude',
        'is_billing',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_guest' => 'boolean',
        'is_billing' => 'boolean',
    ];

    /**
     * Canonical geography relationships (Phase 5)
     *
     * Customer address LGA = Destination for delivery routing (Origin LGA → Destination LGA)
     */
    public function canonicalCountry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function canonicalState(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function canonicalLga(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lga_id');
    }
}
