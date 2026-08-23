<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlacklistedCustomer extends Model
{
    protected $table = 'blacklisted_customers';

    protected $fillable = [
        'user_id',
        'phone',
        'email',
        'ip_address',
        'reason',
        'banned_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'banned_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'banned_by');
    }

    /**
     * [AI] Helper to check if a phone, email, or IP is currently blacklisted.
     */
    public static function isBlacklisted(?string $phone = null, ?string $email = null, ?string $ip = null): bool
    {
        $query = self::query();

        if ($phone) {
            $formatted = \App\Utils\SMSModule::formatNigerianPhone($phone);
            $query->where(function ($q) use ($formatted, $phone) {
                $q->where('phone', $formatted)
                  ->orWhere('phone', $phone)
                  ->orWhere('phone', '0' . substr($formatted, 3));
            });
        }

        if ($email) {
            $query->orWhere('email', $email);
        }

        if ($ip) {
            $query->orWhere('ip_address', $ip);
        }

        return $query->exists();
    }
}
