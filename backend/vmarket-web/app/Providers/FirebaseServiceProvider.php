<?php

namespace App\Providers;

use Kreait\Firebase\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Factory::class, function ($app) {
            $firebaseConfig = getWebConfig('push_notification_key');
            $factory = new Factory;
            if (!empty($firebaseConfig) && (is_array($firebaseConfig) || (is_string($firebaseConfig) && file_exists($firebaseConfig)))) {
                $factory = $factory->withServiceAccount($firebaseConfig);
            }
            return $factory;
        });

        $this->app->singleton(Auth::class, function ($app) {
            try {
                return $app->make(Factory::class)->createAuth();
            } catch (\Throwable $e) {
                return null;
            }
        });

        $this->app->singleton(Messaging::class, function ($app) {
            try {
                return $app->make(Factory::class)->createMessaging();
            } catch (\Throwable $e) {
                return null;
            }
        });

        // Optionally, you can bind it to a simpler alias
        $this->app->alias(Messaging::class, 'firebase.messaging');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
