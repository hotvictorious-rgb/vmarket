<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppBroadcast extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcasts';

    protected $fillable = [
        'title',
        'template_name',
        'template_parameters',
        'target_segment_filters',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'read_count',
        'replied_count',
        'status',
        'scheduled_for',
        'started_at',
        'completed_at',
        'created_by_admin_id',
    ];

    protected $casts = [
        'template_parameters' => 'array',
        'target_segment_filters' => 'array',
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsAppBroadcastLog::class, 'broadcast_id');
    }
}
