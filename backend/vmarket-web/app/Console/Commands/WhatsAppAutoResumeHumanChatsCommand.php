<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppCustomerAiProfile;
use App\Models\WhatsAppMessage;
use App\Services\EpisodicMemoryService;
use App\Services\WhatsAppAiService;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsAppAutoResumeHumanChatsCommand extends Command
{
    protected $signature = 'whatsapp:auto-resume-human-chats {--minutes=5}';
    protected $description = '[AI] Automatically resumes human WhatsApp chats where the agent has been inactive for > 5 minutes';

    public function handle(WhatsAppAiService $aiService): int
    {
        $minutes = (int)$this->option('minutes') ?: 5;
        $cutoffTime = now()->subMinutes($minutes);

        // Find conversations assigned to human agents where the last message was sent by the customer and is older than cutoff
        $conversations = WhatsAppConversation::whereNotNull('assigned_agent_id')
            ->where('status', '!=', 'resolved')
            ->with(['latestMessage', 'customer'])
            ->get();

        $resumedCount = 0;

        foreach ($conversations as $conv) {
            $latestMsg = $conv->latestMessage;

            // Only resume if the customer spoke last and human hasn't responded in > $minutes
            if ($latestMsg && $latestMsg->sender_type === 'customer' && $latestMsg->created_at <= $cutoffTime) {
                $normalizedPhone = SMSModule::formatNigerianPhone($conv->phone);
                
                // Check if auto_resume_enabled for this customer
                $profile = WhatsAppCustomerAiProfile::where('phone', $normalizedPhone)->first();
                if ($profile && $profile->auto_resume_enabled === false) {
                    continue;
                }

                $this->info("Resuming inactive chat for phone: {$conv->phone} (Last spoke: {$latestMsg->created_at->diffForHumans()})");

                try {
                    // 1. Fetch recent chat history
                    $recentHistory = WhatsAppMessage::where('conversation_id', $conv->id)
                        ->orderBy('id', 'desc')
                        ->take(15)
                        ->get()
                        ->reverse()
                        ->toArray();

                    // 2. Generate natural AI continuation
                    $aiResponse = $aiService->resumeHumanChat($conv->phone, $recentHistory);

                    if (!empty($aiResponse['reply'])) {
                        // 3. Dispatch reply to customer
                        dispatch(new SendWhatsAppJob(
                            toPhone: $conv->phone,
                            messageText: $aiResponse['reply'],
                            conversationId: $conv->id,
                            isBotReply: true
                        ));

                        // 4. Re-assign conversation to bot handling
                        $conv->update([
                            'status' => 'bot_handling',
                            'assigned_agent_id' => null,
                            'last_message_at' => now(),
                        ]);

                        EpisodicMemoryService::clearCustomerMessageWaiting($conv->phone);
                        $resumedCount++;
                    }

                } catch (Exception $e) {
                    Log::error("[WhatsAppAutoResumeCommand Error] Phone {$conv->phone}: " . $e->getMessage());
                }
            }
        }

        $this->info("Completed auto-resume worker. Resumed {$resumedCount} inactive conversations.");
        return 0;
    }
}
