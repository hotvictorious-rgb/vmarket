<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // [AI] Single schedule authority; no duplicate entries in routes/console.php.
        $schedule->command('products:check-price-expiry')->dailyAt('00:01')->withoutOverlapping();
        $schedule->command('products:check-marketplace-freshness')->hourly()->withoutOverlapping();
        $schedule->command('cashback:mature')->hourly()->withoutOverlapping();
        $schedule->command('orders:process-settlement-eligibility')->hourly()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
