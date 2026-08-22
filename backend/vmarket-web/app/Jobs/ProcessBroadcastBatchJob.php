<?php

namespace App\Jobs;

use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastLog;
use App\Services\WhatsAppCrmService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBroadcastBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(protected int $broadcastId) {}

    public function handle(WhatsAppCrmService $crmService): void
    {
        $broadcast = WhatsAppBroadcast::find($this->broadcastId);
        if (!$broadcast || $broadcast->status === 'canceled') return;

        $broadcast->update(['status' => 'processing']);

        $pendingLogs = WhatsAppBroadcastLog::where('broadcast_id', $this->broadcastId)
            ->where('status', 'pending')
            ->take(50)
            ->get();

        if ($pendingLogs->isEmpty()) {
            $broadcast->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            return;
        }

        $sentInBatch = 0;

        foreach ($pendingLogs as $log) {
            try {
                $components = $broadcast->template_parameters ?? [];
                
                $result = $crmService->sendTemplateMessage(
                    $log->phone,
                    $broadcast->template_name,
                    'en',
                    $components
                );

                if ($result['success']) {
                    $log->update([
                        'status' => 'sent',
                        'meta_message_id' => $result['meta_message_id'] ?? null,
                    ]);
                    $sentInBatch++;
                } else {
                    $log->update([
                        'status' => 'failed',
                        'failure_reason' => json_encode($result['error'] ?? null),
                    ]);
                }

                // Rate-limiting delay: 50ms (approx 20 msgs/sec for Meta safety)
                usleep(50000);
            } catch (Exception $e) {
                $log->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }
        }

        // Increment sent counter on broadcast
        $broadcast->increment('sent_count', $sentInBatch);

        // Check if more pending logs remain
        $hasMore = WhatsAppBroadcastLog::where('broadcast_id', $this->broadcastId)
            ->where('status', 'pending')
            ->exists();

        if ($hasMore) {
            dispatch(new ProcessBroadcastBatchJob($this->broadcastId))->delay(now()->addSeconds(2));
        } else {
            $broadcast->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
