<?php

namespace App\Services;

use Exception;
use App\Mail\TestEmailSender;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function getData(object $request): array
    {
        return [
            "status" => $request->get('status', 0),
            "name" => $request['name'],
            "host" => $request['host'],
            "driver" => $request['driver'],
            "port" => $request['port'],
            "username" => $request['username'],
            "email_id" => $request['email'],
            "encryption" => $request['encryption'],
            "password" => $request['password']
        ];
    }

    public function getMailData(object|array $mailData): array
    {
        return [
            "status" => 0,
            "name" => $mailData['name'],
            "host" => $mailData['host'],
            "driver" => $mailData['driver'],
            "port" => $mailData['port'],
            "username" => $mailData['username'],
            "email_id" => $mailData['email_id'],
            "encryption" => $mailData['encryption'],
            "password" => $mailData['password']
        ];
    }

    public function sendMail(object $request): array
    {
        $status = 0;
        $message = 'success';
        try {
            $phpMail = getWebConfig(name: 'mail_config_php');
            $smtpMail = getWebConfig(name: 'mail_config');
            if (is_array($smtpMail) && isset($smtpMail['status']) && $smtpMail['status'] == 0) {
                $smtpMail = getWebConfig(name: 'mail_config_sendgrid');
            }

            $isPhpActive = is_array($phpMail) && isset($phpMail['status']) && $phpMail['status'] == 1;
            $isSmtpActive = is_array($smtpMail) && isset($smtpMail['status']) && $smtpMail['status'] == 1;

            if ($isPhpActive && $isSmtpActive) {
                // Rule: PHP mail first, then fallback to SMTP if it fails
                try {
                    Mail::mailer('sendmail')->to($request->email)->send(new TestEmailSender());
                    $status = 1;
                    $message = 'Test mail sent successfully via PHP Mail (Sendmail).';
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Test mail: PHP mail failed, falling back to SMTP: ' . $e->getMessage());
                    Mail::mailer('smtp')->to($request->email)->send(new TestEmailSender());
                    $status = 1;
                    $message = 'PHP Mail failed, successfully failed over to SMTP!';
                }
            } elseif ($isPhpActive) {
                Mail::mailer('sendmail')->to($request->email)->send(new TestEmailSender());
                $status = 1;
                $message = 'Test mail sent successfully via PHP Mail (Sendmail).';
            } elseif ($isSmtpActive) {
                try {
                    Mail::mailer('smtp')->to($request->email)->send(new TestEmailSender());
                    $status = 1;
                    $message = 'Test mail sent successfully via SMTP.';
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Test mail: SMTP failed, attempting PHP mail fallback: ' . $e->getMessage());
                    Mail::mailer('sendmail')->to($request->email)->send(new TestEmailSender());
                    $status = 1;
                    $message = 'SMTP failed, successfully failed over to PHP Mail!';
                }
            } else {
                $message = 'No mail configuration is currently active. Please activate PHP Mail or SMTP.';
                $status = 2;
            }
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            $status = 2;
            \Illuminate\Support\Facades\Log::error('Test mail dispatch failed: ' . $exception->getMessage());
        }
        return [
            'status' => $status,
            'message' => $message
        ];
    }

}
