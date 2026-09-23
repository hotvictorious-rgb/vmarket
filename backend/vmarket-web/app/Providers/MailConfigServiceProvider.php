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
            $phpMail = getWebConfig(name: 'mail_config_php');
            $emailServices_smtp = getWebConfig(name: 'mail_config');
            if (is_array($emailServices_smtp) && isset($emailServices_smtp['status']) && $emailServices_smtp['status'] == 0) {
                $emailServices_smtp = getWebConfig(name: 'mail_config_sendgrid');
            }

            $isPhpActive = is_array($phpMail) && isset($phpMail['status']) && $phpMail['status'] == 1;
            $isSmtpActive = is_array($emailServices_smtp) && isset($emailServices_smtp['status']) && $emailServices_smtp['status'] == 1;

            // 1. Configure sendmail mailer (native PHP mail transfer agent)
            $sendmailPath = is_array($phpMail) && !empty($phpMail['path']) ? $phpMail['path'] : '/usr/sbin/sendmail -bs';
            Config::set('mail.mailers.sendmail', [
                'transport' => 'sendmail',
                'path' => $sendmailPath,
            ]);

            // 2. Configure SMTP mailer
            if ($isSmtpActive) {
                $host = $emailServices_smtp['host'] ?? '127.0.0.1';
                $port = (int)($emailServices_smtp['port'] ?? 587);
                $username = $emailServices_smtp['username'] ?? '';
                $password = $emailServices_smtp['password'] ?? '';
                $encryption = $emailServices_smtp['encryption'] ?? 'tls';

                Config::set('mail.mailers.smtp', [
                    'transport' => 'smtp',
                    'url' => env('MAIL_URL'),
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => null,
                    'local_domain' => env('MAIL_EHLO_DOMAIN'),
                ]);
            }

            // 3. Determine default driver and global From address: PHP first, then SMTP
            if ($isPhpActive) {
                Config::set('mail.default', 'sendmail');
                $fromAddress = $phpMail['email_id'] ?? ($emailServices_smtp['email_id'] ?? 'noreply@vmarket.com');
                $fromName = $phpMail['name'] ?? ($emailServices_smtp['name'] ?? (getWebConfig(name: 'company_name') ?? 'Victorious MARKET'));
            } elseif ($isSmtpActive) {
                Config::set('mail.default', 'smtp');
                $fromAddress = $emailServices_smtp['email_id'] ?? 'noreply@vmarket.com';
                $fromName = $emailServices_smtp['name'] ?? (getWebConfig(name: 'company_name') ?? 'Victorious MARKET');
            } else {
                $fromAddress = 'noreply@vmarket.com';
                $fromName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            }

            Config::set('mail.from', [
                'address' => $fromAddress,
                'name' => $fromName,
            ]);

            // 4. Preserve flat legacy keys for backward-compatibility with custom legacy utilities
            Config::set('mail.driver', Config::get('mail.default', 'smtp'));
            Config::set('mail.sendmail', $sendmailPath);
            Config::set('mail.pretend', false);
            if ($isSmtpActive) {
                Config::set('mail.host', $emailServices_smtp['host'] ?? '127.0.0.1');
                Config::set('mail.port', (int)($emailServices_smtp['port'] ?? 587));
                Config::set('mail.username', $emailServices_smtp['username'] ?? '');
                Config::set('mail.password', $emailServices_smtp['password'] ?? '');
                Config::set('mail.encryption', $emailServices_smtp['encryption'] ?? 'tls');
            }
        } catch (\Throwable $ex) {
            \Illuminate\Support\Facades\Log::warning('MailConfigServiceProvider dynamic mail configuration exception: ' . $ex->getMessage());
        }
    }
}
