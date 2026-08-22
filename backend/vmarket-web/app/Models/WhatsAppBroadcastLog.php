<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBroadcastLog extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcast_logs';

    protected $fillable = [
        'broadcast_id',
        'phone',
        'customer_id',
        'meta_message_id',
        'status',
        'failure_reason',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBroadcast::class, 'broadcast_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
