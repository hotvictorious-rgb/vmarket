<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'conversation_id',
        'meta_message_id',
        'sender_type',
        'sender_id',
        'message_type',
        'message_body',
        'media_url',
        'caption',
        'delivery_status',
        'error_reason',
        'is_ai_draft',
    ];

    protected $casts = [
        'is_ai_draft' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function senderAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sender_id');
    }
}
