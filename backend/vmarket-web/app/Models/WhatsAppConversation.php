<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversation extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'phone',
        'customer_id',
        'assigned_agent_id',
        'assigned_department',
        'status',
        'priority',
        'customer_name',
        'tags',
        'internal_notes',
        'last_message_at',
        'unread_agent_count',
        'unread_customer_count',
        'locked_until',
        'locked_by_agent_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'last_message_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_agent_id');
    }

    public function lockedByAgent(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'locked_by_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }

    public function latestMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'id', 'conversation_id')->latestOfMany();
    }

    public function aiProfile(): BelongsTo
    {
        return $this->belongsTo(WhatsAppCustomerAiProfile::class, 'phone', 'phone');
    }
}
