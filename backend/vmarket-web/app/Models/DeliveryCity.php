<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryCity extends Model
{
    use HasFactory;

    protected $table = 'delivery_cities';

    protected $fillable = [
        'state_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'state_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(DeliveryState::class, 'state_id');
    }

    public function hubs(): HasMany
    {
        return $this->hasMany(DeliveryHub::class, 'city_id');
    }

    public function landmarks(): HasMany
    {
        return $this->hasMany(DeliveryHub::class, 'city_id')->where('type', 'landmark')->where('is_active', 1);
    }

    public function motorParks(): HasMany
    {
        return $this->hasMany(DeliveryHub::class, 'city_id')->where('type', 'motor_park')->where('is_active', 1);
    }
}
