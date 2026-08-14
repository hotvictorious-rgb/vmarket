<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $chat_id          Foreign key to Chat
 * @property string $type           Type: 'media' or 'file'
 * @property string $path           Storage path of file/media
 * @property string $name           Original filename
 * @property string $size           File size
 * @property string $key            S3 or storage key
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class ChatAttachment extends Model
{
    protected $table = 'chat_attachments';

    protected $fillable = [
        'chat_id',
        'type',
        'path',
        'name',
        'size',
        'key',
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'type' => 'string',
        'path' => 'string',
        'name' => 'string',
        'size' => 'string',
        'key' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Chat relationship
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * Scope: Media files only
     */
    public function scopeMedia($query)
    {
        return $query->where('type', 'media');
    }

    /**
     * Scope: Document files only
     */
    public function scopeFiles($query)
    {
        return $query->where('type', 'file');
    }
}
