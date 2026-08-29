<?php

namespace App\Providers;

use App\Models\BusinessPage;
use App\Models\BusinessSetting;
use App\Models\LoginSetup;
use App\Models\StockClearanceProduct;
use App\Traits\CacheManagerTrait;
use App\Traits\FileManagerTrait;
use App\Traits\UpdateClass;
use App\Utils\Helpers;
use App\Enums\GlobalConstant;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\SocialMedia;
use App\Models\Product;
use App\Traits\AddonHelper;
use App\Traits\ThemeHelper;
use App\Utils\ProductManager;
use Exception;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

ini_set('memory_limit', -1);
ini_set('upload_max_filesize', '180M');
ini_set('post_max_size', '200M');

class AppServiceProvider extends ServiceProvider
{
    use AddonHelper;
    use CacheManagerTrait;
    use FileManagerTrait;
    use ThemeHelper;
    use UpdateClass;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Helper', \App\Utils\Helpers::class);
        $loader->alias('Madzipper', \Madnest\Madzipper\Madzipper::class);
        $loader->alias('Excel', \Maatwebsite\Excel\Facades\Excel::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */

    public function boot(): void
    {
        // [AI] Register SQLite Compatibility Functions for MySQL Date Functions
        try {
            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
                $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
                if ($pdo instanceof \PDO) {
                    $pdo->sqliteCreateFunction('YEAR', fn($d) => $d ? (int)date('Y', strtotime($d)) : null, 1);
                    $pdo->sqliteCreateFunction('MONTH', fn($d) => $d ? (int)date('n', strtotime($d)) : null, 1);
                    $pdo->sqliteCreateFunction('DAY', fn($d) => $d ? (int)date('j', strtotime($d)) : null, 1);
                    $pdo->sqliteCreateFunction('DAYNAME', fn($d) => $d ? date('l', strtotime($d)) : null, 1);
                    $pdo->sqliteCreateFunction('IFNULL', fn($v, $def) => $v !== null ? $v : $def, 2);
                    $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'), 0);
                    $pdo->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'), 0);
                    $pdo->sqliteCreateFunction('DATEDIFF', fn($d1, $d2) => (int)round((strtotime($d1) - strtotime($d2)) / 86400), 2);
                }
            }
        } catch (\Throwable $e) {}

        if (!in_array(request()->ip(), ['127.0.0.1', '::1']) && env('FORCE_HTTPS')) {
            \URL::forceScheme('https');
        }
        if (!App::runningInConsole() || defined('LARAVEL_TEST_RUN') || request()) {
            Paginator::useBootstrap();

            Config::set('addon_admin_routes', $this->getAddonAdminRoutes());
            Config::set('get_payment_publish_status', $this->getPaymentPublishStatus());
            Config::set('get_theme_routes', $this->getThemeRoutesArray());

            try {
                if (Schema::hasTable('business_settings')) {
                    $this->setStorageConnectionEnvironment();
                    $this->cacheInHouseShopInTemporaryStatus();

                    $web = $this->cacheBusinessSettingsTable();

                    $firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification');
                    $firebaseOTPVerificationStatus = (int)(is_array($firebaseOTPVerification) && !empty($firebaseOTPVerification['status']) && !empty($firebaseOTPVerification['web_api_key']));

                    $systemColors = getWebConfig('colors');
                    $web_config = [
                        'primary_color' => (is_array($systemColors) ? ($systemColors['primary'] ?? '#5E17EB') : '#5E17EB'),
                        'secondary_color' => (is_array($systemColors) ? ($systemColors['secondary'] ?? '#FFD700') : '#FFD700'),
                        'primary_color_light' => (is_array($systemColors) ? ($systemColors['primary_light'] ?? '#7B39FD') : '#7B39FD'),
                        'panel_sidebar_color' => (is_array($systemColors) ? ($systemColors['panel-sidebar'] ?? '#5E17EB') : '#5E17EB'),
                        'name' => Helpers::get_settings($web, 'company_name'),
                        'company_name' => getWebConfig(name: 'company_name'),
                        'phone' => getWebConfig(name: 'company_phone'),
                        'web_logo' => getWebConfig(name: 'company_web_logo'),
                        'mob_logo' => getWebConfig(name: 'company_mobile_logo'),
                        'fav_icon' => getWebConfig(name: 'company_fav_icon'),
                        'email' => getWebConfig(name: 'company_email'),
                        'about' => Helpers::get_settings($web, 'about_us'),
                        'footer_logo' => getWebConfig(name: 'company_footer_logo'),
                        'copyright_text' => getWebConfig(name: 'company_copyright_text'),
                        'decimal_point_settings' => !empty(getWebConfig(name: 'decimal_point_settings')) ? getWebConfig(name: 'decimal_point_settings') : 0,
                        'seller_registration' => getWebConfig(name: 'seller_registration') ?? 0,
                        'wallet_status' => getWebConfig(name: 'wallet_status'),
                        'loyalty_point_status' => getWebConfig(name: 'loyalty_point_status'),
                        'guest_checkout_status' => getWebConfig(name: 'guest_checkout'),
                        'digital_product_setting' => getWebConfig(name: 'digital_product'),
                        'language' => (is_string(getWebConfig(name: 'language')) ? (json_decode(getWebConfig(name: 'language'), true) ?? [['id' => 1, 'name' => 'English', 'code' => 'en', 'status' => 1, 'default' => true, 'direction' => 'ltr']]) : (getWebConfig(name: 'language') ?? [['id' => 1, 'name' => 'English', 'code' => 'en', 'status' => 1, 'default' => true, 'direction' => 'ltr']])),
                        'currencies' => \App\Models\Currency::where('status', 1)->get(),
                        'currency_model' => getWebConfig(name: 'currency_model') ?? 'single_currency',
                        'brand_setting' => getWebConfig(name: 'product_brand') ?? 1,
                        'publishing_houses' => Schema::hasTable('publishing_houses') ? ProductManager::getPublishingHouseList(type: 'count') : null,
                        'digital_product_authors' => Schema::hasTable('authors') ? ProductManager::getProductAuthorList() : null,
                        'firebase_otp_verification' => $firebaseOTPVerification,
                        'firebase_otp_verification_status' => $firebaseOTPVerificationStatus,
                        'announcement' => getWebConfig(name: 'announcement') ?: ['status' => 0, 'color' => '#5e2e85', 'text_color' => '#ffffff', 'announcement' => ''],
                        'meta_title' => getWebConfig(name: 'meta_title') ?: (getWebConfig(name: 'company_name').' || Your Trusted Online Market in Uyo, Akwa Ibom State'),
                        'meta_description' => getWebConfig(name: 'meta_description') ?: 'Victorious MARKET || Your Trusted Online Market in Uyo, Akwa Ibom State. Shop quality electronics, groceries, fashion, beauty, and home essentials with fast delivery in Uyo.',
                    ];

                    if ((!Request::is('admin') && !Request::is('admin/*') && !Request::is('seller/*') && !Request::is('vendor/*')) || Request::is('vendor/auth/registration/*')) {
                        $userId = Auth::guard('customer')->user() ? Auth::guard('customer')->id() : 0;
                        $flashDeal = ProductManager::getPriorityWiseFlashDealsProductsQuery(userId: $userId);

                        $shops = Cache::remember('top_approved_shops_9', CACHE_FOR_3_HOURS, function () {
                            return Shop::whereHas('seller', function ($query) {
                                return $query->approved();
                            })->take(9)->get();
                        });

                        $recaptcha = getWebConfig(name: 'recaptcha');
                        $paymentGatewayPublishedStatus = config('get_payment_publish_status') ?? 0;

                        $paymentsGatewaysList = Cache::remember('cached_payments_gateways_list_' . $paymentGatewayPublishedStatus, CACHE_FOR_3_HOURS, function () use ($paymentGatewayPublishedStatus) {
                            $paymentGatewaysQuery = Setting::whereIn('settings_type', ['payment_config'])->where('is_active', 1);
                            if ($paymentGatewayPublishedStatus == 1) {
                                return $paymentGatewaysQuery->select('key_name', 'additional_data')->get();
                            } else {
                                return $paymentGatewaysQuery->whereIn('key_name', GlobalConstant::DEFAULT_PAYMENT_GATEWAYS)->select('key_name', 'additional_data')->get();
                            }
                        });

                        $customerLoginOptions = LoginSetup::where(['key' => 'login_options'])->first()?->value ?? '';
                        $customerSocialLoginOptions = LoginSetup::where(['key' => 'social_media_for_login'])->first()?->value ?? '';
                        $customerSocialLoginOptions = json_decode($customerSocialLoginOptions, true) ?? [];
                        $socialLoginConfigStatus = $this->checkCustomerSocialMediaLoginAbility();

                        foreach ($customerSocialLoginOptions as $socialKey => $socialLoginService) {
                            $customerSocialLoginOptions[$socialKey] = isset($socialLoginConfigStatus[$socialKey]) && $socialLoginConfigStatus[$socialKey] && $socialLoginService ? 1 : 0;
                        }

                        $socialLoginTextShowStatus = false;
                        foreach ($customerSocialLoginOptions as $socialLoginService) {
                            if ($socialLoginService == 1) {
                                $socialLoginTextShowStatus = true;
                            }
                        }

                        $totalDiscountProducts = Cache::remember('total_discount_products_count', CACHE_FOR_3_HOURS, function () {
                            return Product::active()
                                ->where(function ($subQuery) {
                                    return $subQuery->where(function ($query) {
                                        return $query->where('discount', '!=', 0);
                                    })->orWhere(function ($query) {
                                        $stockClearanceProductIds = StockClearanceProduct::active()->pluck('product_id')->toArray();
                                        return $query->whereIn('id', $stockClearanceProductIds);
                                    });
                                })
                                ->count();
                        });

                        $web_config += [
                            'cookie_setting' => Helpers::get_settings($web, 'cookie_setting'),
                            'announcement' => getWebConfig(name: 'announcement'),
                            'currency_model' => getWebConfig(name: 'currency_model'),
                            'currencies' => Currency::where(['status' => 1])->get(),
                            'main_categories' => $this->cacheMainCategoriesList(),
                            'priority_wise_brands' => $this->cachePriorityWiseBrandList(),
                            'business_mode' => getWebConfig(name: 'business_mode'),
                            'social_media' => SocialMedia::where('active_status', 1)->get(),
                            'ios' => getWebConfig(name: 'download_app_apple_store'),
                            'android' => getWebConfig(name: 'download_app_google_store'),
                            'refund_policy' => getWebConfig(name: 'refund-policy'),
                            'return_policy' => getWebConfig(name: 'return-policy'),
                            'cancellation_policy' => getWebConfig(name: 'cancellation-policy'),
                            'shipping_policy' => getWebConfig(name: 'shipping-policy'),
                            'flash_deals' => $flashDeal['flashDeal'],
                            'flash_deals_products' => $flashDeal['flashDealProducts'] ?? [],
                            'shops' => $shops,
                            'brand_setting' => getWebConfig(name: 'product_brand'),
                            'discount_product' => $totalDiscountProducts,
                            'recaptcha' => $recaptcha,
                            'socials_login' => getWebConfig(name: 'social_login'),
                            'social_login_text' => $socialLoginTextShowStatus,
                            'popup_banner' => $this->cacheBannerTable(bannerType: 'Popup Banner'),
                            'header_banner' => $this->cacheBannerTable(bannerType: 'Header Banner'),
                            'payments_list' => $paymentsGatewaysList, // Fashion_theme
                            'ref_earning_status' => getWebConfig('ref_earning_status'),
                            'customer_login_options' => json_decode($customerLoginOptions, true),
                            'customer_social_login_options' => $customerSocialLoginOptions,
                            'customer_phone_verification' => getLoginConfig(key: 'phone_verification'),
                            'customer_email_verification' => getLoginConfig(key: 'email_verification'),
                            'default_meta_content' => $this->cacheRobotsMetaContent(page: 'default'),
                            'analytic_scripts' => $this->cacheActiveAnalyticScript(),
                            'clearance_sale_product_count' => $this->cacheClearanceSaleProductsCount(),
                            'business_pages' => $this->cacheBusinessPagesTable(),
                        ];

                        if (theme_root_path() == "theme_fashion") {
                            $featuresSection = [
                                'features_section_top' => getWebConfig(name: 'features_section_top') ?? [],
                                'features_section_middle' => getWebConfig(name: 'features_section_middle') ?? [],
                                'features_section_bottom' => getWebConfig(name: 'features_section_bottom') ?? [],
                            ];

                            $tags = $this->cacheTagTable();

                            $web_config += [
                                'tags' => $tags,
                                'features_section' => $featuresSection,
                                'total_discount_products' => $totalDiscountProducts,
                            ];
                        }
                    }

                    // Language
                    $language = getWebConfig(name: 'language') ?? [];

                    // Currency
                    Helpers::currency_load();
                    View::share(['web_config' => $web_config, 'language' => $language]);
                    Schema::defaultStringLength(191);
                }
            } catch (\Throwable $exception) {
                \Log::error("AppServiceProvider boot error: " . $exception->getMessage() . " at " . $exception->getFile() . ":" . $exception->getLine());
            }

            try {
                if (!in_array(request()->ip(), ['127.0.0.1', '::1'])) {
                    $this->autoClearDebugBarLogs();
                }
            } catch (Exception $exception) {}
        }

        /**
         * Paginate a standard Laravel Collection.
         *
         * @param int $perPage
         * @param int $total
         * @param int $page
         * @param string $pageName
         * @return array
         */

        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
    }

    protected function autoClearDebugBarLogs(): void
    {
        $key = 'debugbar:last_clear';
        $minutes = 60;
        $lastClear = Cache::get($key);

        if (!$lastClear || now()->diffInMinutes($lastClear) >= $minutes) {
            $debugBarPath = storage_path('debugbar');
            if (File::exists($debugBarPath)) {
                foreach (File::files($debugBarPath) as $file) {
                    File::delete($file);
                }
            }

            $logFile = storage_path('logs/laravel.log');
            if (File::exists($logFile)) {
                File::delete($logFile);
            }

            Cache::put($key, now(), $minutes);
        }
    }
}
