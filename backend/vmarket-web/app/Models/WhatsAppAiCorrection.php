<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppAiCorrection extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_ai_corrections';

    protected $fillable = [
        'customer_query',
        'ai_suggested_draft',
        'agent_final_reply',
        'agent_id',
        'is_approved_for_training',
    ];

    protected $casts = [
        'is_approved_for_training' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'agent_id');
    }
}
