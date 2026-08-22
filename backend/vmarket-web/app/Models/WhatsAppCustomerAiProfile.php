<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppCustomerAiProfile extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_customer_ai_profiles';

    protected $fillable = [
        'phone',
        'customer_id',
        'preferred_name',
        'preferred_tone',
        'size_preferences',
        'favorite_categories',
        'favorite_colors',
        'favorite_delivery_landmark',
        'preferred_payment_method',
        'price_sensitivity',
        'interaction_memory_notes',
        'predicted_reorder_categories',
        'loyalty_tier',
        'total_orders_count',
        'total_spend_amount',
        'last_interaction_at',
    ];

    protected $casts = [
        'size_preferences' => 'array',
        'favorite_categories' => 'array',
        'favorite_colors' => 'array',
        'interaction_memory_notes' => 'array',
        'predicted_reorder_categories' => 'array',
        'total_spend_amount' => 'decimal:2',
        'last_interaction_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
