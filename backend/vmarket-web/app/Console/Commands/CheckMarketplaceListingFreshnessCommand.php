<?php

namespace App\Console\Commands;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Marketplace Availability Freshness Scheduler.
 *
 * This command is the ASYNCHRONOUS cleanup layer — NOT the primary security gate.
 * The runtime `isMarketplacePurchasable()` gate (and `scopeMarketplacePurchasable`)
 * already reject expired listings in real-time. This command:
 *   1. Finds seller listings whose `availability_expires_at` has passed.
 *   2. Marks `marketplace_listing_status = 'unlisted'` (removes from storefront).
 *   3. Sets `marketplace_availability = 'out_of_stock'` and `current_stock = 0` (legacy mirror).
 *   4. Sends vendor push notification requesting freshness confirmation.
 *
 * Schedule recommendation: every hour (`hourly`) in Kernel.php.
 */
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
    protected $description = '[AI] Audit marketplace listing freshness: expire stale seller listings and notify vendors to re-confirm availability';

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

        $now = Carbon::now();
        $this->info("[AI] Running marketplace freshness audit at {$now->toDateTimeString()}");

        // [AI] Canonical expiry gate: use availability_expires_at where available.
        // Fallback: derive from legacy marketplace_confirmed_at + confirmation window.
        $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
        $legacyExpiryThreshold = $now->copy()->subDays($confirmationDays);

        $expiredQuery = Product::where('added_by', 'seller')
            ->where('marketplace_listing_status', 'listed')
            ->where(function ($query) use ($now, $legacyExpiryThreshold) {
                // [AI] Canonical path: availability_expires_at is set and has passed
                $query->where(function ($q) use ($now) {
                    $q->whereNotNull('availability_expires_at')
                      ->where('availability_expires_at', '<=', $now);
                })
                // [AI] Legacy fallback path: availability_expires_at not set, use marketplace_confirmed_at window
                ->orWhere(function ($q) use ($legacyExpiryThreshold) {
                    $q->whereNull('availability_expires_at')
                      ->where(function ($q2) use ($legacyExpiryThreshold) {
                          $q2->whereNull('marketplace_confirmed_at')
                             ->orWhere('marketplace_confirmed_at', '<', $legacyExpiryThreshold);
                      });
                });
            });

        $unlistedCount = 0;
        $notificationPayloads = [];

        $expiredQuery->chunk(100, function ($products) use (&$unlistedCount, &$notificationPayloads, $now) {
            foreach ($products as $product) {
                // [AI] Mark unlisted + out_of_stock + clear expiry + set legacy mirror
                $product->marketplace_listing_status = 'unlisted';
                $product->marketplace_availability   = 'out_of_stock';
                $product->availability_expires_at    = null; // already expired; clear to avoid confusion
                $product->current_stock              = 0;    // legacy mirror
                $product->denied_note                = 'listing_expired';
                $product->save();

                $unlistedCount++;

                Log::info("[AI Marketplace Freshness] Auto-unlisted expired product #{$product->id} ('{$product->name}') for vendor #{$product->user_id}");

                // [AI] Queue vendor notification (batched for efficiency)
                $notificationPayloads[] = [
                    'vendor_id'    => $product->user_id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                ];
            }
        });

        // [AI] Send vendor push notifications for expired listings
        if (!empty($notificationPayloads)) {
            foreach ($notificationPayloads as $payload) {
                try {
                    $notificationMessage = translate('Availability_confirmation_required') . ': '
                        . translate('Please_confirm_that') . ' "' . $payload['product_name'] . '" '
                        . translate('is_still_available_on_Victorious_MARKET');

                    // [AI] Fire notification event for vendor — consumed by existing notification infrastructure
                    event(new \App\Events\NotificationEvent(
                        receiver_type: 'seller',
                        receiver_id: $payload['vendor_id'],
                        notification: (object)[
                            'key'     => 'marketplace_listing_expired',
                            'type'    => 'marketplace',
                            'message' => $notificationMessage,
                            'data'    => (object)['product_id' => $payload['product_id']],
                        ]
                    ));
                } catch (\Throwable $e) {
                    // [AI] Never let notification failures block the cleanup job
                    Log::warning("[AI Marketplace Freshness] Notification failed for vendor #{$payload['vendor_id']}: " . $e->getMessage());
                }
            }
        }

        if ($unlistedCount > 0) {
            cacheRemoveByType(type: 'products');
            $this->info("[AI] Successfully unlisted {$unlistedCount} expired marketplace product(s) and notified vendors.");
        } else {
            $this->info('[AI] No expired marketplace products found.');
        }

        return 0;
    }
}
