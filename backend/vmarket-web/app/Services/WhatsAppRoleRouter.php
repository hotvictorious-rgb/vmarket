<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\Seller;
use App\Models\User;
use App\Utils\SMSModule;

class WhatsAppRoleRouter
{
    /**
     * [AI] Resolve all active roles associated with a verified phone number.
     */
    public static function resolveRoles(string $phone): array
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        $localPhone = '0' . substr($normalizedPhone, 3);
        $plusPhone = '+' . $normalizedPhone;

        $customer = User::where('phone', $normalizedPhone)
            ->orWhere('phone', $localPhone)
            ->orWhere('phone', $plusPhone)
            ->first();

        $seller = Seller::where('phone', $normalizedPhone)
            ->orWhere('phone', $localPhone)
            ->orWhere('phone', $plusPhone)
            ->with(['shop'])
            ->first();

        $deliveryMan = DeliveryMan::where('phone', $normalizedPhone)
            ->orWhere('phone', $localPhone)
            ->orWhere('phone', $plusPhone)
            ->first();

        $roles = [];
        if ($customer) $roles[] = 'customer';
        if ($seller && $seller->status === 'approved') $roles[] = 'vendor';
        if ($deliveryMan && $deliveryMan->is_active == 1) $roles[] = 'rider';

        return [
            'phone' => $normalizedPhone,
            'roles' => $roles,
            'customer' => $customer,
            'seller' => $seller,
            'delivery_man' => $deliveryMan,
            'is_multi_role' => count($roles) > 1,
        ];
    }
}
