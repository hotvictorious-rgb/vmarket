<?php

namespace App\Traits;

use App\Models\NotificationMessage;
use App\Models\Order;
use App\Models\ReferralCustomer;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Throwable;

trait PushNotificationTrait
{
    use CommonTrait;

    /**
     * @param string $key
     * @param string $type
     * @param object|array $order
     * @param object|array $data
     * @return void
     * push notification order related
     */
    protected function sendOrderNotification(string $key, string $type, object|array $order, object|array $data = []): void
    {
        try {
            $lang = getDefaultLanguage();
            /** for customer  */
            if ($type == 'customer') {
                $fcmToken = $order->customer?->cm_firebase_token;
                $lang = $order->customer?->app_language ?? $lang;
                $value = $this->pushNotificationMessage($key, 'customer', $lang);
                if ($fcmToken && $value) {
                    $value = $this->textVariableDataFormat(value: $value, key: $key, userName: "{$order->customer?->f_name} {$order->customer?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                    $postData = [
                        'title' => translate('order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'order_details_id' => $data['order_details_id'] ?? '',
                        'image' => '',
                        'type' => 'order',
                        'message_key' => $key,
                    ];
                    $this->sendPushNotificationToDevice($fcmToken, $postData);
                }
            }

            if ($type == 'promoter') {
                $getCustomer = ReferralCustomer::where('user_id', $order->customer->id)->first();
                $getPromoter = User::where('id', $getCustomer->refer_by)->first();
                $fcmToken = $getPromoter?->cm_firebase_token;
                $lang = $getPromoter?->app_language ?? $lang;
                $value = $this->pushNotificationMessage(key: $key, userType: 'customer', lang: $lang);
                if ($fcmToken && $value) {
                    $value = $this->textVariableDataFormat(value: $value, key: $key, userName: "{$getPromoter?->f_name} {$getPromoter?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                    $postData = [
                        'title' => translate('Referral_Reward_Updates'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'order_details_id' => $data['order_details_id'] ?? '',
                        'image' => '',
                        'type' => 'referral_code_used',
                        'message_key' => $key,
                    ];
                    $this->sendPushNotificationToDevice($fcmToken, $postData);
                }
            }
            /** end for customer  */

            /**for seller */
            if ($type == 'seller') {
                $sellerFcmToken = $order->seller?->cm_firebase_token;
                if ($sellerFcmToken) {
                    $lang = $order->seller?->app_language ?? $lang;
                    $value_seller = $this->pushNotificationMessage($key, 'seller', $lang);

                    if ($value_seller) {
                        $value_seller = $this->textVariableDataFormat(value: $value_seller, key: $key, userName: "{$order->customer?->f_name} {$order->customer?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                        $postData = [
                            'title' => translate('order'),
                            'description' => $value_seller,
                            'order_id' => $order['id'],
                            'order_details_id' => $data['order_details_id'] ?? '',
                            'image' => '',
                            'type' => 'order',
                            'message_key' => $key,
                        ];
                        if (isset($data['refund'])) {
                            $postData['type'] = 'refund';
                            $postData['refund_id'] = $data['refund']['id'];
                        }

                        $this->sendPushNotificationToDevice($sellerFcmToken, $postData);
                    }
                }
            }
            /**end for seller */

            /** for delivery man*/
            if ($type == 'delivery_man') {
                $fcmTokenDeliveryMan = $order->deliveryMan?->fcm_token;
                $lang = $order->deliveryMan?->app_language ?? $lang;
                $value_delivery_man = $this->pushNotificationMessage($key, 'delivery_man', $lang);

                if ($value_delivery_man) {
                    $value_delivery_man = $this->textVariableDataFormat(value: $value_delivery_man, key: $key, userName: "{$order->customer?->f_name} {$order->customer?->l_name}", shopName: $order->seller?->shop?->name, deliveryManName: "{$order->deliveryMan?->f_name} {$order->deliveryMan?->l_name}", time: now()->diffForHumans(), orderId: $order->id);
                    $postData = [
                        'title' => translate('order'),
                        'description' => $value_delivery_man,
                        'order_id' => $order['id'],
                        'deliveryman_charge' => usdToDefaultCurrency(amount: $order['deliveryman_charge']) ?? 0,
                        'expected_delivery_date' => $order['expected_delivery_date'] ?? '',
                        'image' => '',
                        'type' => 'order'
                    ];
                    if ($order->delivery_man_id) {
                        self::add_deliveryman_push_notification($postData, $order->delivery_man_id);
                    }
                    if ($fcmTokenDeliveryMan) {
                        $this->sendPushNotificationToDevice($fcmTokenDeliveryMan, $postData);
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    protected function withdrawStatusUpdateNotification(string $key, string $type, string $lang, int $status, string $fcmToken): void
    {
        $value = $this->pushNotificationMessage($key, $type, $lang);
        if ($value) {
            $data = [
                'title' => translate('withdraw_request_' . ($status == 1 ? 'approved' : 'denied')),
                'description' => $value,
                'image' => '',
                'type' => 'wallet_withdraw',
                'message_key' => $key,
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    protected function customerStatusUpdateNotification(string $key, string $type, string $lang, string $status, string $fcmToken): void
    {
        $value = $this->pushNotificationMessage($key, $type, $lang);
        if ($value) {
            $data = [
                'title' => translate('your_account_has_been' . '_' . $status),
                'description' => $value,
                'image' => '',
                'type' => 'block',
                'message_key' => $key,
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    protected function productRequestStatusUpdateNotification(string $key, string $type, string $lang, string $fcmToken): void
    {
        $value = $this->pushNotificationMessage($key, $type, $lang);
        if ($value) {
            $data = [
                'title' => translate($key),
                'description' => $value,
                'image' => '',
                'type' => 'product_request_approved_message',
                'message_key' => $key,
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    protected function cashCollectNotification(string $key, string $type, string $lang, float $amount, string $fcmToken): void
    {
        $value = $this->pushNotificationMessage($key, $type, $lang);
        if ($value) {
            $data = [
                'title' => currencyConverter($amount) . ' ' . translate('_cash_deposit'),
                'description' => $value,
                'image' => '',
                'type' => 'wallet',
                'message_key' => $key,
            ];
            $this->sendPushNotificationToDevice($fcmToken, $data);
        }
    }

    /**
     * push notification variable message format
     */
    protected function textVariableDataFormat($value, $key = null, $userName = null, $shopName = null, $deliveryManName = null, $time = null, $orderId = null, $companyName = null, $batchId = null, $reservationCode = null, $amount = null)
    {
        $data = $value;
        if ($data) {
            $order = $orderId ? Order::find($orderId) : null;
            $data = $userName ? str_replace("{userName}", $userName, $data) : $data;
            $data = $shopName ? str_replace("{shopName}", $shopName, $data) : $data;
            $data = $deliveryManName ? str_replace("{deliveryManName}", $deliveryManName, $data) : $data;
            $data = $companyName ? str_replace("{companyName}", $companyName, $data) : $data;
            $data = $batchId ? str_replace("{batchId}", $batchId, $data) : $data;
            $data = $reservationCode ? str_replace("{reservationCode}", $reservationCode, $data) : $data;
            $data = $amount ? str_replace("{amount}", $amount, $data) : $data;
            $data = $key == 'expected_delivery_date' ? ($order ? str_replace("{time}", $order->expected_delivery_date, $data) : $data) : ($time ? str_replace("{time}", $time, $data) : $data);
            $data = $orderId ? str_replace("{orderId}", $orderId, $data) : $data;
        }
        return $data;
    }

    /**
     * send in-shop pickup reservation push and in-app notifications
     */
    protected function sendPickupReservationNotification(string $key, object $reservation, string $target = 'customer'): void
    {
        try {
            $customer = $reservation->customer ?? \App\Models\User::find($reservation->customer_id);
            $seller = $reservation->seller ?? \App\Models\Seller::find($reservation->seller_id);
            $shop = $reservation->shop ?? \App\Models\Shop::find($reservation->shop_id);

            $userName = $customer ? trim("{$customer->f_name} {$customer->l_name}") : 'Customer';
            $shopName = $shop ? $shop->name : ($seller?->shop?->name ?? 'Shop');

            if ($target === 'customer' && $customer) {
                $lang = $customer->app_language ?? getDefaultLanguage();
                $value = $this->pushNotificationMessage(key: $key, userType: 'customer', lang: $lang);
                if ($value) {
                    $formattedMessage = $this->textVariableDataFormat(
                        value: $value,
                        key: $key,
                        userName: $userName,
                        shopName: $shopName,
                        time: now()->diffForHumans(),
                        reservationCode: $reservation->reservation_code ?? ''
                    );
                    $postData = [
                        'title' => translate(str_replace('_', ' ', $key)),
                        'description' => $formattedMessage,
                        'reservation_code' => $reservation->reservation_code ?? '',
                        'reservation_id' => $reservation->id ?? '',
                        'image' => '',
                        'type' => 'pickup_reservation',
                        'message_key' => $key,
                    ];
                    if (!empty($customer->cm_firebase_token)) {
                        $this->sendPushNotificationToDevice($customer->cm_firebase_token, $postData);
                    }
                }
            }

            if ($target === 'seller' && $seller) {
                $lang = $seller->app_language ?? getDefaultLanguage();
                $value = $this->pushNotificationMessage(key: $key, userType: 'seller', lang: $lang);
                if ($value) {
                    $formattedMessage = $this->textVariableDataFormat(
                        value: $value,
                        key: $key,
                        userName: $userName,
                        shopName: $shopName,
                        time: now()->diffForHumans(),
                        reservationCode: $reservation->reservation_code ?? ''
                    );
                    $postData = [
                        'title' => translate(str_replace('_', ' ', $key)),
                        'description' => $formattedMessage,
                        'reservation_code' => $reservation->reservation_code ?? '',
                        'reservation_id' => $reservation->id ?? '',
                        'image' => '',
                        'type' => 'pickup_reservation',
                        'message_key' => $key,
                    ];
                    if (!empty($seller->cm_firebase_token)) {
                        $this->sendPushNotificationToDevice($seller->cm_firebase_token, $postData);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail-safe
        }
    }

    /**
     * send cashback earned push and in-app notification to customer
     */
    protected function sendCashbackEarnedNotification(object $order, string $cashbackAmount): void
    {
        try {
            $customer = $order->customer ?? \App\Models\User::find($order->customer_id);
            if ($customer && !empty($customer->cm_firebase_token)) {
                $lang = $customer->app_language ?? getDefaultLanguage();
                $value = $this->pushNotificationMessage(key: 'cashback_earned_message', userType: 'customer', lang: $lang);
                if ($value) {
                    $formattedMessage = $this->textVariableDataFormat(
                        value: $value,
                        key: 'cashback_earned_message',
                        userName: trim("{$customer->f_name} {$customer->l_name}"),
                        shopName: $order->seller?->shop?->name ?? 'Victorious Market',
                        time: now()->diffForHumans(),
                        orderId: $order->id,
                        amount: currencyConverter($cashbackAmount)
                    );
                    $postData = [
                        'title' => translate('Cashback_Reward_Earned'),
                        'description' => $formattedMessage,
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'cashback',
                        'message_key' => 'cashback_earned_message',
                    ];
                    $this->sendPushNotificationToDevice($customer->cm_firebase_token, $postData);
                }
            }
        } catch (\Throwable $e) {
            // Fail-safe
        }
    }

    /**
     * send logistics company in-portal and dispatch alert notification
     */
    protected function sendLogisticsNotification(string $key, object|array $company, array $data = []): void
    {
        try {
            $lang = getDefaultLanguage();
            $value = $this->pushNotificationMessage(key: $key, userType: 'logistics_company', lang: $lang);
            if ($value) {
                $contactName = is_object($company) ? ($company->contact_person_name ?? $company->name ?? '') : ($company['contact_person_name'] ?? $company['name'] ?? '');
                $companyName = is_object($company) ? ($company->name ?? '') : ($company['name'] ?? '');
                $companyId = is_object($company) ? ($company->id ?? null) : ($company['id'] ?? null);
                $companyEmail = is_object($company) ? ($company->company_email ?? null) : ($company['company_email'] ?? null);

                $formattedMessage = $this->textVariableDataFormat(
                    value: $value,
                    key: $key,
                    userName: $contactName,
                    companyName: $companyName,
                    time: now()->diffForHumans(),
                    orderId: $data['order_id'] ?? null,
                    batchId: $data['batch_id'] ?? null
                );

                if ($companyId) {
                    \App\Models\Notification::create([
                        'sent_by' => 'admin',
                        'sent_to' => 'logistics_' . $companyId,
                        'title' => translate(str_replace('_', ' ', $key)),
                        'description' => $formattedMessage,
                        'notification_count' => 1,
                        'image' => '',
                        'status' => 1,
                    ]);
                }

                if (!empty($companyEmail)) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw($formattedMessage, function ($mail) use ($companyEmail, $key) {
                            $mail->to($companyEmail)
                                 ->subject(translate(str_replace('_', ' ', $key)) . ' - ' . getWebConfig('company_name'));
                        });
                    } catch (\Throwable $e) {
                        // Email fail-safe
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail-safe
        }
    }

    /**
     * push notification variable message
     * @param string $key
     * @param string $userType
     * @param string $lang
     * @return false|int|mixed|void
     */
    protected function pushNotificationMessage(string $key, string $userType, string $lang)
    {
        try {
            $notificationKey = [
                'pending' => 'order_pending_message',
                'confirmed' => 'order_confirmation_message',
                'processing' => 'order_processing_message',
                'out_for_delivery' => 'out_for_delivery_message',
                'delivered' => 'order_delivered_message',
                'returned' => 'order_returned_message',
                'failed' => 'order_failed_message',
                'canceled' => 'order_canceled',
                'order_refunded_message' => 'order_refunded_message',
                'refund_request_canceled_message' => 'refund_request_canceled_message',
                'new_order_message' => 'new_order_message',
                'order_edit_message' => 'order_edit_message',
                'order_edit_return_amount_message' => 'order_edit_return_amount_message',
                'new_order_assigned_message' => 'new_order_assigned_message',
                'delivery_man_assign_by_admin_message' => 'delivery_man_assign_by_admin_message',
                'order_rescheduled_message' => 'order_rescheduled_message',
                'expected_delivery_date' => 'expected_delivery_date',
                'message_from_admin' => 'message_from_admin',
                'message_from_seller' => 'message_from_seller',
                'message_from_delivery_man' => 'message_from_delivery_man',
                'message_from_customer' => 'message_from_customer',
                'refund_request_status_changed_by_admin' => 'refund_request_status_changed_by_admin',
                'withdraw_request_status_message' => 'withdraw_request_status_message',
                'fund_added_by_admin_message' => 'fund_added_by_admin_message',
                'delivery_man_charge' => 'delivery_man_charge',
                'product_request_approved_message' => 'product_request_approved_message',
                'product_request_rejected_message' => 'product_request_rejected_message',
                'customer_block_message' => 'customer_block_message',
                'customer_unblock_message' => 'customer_unblock_message',
                'your_referred_customer_has_been_place_order' => 'your_referred_customer_has_been_place_order',
                'your_referred_customer_order_has_been_delivered' => 'your_referred_customer_order_has_been_delivered',
                'cashback_earned_message' => 'cashback_earned_message',
                'pickup_reserved_message' => 'pickup_reserved_message',
                'pickup_inspected_accepted_message' => 'pickup_inspected_accepted_message',
                'pickup_completed_message' => 'pickup_completed_message',
                'pickup_expired_message' => 'pickup_expired_message',
                'order_waybill_generated_message' => 'order_waybill_generated_message',
                'new_pickup_reservation_message' => 'new_pickup_reservation_message',
                'pickup_reservation_expired_message' => 'pickup_reservation_expired_message',
                'low_stock_alert_message' => 'low_stock_alert_message',
                'delivery_partner_assigned_message' => 'delivery_partner_assigned_message',
                'waybill_assigned_message' => 'waybill_assigned_message',
                'order_dispatched_to_company' => 'order_dispatched_to_company',
                'waybill_routed_to_company' => 'waybill_routed_to_company',
                'rider_delivery_completed' => 'rider_delivery_completed',
                'company_withdrawal_status' => 'company_withdrawal_status',
                'rider_failed_delivery_alert' => 'rider_failed_delivery_alert',
            ];
            $targetKey = $notificationKey[$key] ?? $key;
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where(['key' => $targetKey, 'user_type' => $userType])->first() ?? ["status" => 0, "message" => "", "translations" => []];
            if ($data) {
                if ($data['status'] == 0) {
                    return false;
                }
                return count($data->translations) > 0 ? $data->translations[0]->value : $data['message'];
            } else {
                return false;
            }
        } catch (Exception $exception) {

        }
    }


    protected function demoResetNotification(): void
    {
        try {
            $data = [
                'title' => translate('demo_reset_alert'),
                'description' => translate('demo_data_is_being_reset_to_default') . '.',
                'image' => '',
                'order_id' => '',
                'type' => 'demo_reset',
            ];
            $this->sendPushNotificationToTopic(data: $data, topic: $data['type']);
        } catch (Throwable $th) {
            info('Failed_to_sent_demo_reset_notification');
        }
    }


    /**
     * Device wise notification send
     * @param string $fcmToken
     * @param array $data
     * @return bool|string
     */

    protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
    {
        $postData = [
            'message' => [
                'token' => $fcmToken,
                'data' => [
                    'title' => (string)$data['title'],
                    'body' => (string)$data['description'],
                    'image' => $data['image'],
                    'order_id' => (string)($data['order_id'] ?? ''),
                    'order_details_id' => (string)($data['order_details_id'] ?? ''),
                    'refund_id' => (string)($data['refund_id'] ?? ''),
                    'deliveryman_charge' => (string)($data['deliveryman_charge'] ?? ''),
                    'expected_delivery_date' => (string)($data['expected_delivery_date'] ?? ''),
                    'type' => (string)$data['type'],
                    'is_read' => '0',
                    'message_key' => (string)($data['message_key'] ?? ''),
                    'notification_key' => (string)($data['notification_key'] ?? ''),
                    'notification_from' => (string)($data['notification_from'] ?? ''),
                ],
                'notification' => [
                    'title' => (string)$data['title'],
                    'body' => (string)$data['description'],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ]
                    ]
                ]
            ]
        ];
        return $this->sendNotificationToHttp($postData);
    }

    /**
     * Device wise notification send
     * @param array|object $data
     * @param string $topic
     * @return bool|string
     */
    protected function sendPushNotificationToTopic(array|object $data, string $topic = 'sixvalley'): bool|string
    {
        $postData = [
            'message' => [
                'topic' => $topic,
                'data' => [
                    'title' => (string)($data['title'] ?? ''),
                    'body' => (string)($data['description'] ?? ''),
                    'image' => $data['image'] ?? '',
                    'order_id' => (string)($data['order_id'] ?? ''),
                    'type' => (string)($data['type'] ?? ''),
                    'is_read' => '0'
                ],
                'notification' => [
                    'title' => (string)($data['title'] ?? ''),
                    'body' => (string)($data['description'] ?? ''),
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ]
                    ]
                ]
            ]
        ];
        return $this->sendNotificationToHttp($postData);
    }

    protected function sendNotificationToHttp(array|null $data): bool|string|null
    {
        try {
            $key = (array)getWebConfig('push_notification_key');
            if (isset($key['project_id'])) {
                $url = 'https://fcm.googleapis.com/v1/projects/' . $key['project_id'] . '/messages:send';
                $headers = [
                    'Authorization' => 'Bearer ' . $this->getAccessToken($key),
                    'Content-Type' => 'application/json',
                ];
            }
            return Http::withHeaders($headers)->post($url, $data);
        } catch (Exception $exception) {
            return false;
        }
    }

    protected function getAccessToken($key): string|null
    {
        $jwtToken = [
            'iss' => $key['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => time() + 3600,
            'iat' => time(),
        ];
        $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $jwtPayload = base64_encode(json_encode($jwtToken));
        $unsignedJwt = $jwtHeader . '.' . $jwtPayload;
        openssl_sign($unsignedJwt, $signature, $key['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $unsignedJwt . '.' . base64_encode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);
        return $response->json('access_token') ?? null;
    }
}
