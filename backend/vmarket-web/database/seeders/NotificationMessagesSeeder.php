<?php

namespace Database\Seeders;

use App\Models\NotificationMessage;
use Illuminate\Database\Seeder;

/**
 * [AI] Seeder NotificationMessagesSeeder
 * Seeds comprehensive, production-ready default push and in-app notification messages
 * across Customer, Vendor, Delivery Rider, and Logistics Company actors.
 */
class NotificationMessagesSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            // ==================== CUSTOMER NOTIFICATIONS ====================
            [
                'user_type' => 'customer',
                'key' => 'order_pending_message',
                'message' => 'Hello {userName}, your order #{orderId} has been placed successfully and is pending confirmation.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_confirmation_message',
                'message' => 'Great news {userName}! Your order #{orderId} from {shopName} has been confirmed.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_processing_message',
                'message' => 'Your order #{orderId} is now being prepared by {shopName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'out_for_delivery_message',
                'message' => 'Your order #{orderId} is out for delivery with dispatch rider {deliveryManName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_delivered_message',
                'message' => 'Your order #{orderId} has been successfully delivered. Thank you for shopping with Victorious MARKET!',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_returned_message',
                'message' => 'Order #{orderId} return request has been processed.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_failed_message',
                'message' => 'Delivery attempt for order #{orderId} was unsuccessful. Our dispatch team will follow up.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_canceled',
                'message' => 'Order #{orderId} has been cancelled.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_refunded_message',
                'message' => 'Refund for order #{orderId} has been approved and processed.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'refund_request_canceled_message',
                'message' => 'Your refund request for order #{orderId} has been reviewed and declined.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'message_from_delivery_man',
                'message' => 'New message from dispatch rider {deliveryManName} regarding order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'message_from_seller',
                'message' => 'New message from merchant {shopName} regarding order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'message_from_admin',
                'message' => 'You have received an official update from Victorious MARKET Support.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_rescheduled_message',
                'message' => 'The expected delivery for order #{orderId} has been rescheduled.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'expected_delivery_date',
                'message' => 'Expected delivery time for order #{orderId} is {time}.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'customer_block_message',
                'message' => 'Your account has been temporarily restricted. Please contact support.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'customer_unblock_message',
                'message' => 'Your account has been restored. Welcome back to Victorious MARKET!',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'your_referred_customer_has_been_place_order',
                'message' => 'Congratulations! A shopper you referred just placed their first order.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'your_referred_customer_order_has_been_delivered',
                'message' => 'Referral reward unlocked! Your referred friend\'s order was delivered.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_edit_message',
                'message' => 'Order #{orderId} details have been updated.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_edit_return_amount_message',
                'message' => 'Adjustment refund of {amount} has been issued for order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'cashback_earned_message',
                'message' => 'Congratulations {userName}! You earned {amount} in Victorious Cashback Rewards for Order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'pickup_reserved_message',
                'message' => 'Hello {userName}, your item reservation {reservationCode} at {shopName} is confirmed for in-store physical inspection.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'pickup_inspected_accepted_message',
                'message' => 'Inspection Passed! Your reservation {reservationCode} at {shopName} is accepted. Proceed to make payment.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'pickup_completed_message',
                'message' => 'Thank you {userName}! Your in-shop pickup order #{orderId} from {shopName} has been successfully completed.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'pickup_expired_message',
                'message' => 'Notice: Your reservation {reservationCode} at {shopName} was not claimed within the hold window and has expired.',
                'status' => 1,
            ],
            [
                'user_type' => 'customer',
                'key' => 'order_waybill_generated_message',
                'message' => 'A digital waybill has been generated for order #{orderId}. Your items are being prepared for transit.',
                'status' => 1,
            ],

            // ==================== VENDOR NOTIFICATIONS ====================
            [
                'user_type' => 'seller',
                'key' => 'new_order_message',
                'message' => 'New Order Alert! You have received a new order #{orderId} from customer {userName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'order_edit_message',
                'message' => 'Order #{orderId} details were updated.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'order_edit_return_amount_message',
                'message' => 'Price adjustment applied to order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'refund_request_status_changed_by_admin',
                'message' => 'Refund request status for order #{orderId} was updated by Victorious MARKET Admin.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'message_from_customer',
                'message' => 'New customer message from {userName} regarding order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'message_from_delivery_man',
                'message' => 'Dispatch rider {deliveryManName} sent a message regarding order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'message_from_admin',
                'message' => 'Official notice from Victorious MARKET Super Admin.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'fund_added_by_admin_message',
                'message' => 'Your merchant wallet was credited by platform administrator.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'delivery_man_charge',
                'message' => 'Delivery fee adjustment recorded for order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'withdraw_request_status_message',
                'message' => 'Your merchant withdrawal request status has been updated.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'product_request_approved_message',
                'message' => 'Your new product listing has been reviewed and approved for the marketplace.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'product_request_rejected_message',
                'message' => 'Your product listing requires modification before marketplace approval.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'new_pickup_reservation_message',
                'message' => 'New In-Shop Reservation: Customer {userName} has reserved an item (Code: {reservationCode}) at {shopName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'pickup_reservation_expired_message',
                'message' => 'Reservation Expired: Reservation {reservationCode} at {shopName} was not claimed within the hold window and has expired.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'low_stock_alert_message',
                'message' => 'Inventory Alert: One or more products in {shopName} are running low on stock. Please restock.',
                'status' => 1,
            ],
            [
                'user_type' => 'seller',
                'key' => 'delivery_partner_assigned_message',
                'message' => 'Logistics Assigned: Dispatch rider {deliveryManName} has been assigned to collect order #{orderId}.',
                'status' => 1,
            ],

            // ==================== DELIVERY RIDER NOTIFICATIONS ====================
            [
                'user_type' => 'delivery_man',
                'key' => 'new_order_assigned_message',
                'message' => 'New Dispatch Assignment! Order #{orderId} has been assigned to you for collection at {shopName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'expected_delivery_date',
                'message' => 'Delivery schedule for order #{orderId} is set for {time}.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'delivery_man_assign_by_admin_message',
                'message' => 'Super Admin assigned you to dispatch order #{orderId} to customer {userName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'order_rescheduled_message',
                'message' => 'Order #{orderId} delivery window has been rescheduled.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'order_canceled',
                'message' => 'Order #{orderId} has been cancelled. Return parcel to merchant if already collected.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'message_from_seller',
                'message' => 'Merchant {shopName} sent a message regarding order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'message_from_admin',
                'message' => 'Official dispatch instruction from Victorious MARKET Dispatch Center.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'message_from_customer',
                'message' => 'Customer {userName} sent a delivery location update for order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'withdraw_request_status_message',
                'message' => 'Your rider delivery fee withdrawal request status has been updated.',
                'status' => 1,
            ],
            [
                'user_type' => 'delivery_man',
                'key' => 'waybill_assigned_message',
                'message' => 'Waybill Assignment: Waybill for order #{orderId} assigned for collection from {shopName}.',
                'status' => 1,
            ],

            // ==================== LOGISTICS COMPANY NOTIFICATIONS ====================
            [
                'user_type' => 'logistics_company',
                'key' => 'order_dispatched_to_company',
                'message' => 'New Logistics Dispatch: Order #{orderId} from {shopName} has been routed to {companyName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'logistics_company',
                'key' => 'waybill_routed_to_company',
                'message' => 'Waybill Transfer: Waybill batch #{batchId} has been successfully assigned to {companyName}.',
                'status' => 1,
            ],
            [
                'user_type' => 'logistics_company',
                'key' => 'rider_delivery_completed',
                'message' => 'Delivery Confirmed: Rider completed final handover for order #{orderId}.',
                'status' => 1,
            ],
            [
                'user_type' => 'logistics_company',
                'key' => 'company_withdrawal_status',
                'message' => 'Logistics Treasury: Withdrawal request for {companyName} has been processed.',
                'status' => 1,
            ],
            [
                'user_type' => 'logistics_company',
                'key' => 'rider_failed_delivery_alert',
                'message' => 'Delivery Exception: Rider reported an unsuccessful delivery attempt for order #{orderId}.',
                'status' => 1,
            ],
        ];

        foreach ($messages as $item) {
            NotificationMessage::updateOrCreate(
                [
                    'user_type' => $item['user_type'],
                    'key' => $item['key'],
                ],
                [
                    'message' => $item['message'],
                    'status' => $item['status'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}
