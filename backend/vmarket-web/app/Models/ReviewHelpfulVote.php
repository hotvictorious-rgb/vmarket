<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Class ReviewHelpfulVote
 *
 * Tracks individual customer and IP upvotes on verified reviews to prevent multi-voting spam.
 */
class ReviewHelpfulVote extends Model
{
    protected $fillable = [
        'review_id',
        'customer_id',
        'ip_address',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
