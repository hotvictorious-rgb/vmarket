<?php

namespace App\Traits;

use App\Mail\SendMail;
use App\Models\SocialMedia;
use App\Models\EmailTemplate;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Services\EmailTemplateService;

trait EmailTemplateTrait
{
    use FileManagerTrait;

    protected function textVariableFormat(
        $value, $userName = null, $adminName = null, $vendorName = null, $shopName = null, $shopId = null,
        $deliveryManName = null, $orderId = null, $emailId = null)
    {
        $data = $value;
        if ($data) {
            $data = $userName ? str_replace("{userName}", $userName, $data) : $data;
            $data = $vendorName ? str_replace("{vendorName}", $vendorName, $data) : $data;
            $data = $adminName ? str_replace("{adminName}", $adminName, $data) : $data;
            $data = $shopName ? str_replace("{shopName}", $shopName, $data) : $data;
            $data = $shopName ? str_replace("{shopId}", $shopId, $data) : $data;
            $data = $deliveryManName ? str_replace("{deliveryManName}", $deliveryManName, $data) : $data;
            $data = $orderId ? str_replace("{orderId}", $orderId, $data) : $data;
            $data = $emailId ? str_replace("{emailId}", $emailId, $data) : $data;
        }
        return $data;
    }

    protected function sendingMail($sendMailTo, $userType, $templateName, $data = null): void
    {
        $template = EmailTemplate::with('translationCurrentLanguage')->where(['user_type' => $userType, 'template_name' => $templateName])->first();
        if ($template) {
            if (count($template['translationCurrentLanguage'])) {
                foreach ($template?->translationCurrentLanguage ?? [] as $translate) {
                    $template['title'] = $translate->key == 'title' ? $translate->value : $template['title'];
                    $template['body'] = $translate->key == 'body' ? $translate->value : $template['body'];
                    $template['footer_text'] = $translate->key == 'footer_text' ? $translate->value : $template['footer_text'];
                    $template['copyright_text'] = $translate->key == 'copyright_text' ? $translate->value : $template['copyright_text'];
                    $template['button_name'] = $translate->key == 'button_name' ? $translate->value : $template['button_name'];
                }
            }
            $socialMedia = SocialMedia::where(['status' => 1])->get();
            $template['body'] = $this->textVariableFormat(
                value: $template['body'],
                userName: $data['userName'] ?? null,
                adminName: $data['adminName'] ?? null,
                vendorName: $data['vendorName'] ?? null,
                shopName: $data['shopName'] ?? null,
                shopId: $data['shopId'] ?? null,
                deliveryManName: $data['deliveryManName'] ?? null,
                orderId: $data['orderId'] ?? null,
                emailId: $data['emailId'] ?? null
            );
            $template['title'] = $this->textVariableFormat(
                value: $template['title'],
                userName: $data['userName'] ?? null,
                adminName: $data['adminName'] ?? null,
                vendorName: $data['vendorName'] ?? null,
                shopName: $data['shopName'] ?? null,
                deliveryManName: $data['deliveryManName'] ?? null,
                orderId: $data['orderId'] ?? null
            );
            $data['send-mail'] = true;
            if ($template['status'] == 1) {
                $phpMail = getWebConfig(name: 'mail_config_php');
                $smtpMail = getWebConfig(name: 'mail_config');
                if (is_array($smtpMail) && isset($smtpMail['status']) && $smtpMail['status'] == 0) {
                    $smtpMail = getWebConfig(name: 'mail_config_sendgrid');
                }

                $isPhpActive = is_array($phpMail) && isset($phpMail['status']) && $phpMail['status'] == 1;
                $isSmtpActive = is_array($smtpMail) && isset($smtpMail['status']) && $smtpMail['status'] == 1;

                if ($isPhpActive && $isSmtpActive) {
                    // Rule: PHP mail first, then fallback to SMTP
                    try {
                        Mail::mailer('sendmail')->to($sendMailTo)->send(new SendMail($data, $template, $socialMedia));
                    } catch (\Throwable $exception) {
                        \Illuminate\Support\Facades\Log::warning('PHP mail (sendmail) failed, failing over to SMTP: ' . $exception->getMessage(), [
                            'to' => $sendMailTo,
                            'template' => $templateName,
                        ]);
                        try {
                            Mail::mailer('smtp')->to($sendMailTo)->send(new SendMail($data, $template, $socialMedia));
                        } catch (\Throwable $smtpException) {
                            \Illuminate\Support\Facades\Log::error('SMTP fallback also failed: ' . $smtpException->getMessage(), [
                                'to' => $sendMailTo,
                                'template' => $templateName,
                            ]);
                        }
                    }
                } elseif ($isPhpActive) {
                    try {
                        Mail::mailer('sendmail')->to($sendMailTo)->send(new SendMail($data, $template, $socialMedia));
                    } catch (\Throwable $exception) {
                        \Illuminate\Support\Facades\Log::error('PHP mail failed in EmailTemplateTrait: ' . $exception->getMessage(), [
                            'to' => $sendMailTo,
                            'template' => $templateName,
                        ]);
                    }
                } elseif ($isSmtpActive) {
                    try {
                        Mail::mailer('smtp')->to($sendMailTo)->send(new SendMail($data, $template, $socialMedia));
                    } catch (\Throwable $exception) {
                        \Illuminate\Support\Facades\Log::warning('SMTP mail failed, attempting PHP sendmail fallback: ' . $exception->getMessage(), [
                            'to' => $sendMailTo,
                            'template' => $templateName,
                        ]);
                        try {
                            Mail::mailer('sendmail')->to($sendMailTo)->send(new SendMail($data, $template, $socialMedia));
                        } catch (\Throwable $phpException) {
                            \Illuminate\Support\Facades\Log::error('PHP sendmail fallback failed: ' . $phpException->getMessage(), [
                                'to' => $sendMailTo,
                                'template' => $templateName,
                            ]);
                        }
                    }
                } else {
                    try {
                        Mail::to($sendMailTo)->send(new SendMail($data, $template, $socialMedia));
                    } catch (\Throwable $exception) {
                        \Illuminate\Support\Facades\Log::error('Default mail dispatch failed: ' . $exception->getMessage(), [
                            'to' => $sendMailTo,
                            'template' => $templateName,
                        ]);
                    }
                }
            }
            if (isset($data['attachmentPath'])) {
                unlink($data['attachmentPath']);
            }
        }
    }

    public function getEmailTemplateDataForUpdate($userType): void
    {
        $emailTemplates = EmailTemplate::where(['user_type' => $userType])->get();
        $emailTemplateArray = (new EmailTemplateService)->getEmailTemplateData(userType: $userType);
        foreach ($emailTemplateArray as $value) {
            $checkKey = $emailTemplates->where('template_name', $value)->first();
            if ($checkKey === null) {
                $hideField = (new EmailTemplateService)->getHiddenField(userType: $userType, templateName: $value);
                $title = (new EmailTemplateService)->getTitleData(userType: $userType, templateName: $value);
                $body = (new EmailTemplateService)->getBodyData(userType: $userType, templateName: $value);
                $addData = (new EmailTemplateService)->getAddData(userType: $userType, templateName: $value, hideField: $hideField, title: $title, body: $body);
                EmailTemplate::create($addData);
            }
        }
        foreach ($emailTemplates as $value) {
            if (!in_array($value['template_name'], $emailTemplateArray)) {
                EmailTemplate::find($value['id'])->delete();
            }
        }
    }
}
