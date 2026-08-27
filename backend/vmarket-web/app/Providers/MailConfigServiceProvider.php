<?php

namespace App\Providers;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        try {
            $emailServices_smtp = getWebConfig(name: 'mail_config');
            if (is_array($emailServices_smtp) && isset($emailServices_smtp['status']) && $emailServices_smtp['status'] == 0) {
                $emailServices_smtp = getWebConfig(name: 'mail_config_sendgrid');
            }
            if (is_array($emailServices_smtp) && isset($emailServices_smtp['status']) && $emailServices_smtp['status'] == 1) {
                $config = array(
                    'driver' => $emailServices_smtp['driver'] ?? 'smtp',
                    'host' => $emailServices_smtp['host'] ?? '127.0.0.1',
                    'port' => $emailServices_smtp['port'] ?? 587,
                    'username' => $emailServices_smtp['username'] ?? '',
                    'password' => $emailServices_smtp['password'] ?? '',
                    'encryption' => $emailServices_smtp['encryption'] ?? 'tls',
                    'from' => array('address' => $emailServices_smtp['email_id'] ?? 'noreply@vmarket.com', 'name' => $emailServices_smtp['name'] ?? 'Victorious MARKET'),
                    'sendmail' => '/usr/sbin/sendmail -bs',
                    'pretend' => false,
                );
                Config::set('mail', $config);
            }
        } catch (\Throwable $ex) {

        }
    }
}
