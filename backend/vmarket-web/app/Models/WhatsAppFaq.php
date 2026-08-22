<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppFaq extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_faqs';

    protected $fillable = [
        'category',
        'question',
        'answer',
        'keywords',
        'is_active',
        'times_used',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
        'times_used' => 'integer',
    ];
}
