<?php

namespace App\Console\Commands;

use App\Services\VendorAiReportService;
use Illuminate\Console\Command;

class SendVendorAiPerformanceReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendor:send-ai-reports {type=daily : Report frequency (daily, weekly, monthly)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[AI] Automatically generate and send WhatsApp Business Intelligence reports to Pro Subscribed Vendors';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = strtolower($this->argument('type'));
        if (!in_array($type, ['daily', 'weekly', 'monthly'])) {
            $this->error("Invalid report type '{$type}'. Use 'daily', 'weekly', or 'monthly'.");
            return 1;
        }

        $this->info("🚀 Generating and dispatching {$type} AI Business Intelligence reports to Pro Subscribed Vendors...");

        $sentCount = VendorAiReportService::sendAllSubscribedReports($type);

        $this->info("✅ Successfully generated and dispatched {$sentCount} {$type} AI report(s).");
        return 0;
    }
}
