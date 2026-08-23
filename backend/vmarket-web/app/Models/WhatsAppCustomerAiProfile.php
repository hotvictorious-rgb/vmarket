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
        'episodic_memory',
        'last_human_agent_name',
        'last_human_interaction_at',
        'unanswered_customer_since',
        'auto_resume_enabled',
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
        'episodic_memory' => 'array',
        'predicted_reorder_categories' => 'array',
        'auto_resume_enabled' => 'boolean',
        'last_human_interaction_at' => 'datetime',
        'unanswered_customer_since' => 'datetime',
        'total_spend_amount' => 'decimal:2',
        'last_interaction_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
