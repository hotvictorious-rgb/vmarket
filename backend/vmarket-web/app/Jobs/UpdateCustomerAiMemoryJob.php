<?php

namespace App\Jobs;

use App\Models\WhatsAppCustomerAiProfile;
use App\Models\WhatsAppMessage;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateCustomerAiMemoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        protected int $conversationId,
        protected string $phone
    ) {}

    public function handle(): void
    {
        $apiKey = env('GEMINI_API_KEY', '');
        if (empty($apiKey)) return;

        try {
            $formattedPhone = SMSModule::formatNigerianPhone($this->phone);

            // 1. Fetch recent messages
            $messages = WhatsAppMessage::where('conversation_id', $this->conversationId)
                ->latest()
                ->take(15)
                ->get()
                ->reverse();

            if ($messages->count() < 2) return;

            $transcript = $messages->map(fn($m) => "{$m->sender_type}: {$m->message_body}")->join("\n");

            // 2. Synthesize memory extraction prompt
            $prompt = <<<PROMPT
You are a customer intelligence extractor for an e-commerce platform.
Read the following chat transcript between customer and agent/bot:
----------------------------------
{$transcript}
----------------------------------
Extract personal customer shopping preferences. Return strictly valid JSON without markdown fences, matching this schema:
{
  "preferred_name": "string or null",
  "preferred_tone": "pidgin_friendly" | "formal_english" | "casual_english",
  "size_preferences": { "shoes": "...", "clothing": "..." } or null,
  "favorite_categories": ["..."] or null,
  "favorite_colors": ["..."] or null,
  "favorite_delivery_landmark": "string or null",
  "new_memory_fact": "A concise 1-sentence note about the customer or null"
}
PROMPT;

            $response = Http::timeout(15)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json']
            ]);

            if ($response->successful()) {
                $rawJson = $response->json('candidates.0.content.parts.0.text') ?? '{}';
                $extracted = json_decode($rawJson, true);

                if (is_array($extracted)) {
                    $profile = WhatsAppCustomerAiProfile::firstOrNew(['phone' => $formattedPhone]);

                    if (!empty($extracted['preferred_name']) && $extracted['preferred_name'] !== 'null') {
                        $profile->preferred_name = $extracted['preferred_name'];
                    }
                    if (!empty($extracted['preferred_tone'])) {
                        $profile->preferred_tone = $extracted['preferred_tone'];
                    }
                    if (!empty($extracted['size_preferences']) && is_array($extracted['size_preferences'])) {
                        $profile->size_preferences = array_merge($profile->size_preferences ?? [], array_filter($extracted['size_preferences']));
                    }
                    if (!empty($extracted['favorite_categories']) && is_array($extracted['favorite_categories'])) {
                        $profile->favorite_categories = array_values(array_unique(array_merge($profile->favorite_categories ?? [], $extracted['favorite_categories'])));
                    }
                    if (!empty($extracted['favorite_colors']) && is_array($extracted['favorite_colors'])) {
                        $profile->favorite_colors = array_values(array_unique(array_merge($profile->favorite_colors ?? [], $extracted['favorite_colors'])));
                    }
                    if (!empty($extracted['favorite_delivery_landmark']) && $extracted['favorite_delivery_landmark'] !== 'null') {
                        $profile->favorite_delivery_landmark = $extracted['favorite_delivery_landmark'];
                    }
                    if (!empty($extracted['new_memory_fact']) && $extracted['new_memory_fact'] !== 'null') {
                        $notes = $profile->interaction_memory_notes ?? [];
                        $notes[] = [
                            'date' => now()->toDateString(),
                            'fact' => $extracted['new_memory_fact']
                        ];
                        $profile->interaction_memory_notes = array_slice($notes, -10); // Retain top 10 most recent facts
                    }

                    $profile->last_interaction_at = now();
                    $profile->save();
                }
            }
        } catch (Exception $e) {
            Log::error('[AI Memory Extraction Exception] ' . $e->getMessage());
        }
    }
}
