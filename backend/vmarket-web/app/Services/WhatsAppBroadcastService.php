<?php

namespace App\Services;

use App\Jobs\ProcessBroadcastBatchJob;
use App\Models\User;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastLog;
use App\Models\WhatsAppCustomerAiProfile;
use Illuminate\Support\Facades\DB;

class WhatsAppBroadcastService
{
    /**
     * [AI] Creates and queues a targeted WhatsApp broadcast campaign.
     */
    public function createAndQueueBroadcast(array $data, int $adminId): WhatsAppBroadcast
    {
        return DB::transaction(function () use ($data, $adminId) {
            $filters = $data['filters'] ?? [];
            
            // 1. Resolve Target Audience
            $query = User::whereNotNull('phone')->where('is_active', 1);

            if (!empty($filters['min_ltv'])) {
                $query->whereHas('orders', function ($q) use ($filters) {
                    $q->where('order_status', 'delivered');
                }, '>=', 1);
            }

            if (!empty($filters['city'])) {
                $query->where('city', 'like', '%' . $filters['city'] . '%');
            }

            $recipients = $query->get(['id', 'phone', 'f_name', 'l_name']);

            // 2. Create Broadcast Record
            $broadcast = WhatsAppBroadcast::create([
                'title' => $data['title'],
                'template_name' => $data['template_name'],
                'template_parameters' => $data['template_parameters'] ?? [],
                'target_segment_filters' => $filters,
                'total_recipients' => $recipients->count(),
                'status' => !empty($data['scheduled_for']) ? 'scheduled' : 'processing',
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'started_at' => empty($data['scheduled_for']) ? now() : null,
                'created_by_admin_id' => $adminId,
            ]);

            // 3. Populate Broadcast Logs in Chunks
            $recipientChunks = $recipients->chunk(50);
            foreach ($recipientChunks as $chunk) {
                $logEntries = [];
                foreach ($chunk as $user) {
                    $logEntries[] = [
                        'broadcast_id' => $broadcast->id,
                        'phone' => $user->phone,
                        'customer_id' => $user->id,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                WhatsAppBroadcastLog::insert($logEntries);
            }

            // 4. Dispatch Async Queue Worker (if immediate)
            if (empty($data['scheduled_for'])) {
                dispatch(new ProcessBroadcastBatchJob($broadcast->id));
            }

            return $broadcast;
        });
    }
}
