<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsAppCustomerAiProfile;
use Illuminate\Support\Facades\DB;

class CustomerAiRelationshipEngine
{
    /**
     * [AI] Assembles the complete Past, Present, Future Dossier for an individual customer.
     */
    public static function buildCustomerDossier(string $phone): array
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);

        // 1. Fetch User Record
        $user = User::where('phone', $normalizedPhone)
            ->orWhere('phone', '0' . substr($normalizedPhone, 3))
            ->orWhere('phone', '+' . $normalizedPhone)
            ->first();

        // 2. Fetch or Init Customer AI Memory Profile
        $profile = WhatsAppCustomerAiProfile::firstOrCreate(
            ['phone' => $normalizedPhone],
            [
                'customer_id' => $user?->id,
                'preferred_name' => $user ? trim($user->f_name . ' ' . $user->l_name) : null,
                'preferred_tone' => 'pidgin_friendly',
                'loyalty_tier' => 'Bronze',
            ]
        );

        // 3. PAST Context (Orders History, LTV, Preferences)
        $pastOrders = [];
        $totalSpend = 0.0;
        $totalOrdersCount = 0;

        if ($user) {
            $ordersQuery = Order::where('customer_id', $user->id)->orderBy('id', 'desc')->take(5)->get();
            $totalOrdersCount = Order::where('customer_id', $user->id)->count();
            $totalSpend = (float) Order::where('customer_id', $user->id)->where('order_status', 'delivered')->sum('order_amount');

            foreach ($ordersQuery as $ord) {
                $pastOrders[] = WhatsAppCustomerTransformer::sanitizeOrderForChat($ord);
            }
        }

        // 4. PRESENT Context (Active Deliveries, Cart items, Wallet)
        $activeOrders = [];
        $cartItems = [];

        if ($user) {
            $activeQuery = Order::where('customer_id', $user->id)
                ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery'])
                ->orderBy('id', 'desc')
                ->get();

            foreach ($activeQuery as $active) {
                $activeOrders[] = WhatsAppCustomerTransformer::sanitizeOrderForChat($active);
            }

            $userCarts = Cart::where('customer_id', $user->id)->get();
            foreach ($userCarts as $c) {
                $product = $c->product;
                if ($product) {
                    $cartItems[] = [
                        'name' => $product->name,
                        'qty' => $c->quantity,
                        'price' => (float) $c->price,
                        'variant' => $c->variant,
                    ];
                }
            }
        }

        // 5. FUTURE Context (Predicted Re-orders, Milestone, Loyalty)
        $loyaltyTier = 'Bronze Shopper';
        if ($totalSpend >= 200000) {
            $loyaltyTier = 'Crown VIP (High Value)';
        } elseif ($totalSpend >= 75000) {
            $loyaltyTier = 'Gold Member (Repeat Loyal)';
        } elseif ($totalSpend >= 25000) {
            $loyaltyTier = 'Silver Shopper';
        }

        // 6. Web Support Tickets (Omnichannel Support Integration)
        $supportTickets = [];
        if ($user) {
            $tickets = \App\Models\SupportTicket::where('customer_id', $user->id)->orderBy('id', 'desc')->take(5)->get();
            foreach ($tickets as $t) {
                $supportTickets[] = [
                    'id' => $t->id,
                    'subject' => $t->subject,
                    'type' => $t->type,
                    'priority' => $t->priority,
                    'status' => $t->status,
                    'created_at' => $t->created_at ? $t->created_at->format('d M Y, h:i A') : '',
                ];
            }
        }

        return [
            'phone' => $normalizedPhone,
            'user_id' => $user?->id,
            'name' => $profile->preferred_name ?: ($user ? trim($user->f_name . ' ' . $user->l_name) : 'Valued Shopper'),
            'loyalty_tier' => $loyaltyTier,
            'total_spend' => $totalSpend,
            'total_orders' => $totalOrdersCount,
            'wallet_balance' => (float) ($user?->wallet_balance ?? 0.0),
            
            // Memory Graphs
            'preferred_tone' => $profile->preferred_tone ?? 'pidgin_friendly',
            'size_preferences' => $profile->size_preferences ?? [],
            'favorite_categories' => $profile->favorite_categories ?? [],
            'favorite_colors' => $profile->favorite_colors ?? [],
            'delivery_landmark' => $profile->favorite_delivery_landmark ?? ($user?->street_address ?? 'Uyo, Akwa Ibom'),
            'interaction_memory_notes' => $profile->interaction_memory_notes ?? [],
            'episodic_memory' => $profile->episodic_memory ?? [],
            'last_human_agent_name' => $profile->last_human_agent_name ?? null,
            'last_human_interaction_at' => $profile->last_human_interaction_at?->diffForHumans() ?? null,
            'unanswered_customer_since' => $profile->unanswered_customer_since?->diffForHumans() ?? null,
            'auto_resume_enabled' => (bool) ($profile->auto_resume_enabled ?? true),
            
            // Live State
            'active_orders' => $activeOrders,
            'cart_items' => $cartItems,
            'recent_orders' => $pastOrders,
            'support_tickets' => $supportTickets,
        ];
    }
}
