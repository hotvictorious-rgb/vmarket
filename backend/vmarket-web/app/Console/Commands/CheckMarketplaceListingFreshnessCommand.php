<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Seller;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMarketplaceListingFreshnessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:check-marketplace-freshness {--force : Force execution ignoring configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit marketplace product freshness and auto-unlist seller products whose confirmation has expired beyond the configured window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $autoUnlist = function_exists('isMarketplaceAutoUnlistEnabled') ? isMarketplaceAutoUnlistEnabled() : true;
        if (!$autoUnlist && !$this->option('force')) {
            $this->info('Marketplace auto-unlisting of expired products is currently disabled in business settings.');
            return 0;
        }

        $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
        $expiryThreshold = Carbon::now()->subDays($confirmationDays);

        $this->info("Running marketplace freshness audit: Confirmation window = {$confirmationDays} days (Threshold: {$expiryThreshold->toDateTimeString()})");

        $expiredQuery = Product::where('added_by', 'seller')
            ->where('marketplace_listing_status', 'listed')
            ->where(function ($query) use ($expiryThreshold) {
                $query->whereNull('marketplace_confirmed_at')
                      ->orWhere('marketplace_confirmed_at', '<', $expiryThreshold);
            });

        $unlistedCount = 0;

        $expiredQuery->chunk(100, function ($products) use (&$unlistedCount) {
            foreach ($products as $product) {
                $product->marketplace_listing_status = 'unlisted';
                $product->denied_note = 'listing_expired';
                $product->save();

                $unlistedCount++;

                Log::info("[AI Marketplace Freshness] Auto-unlisted expired product #{$product->id} ('{$product->name}') for vendor #{$product->user_id}");
            }
        });

        if ($unlistedCount > 0) {
            cacheRemoveByType(type: 'products');
            $this->info("Successfully unlisted {$unlistedCount} expired marketplace product(s).");
        } else {
            $this->info("No expired marketplace products found.");
        }

        return 0;
    }
}
