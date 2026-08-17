<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryState extends Model
{
    use HasFactory;

    protected $table = 'delivery_states';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(DeliveryCity::class, 'state_id');
    }

    public function activeCities(): HasMany
    {
        return $this->hasMany(DeliveryCity::class, 'state_id')->where('is_active', true);
    }
}
