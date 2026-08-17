<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Seller;
use App\Utils\Helpers;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckProductPriceExpiryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:check-price-expiry {--force : Force check ignoring configuration status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and auto-deactivate products whose prices have not been updated within the configured expiry period (e.g. 30 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = getWebConfig('product_price_expiry_config');
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        $isEnabled = $this->option('force') || ($config['status'] ?? 1) == 1;
        if (!$isEnabled) {
            $this->info('Product price auto-expiry is currently disabled in business settings.');
            return 0;
        }

        $expiryDays = (int)($config['expiry_days'] ?? 30);
        $warningDays = (int)($config['warning_days'] ?? 25);
        $scope = $config['scope'] ?? 'all'; // 'all', 'vendor', 'inhouse'

        $this->info("Running price expiry check: Expiry = {$expiryDays} days, Warning = {$warningDays} days, Scope = {$scope}");

        $expiryThreshold = Carbon::now()->subDays($expiryDays);
        $warningThreshold = Carbon::now()->subDays($warningDays);

        // 1. Process Warnings (At 25 days)
        $warningQuery = Product::where('status', 1)
            ->whereNull('price_expiry_notified_at')
            ->where(function ($query) use ($warningThreshold, $expiryThreshold) {
                $query->where('price_updated_at', '<=', $warningThreshold)
                      ->where('price_updated_at', '>', $expiryThreshold);
            });

        if ($scope === 'vendor') {
            $warningQuery->where('added_by', 'seller');
        } elseif ($scope === 'inhouse') {
            $warningQuery->where('added_by', 'admin');
        }

        $warningCount = 0;
        $warningQuery->chunk(100, function ($products) use (&$warningCount, $expiryDays, $warningDays) {
            $daysLeft = max(1, $expiryDays - $warningDays);
            foreach ($products as $product) {
                $product->update([
                    'price_expiry_notified_at' => Carbon::now(),
                ]);

                $warningCount++;

                // Notify Vendor if added by seller
                if ($product->added_by === 'seller' && $product->user_id) {
                    $seller = Seller::find($product->user_id);
                    if ($seller && $seller->cm_firebase_token) {
                        Helpers::send_push_notif_to_device(
                            $seller->cm_firebase_token,
                            [
                                'title' => 'Price Update Reminder - Action Needed',
                                'description' => "The price for '{$product->name}' will expire in {$daysLeft} days. Please review and update your price to keep it active.",
                                'image' => '',
                                'type' => 'product_price_warning',
                                'product_id' => $product->id,
                            ]
                        );
                    }
                }
            }
        });

        $this->info("Sent {$warningCount} price expiry warning notifications.");

        // 2. Process Expirations (At 30 days)
        $expiryQuery = Product::where('status', 1)
            ->where('price_updated_at', '<=', $expiryThreshold);

        if ($scope === 'vendor') {
            $expiryQuery->where('added_by', 'seller');
        } elseif ($scope === 'inhouse') {
            $expiryQuery->where('added_by', 'admin');
        }

        $expiredCount = 0;
        $expiryQuery->chunk(100, function ($products) use (&$expiredCount) {
            foreach ($products as $product) {
                $product->update([
                    'status' => 0,
                    'deactivation_reason' => 'price_expired',
                ]);

                $expiredCount++;

                // Notify Vendor of deactivation
                if ($product->added_by === 'seller' && $product->user_id) {
                    $seller = Seller::find($product->user_id);
                    if ($seller && $seller->cm_firebase_token) {
                        Helpers::send_push_notif_to_device(
                            $seller->cm_firebase_token,
                            [
                                'title' => 'Product Deactivated - Price Expired',
                                'description' => "Product '{$product->name}' was automatically deactivated because its price hasn't been updated in 30 days. Update the price to reactivate it.",
                                'image' => '',
                                'type' => 'product_price_expired',
                                'product_id' => $product->id,
                            ]
                        );
                    }
                }
            }
        });

        if ($expiredCount > 0) {
            clearWebConfigCacheKeys();
        }

        $this->info("Successfully deactivated {$expiredCount} expired products and invalidated storefront caches.");
        return 0;
    }
}
