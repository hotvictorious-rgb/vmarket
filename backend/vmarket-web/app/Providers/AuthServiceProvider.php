<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Admin;
use App\Models\Order;
use App\Models\DeliveryLane;
use App\Models\Seller;
use App\Policies\AdminPolicy;
use App\Policies\OrderPolicy;
use App\Policies\FinancePolicy;
use App\Policies\GeographyPolicy;
use App\Policies\VendorManagementPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Admin::class => AdminPolicy::class,
        Order::class => OrderPolicy::class,
        DeliveryLane::class => GeographyPolicy::class,
        Seller::class => VendorManagementPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
