<?php

namespace App\Utils;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\OrderDetail;
use App\Models\SupportTicket;
use App\Models\ProductCompare;
use App\Models\RestockProduct;
use App\Models\BusinessSetting;
use App\Models\ReferralCustomer;
use Illuminate\Support\Facades\DB;

class CustomerManager
{
    public static function create_support_ticket($data)
    {
        $support = new SupportTicket();
        $support->customer_id = $data['customer_id'];
        $support->subject = $data['subject'];
        $support->type = $data['type'];
        $support->priority = $data['priority'];
        $support->description = $data['description'];
        $support->attachment = $data['attachment'];
        $support->status = $data['status'];
        $support->save();

        return $support;
    }

    public static function updateCustomerSessionData($userId): void
    {
        session()->forget('coupon_code');
        session()->forget('coupon_type');
        session()->forget('coupon_bearer');
        session()->forget('coupon_discount');
        session()->forget('payment_method');
        session()->forget('shipping_method_id');
        session()->forget('billing_address_id');
        session()->forget('order_id');
        session()->forget('cart_group_id');
        session()->forget('order_note');
        session()->forget('wish_list');
        session()->forget('compare_list');

        $compareListArray = ProductCompare::whereHas('product')->where('user_id', $userId)->pluck('product_id')->toArray();
        $wishList = Wishlist::whereHas('wishlistProduct', function ($query) {
            return $query->active();
        })->where('customer_id', $userId)->pluck('product_id')->toArray();

        session()->forget('customer_fcm_topic');
        session()->put('wish_list', $wishList);
        session()->put('compare_list', $compareListArray);


        $restockProductList = RestockProduct::whereHas('restockProductCustomers', function ($query) use ($userId) {
            return $query->where('customer_id', $userId);
        })->get();

        if ($restockProductList) {
            $customerFCMTopics = [];
            foreach ($restockProductList as $restockProduct) {
                $customerFCMTopics[] = getRestockProductFCMTopic(restockRequest: $restockProduct);
            }
            session()->put('customer_fcm_topic', $customerFCMTopics);
        }
    }

    public static function onlyUpdateCustomerCompareAndWishListSession($userId): void
    {
        $compareListArray = ProductCompare::whereHas('product')->where('user_id', $userId)->pluck('product_id')->toArray();
        $wishList = Wishlist::whereHas('wishlistProduct', function ($query) {
            return $query->active();
        })->where('customer_id', $userId)->pluck('product_id')->toArray();

        session()->put('wish_list', $wishList);
        session()->put('compare_list', $compareListArray);
    }


    public static function getReferralDiscountAmount($user = null, $couponDiscount = null)
    {
        // [AI] Victorious MARKET V1: Referral discounts do not reduce marketplace checkout totals.
        // Cashback (Victorious Points) is the sole customer order-reduction mechanism.
        return 0;
    }

}
