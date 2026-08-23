<?php

namespace App\Services;

use App\Models\WhatsAppCustomerAiProfile;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Support\Facades\Log;

class EpisodicMemoryService
{
    /**
     * [AI] Appends a long-term episodic memory point strictly isolated to this WhatsApp phone number.
     */
    public static function addMemoryPoint(string $phone, string $fact, string $category = 'general'): bool
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);

        try {
            $profile = WhatsAppCustomerAiProfile::firstOrCreate(
                ['phone' => $normalizedPhone],
                [
                    'preferred_tone' => 'pidgin_friendly',
                    'loyalty_tier' => 'Bronze',
                ]
            );

            $memories = $profile->episodic_memory ?? [];
            
            // Avoid duplicate facts
            foreach ($memories as $m) {
                if (strtolower(trim($m['fact'] ?? '')) === strtolower(trim($fact))) {
                    return true;
                }
            }

            $memories[] = [
                'fact' => trim($fact),
                'category' => $category,
                'recorded_at' => now()->toIso8601String(),
            ];

            // Bound memory points to last 20 most relevant items to prevent prompt bloat
            if (count($memories) > 20) {
                $memories = array_slice($memories, -20);
            }

            $profile->update(['episodic_memory' => $memories]);
            return true;

        } catch (Exception $e) {
            Log::error('[EpisodicMemoryService addMemoryPoint Error] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * [AI] Retrieves the full episodic memory points for this specific phone number.
     */
    public static function getMemories(string $phone): array
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        $profile = WhatsAppCustomerAiProfile::where('phone', $normalizedPhone)->first();
        return $profile?->episodic_memory ?? [];
    }

    /**
     * [AI] Clears episodic memory for this specific phone number.
     */
    public static function clearMemories(string $phone): bool
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        $profile = WhatsAppCustomerAiProfile::where('phone', $normalizedPhone)->first();
        if ($profile) {
            $profile->update(['episodic_memory' => []]);
        }
        return true;
    }

    /**
     * [AI] Records human agent activity on this thread.
     */
    public static function recordHumanInteraction(string $phone, string $agentName): void
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        WhatsAppCustomerAiProfile::updateOrCreate(
            ['phone' => $normalizedPhone],
            [
                'last_human_agent_name' => $agentName,
                'last_human_interaction_at' => now(),
                'unanswered_customer_since' => null,
            ]
        );
    }

    /**
     * [AI] Records when a customer sent a message awaiting response.
     */
    public static function recordCustomerMessageWaiting(string $phone): void
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        $profile = WhatsAppCustomerAiProfile::firstOrCreate(
            ['phone' => $normalizedPhone],
            ['preferred_tone' => 'pidgin_friendly']
        );

        if (empty($profile->unanswered_customer_since)) {
            $profile->update(['unanswered_customer_since' => now()]);
        }
    }

    /**
     * [AI] Clears the unanswered waiting state once a reply is sent.
     */
    public static function clearCustomerMessageWaiting(string $phone): void
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        WhatsAppCustomerAiProfile::where('phone', $normalizedPhone)->update([
            'unanswered_customer_since' => null,
        ]);
    }
}
