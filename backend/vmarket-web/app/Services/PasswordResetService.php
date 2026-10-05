<?php
namespace App\Services;
class PasswordResetService
{
    public function getAddData(string|int $identity, string $token, string $userType, string $purpose = 'password_reset'): array
    {
        $class = match ($userType) {
            'seller' => \App\Models\Seller::class,
            'customer' => \App\Models\User::class,
            default => throw new \InvalidArgumentException('Unsupported reset actor.'),
        };
        if (!in_array($purpose, ['password_reset', 'firebase_pending'], true)) {
            throw new \InvalidArgumentException('Unsupported reset purpose.');
        }
        $accounts = $class::where(fn ($query) => $query->where('phone', $identity)->orWhere('email', $identity))->get();
        // [AI] Customer creation issues invitation metadata before the account exists. It must never authorize a reset.
        if ($accounts->isEmpty() && $userType === 'customer') {
            return ['identity' => $identity, 'token' => hash('sha256', $token), 'user_type' => $userType,
                'account_id' => null, 'purpose' => 'registration_invite', 'created_at' => now(), 'updated_at' => now()];
        }
        if ($accounts->count() !== 1) {
            throw new \RuntimeException('Ambiguous reset account.');
        }
        // [AI] Only an explicitly designated provider session may remain raw; it cannot authorize a password reset.
        return ['identity' => $identity, 'token' => $purpose === 'firebase_pending' ? $token : hash('sha256', $token),
            'user_type' => $userType, 'account_id' => $accounts->first()->id, 'purpose' => $purpose,
            'created_at' => now(), 'updated_at' => now()];
    }
}
