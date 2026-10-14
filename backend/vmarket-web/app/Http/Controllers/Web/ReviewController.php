<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Traits\FileManagerTrait;
use App\Utils\ImageManager;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use function React\Promise\all;

class ReviewController extends Controller
{
    use FileManagerTrait;

    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepo,
        private readonly OrderRepositoryInterface  $orderRepo,
    )
    {
    }

    public function add(Request $request): RedirectResponse
    {
        $request->validate([
            'rating' => 'required',
            'comment' => 'required',
            'order_id' => 'required',
            'product_id' => 'required',
        ], [
            'rating.required' => translate('please_rate_the_quality') . '!',
            'comment.required' => translate('The_comment_is_required') . '!',
        ]);

        $customerId = auth('customer')->id();
        if (!$customerId) {
            Toastr::error(translate('Please_log_in_to_submit_a_product_review'));
            return redirect()->route('customer.auth.login');
        }

        // [AI] Verified Buyer Gate: Customer must have purchased and received this product in a delivered order
        $order = $this->orderRepo->getFirstWhere(params: [
            'id' => $request['order_id'],
            'customer_id' => $customerId,
            'order_status' => 'delivered'
        ]);
        if (!$order) {
            Toastr::error(translate('Only_customers_who_have_purchased_and_received_this_product_can_leave_a_review'));
            return redirect()->back();
        }

        $orderDetail = \App\Models\OrderDetail::where(['order_id' => $request['order_id'], 'product_id' => $request['product_id']])->first();
        if (!$orderDetail) {
            Toastr::error(translate('product_not_found_in_order'));
            return redirect()->back();
        }

        if ($request->has('fileUpload')) {
            foreach ($request->file('fileUpload') as $image) {
                $extension = $image->getClientOriginalExtension();
                if(in_array($extension, getDisallowedExtensionsListArray())){
                    Toastr::error(translate('Invalid_file_format_given_').$extension);
                    return redirect()->back();
                }
            }
        }

        $imageArray = [];
        $review = null;
        if ($request['review_id']) {
            // [AI] Ownership Guard: Review being edited must belong to the authenticated customer
            $review = $this->reviewRepo->getFirstWhere(params: ['customer_id' => $customerId, 'id' => $request['review_id']]);
            if (!$review) {
                Toastr::error(translate('unauthorized_access'));
                return redirect()->back();
            }
            if ($review['attachment']) {
                foreach ($review['attachment'] as $image) {
                    $imageArray[] = $image;
                }
            }
        }

        if ($request->has('fileUpload')) {
            foreach ($request->file('fileUpload') as $image) {
                $imageArray[] = [
                    'file_name' => $this->upload(dir: 'review/', format: 'webp', image: $image),
                    'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
                ];
            }
        }

        if ($request['review_id']) {
            $reviewId = $request['review_id'];
        } else {
            $oldReview = $this->reviewRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['order_id' => $request['order_id']]);
            $reviewId = $request['order_id'] . (count($oldReview) < 10 ? '0' . (count($oldReview) + 1) : (count($oldReview) + 1));
        }

        $sentimentTags = is_array($request['sentiment_tags'])
            ? $request['sentiment_tags']
            : (is_string($request['sentiment_tags']) ? explode(',', $request['sentiment_tags']) : null);
        $verifiedLocation = Review::resolveVerifiedLocation($order);

        $dataArray = [
            'id' => $reviewId,
            'customer_id' => $customerId,
            'product_id' => $request['product_id'],
            'order_id' => $request['order_id'],
            'comment' => $request['comment'],
            'rating' => $request['rating'],
            'sentiment_tags' => $sentimentTags,
            'verified_location' => $verifiedLocation,
            'attachment' => $request->has('fileUpload') ? $imageArray : ($review->attachment ?? null),
            'status' => 1,
            'updated_at' => now(),
            'created_at' => $review->created_at ?? now()
        ];

        if ($request['review_id']) {
            $this->reviewRepo->update(id: $request['review_id'], data: $dataArray);
        } else {
            $this->reviewRepo->add(data: $dataArray);
        }

        Toastr::success(translate('successfully_added_review'));
        return redirect()->back();
    }

    public function addDeliveryManReview(Request $request): RedirectResponse
    {
        $order = $this->orderRepo->getFirstWhere(params: ['id' => $request['order_id'], 'customer_id' => auth('customer')->id(), 'payment_status' => 'paid']);
        if (!isset($order->delivery_man_id)) {
            Toastr::error(translate('Invalid_review'));
            return redirect('/');
        }

        if ($request['rating'] == 0) {
            Toastr::error(translate('please_select_ratting'));
            return back();
        }

        $review = $this->reviewRepo->getFirstWhere(params: [
            'delivery_man_id' => $order['delivery_man_id'],
            'customer_id' => auth('customer')->id(),
            'order_id' => $request['order_id'],
        ]);

        $sentimentTags = is_array($request['sentiment_tags'])
            ? $request['sentiment_tags']
            : (is_string($request['sentiment_tags']) ? explode(',', $request['sentiment_tags']) : null);
        $verifiedLocation = Review::resolveVerifiedLocation($order);

        $dataArray = [
            'customer_id' => auth('customer')->id(),
            'delivery_man_id' => $order['delivery_man_id'],
            'order_id' => $request['order_id'],
            'comment' => $request['comment'],
            'rating' => $request['rating'],
            'sentiment_tags' => $sentimentTags,
            'verified_location' => $verifiedLocation,
            'status' => 1,
            'updated_at' => now(),
        ];

        if (!$review) {
            $dataArray['created_at'] = now();
        }

        $this->reviewRepo->updateOrInsert(params: [
            'delivery_man_id' => $order['delivery_man_id'],
            'customer_id' => auth('customer')->id(),
            'order_id' => $request['order_id']
        ], data: $dataArray
        );

        Toastr::success(translate('successfully_added_review'));
        return back();
    }

    /**
     * [AI] Submit in-shop counter pickup review from web storefront.
     */
    public function addShopReview(Request $request): RedirectResponse
    {
        $request->validate([
            'order_id' => 'required',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $customerId = auth('customer')->id();
        $order = $this->orderRepo->getFirstWhere(params: [
            'id' => $request['order_id'],
            'customer_id' => $customerId,
        ]);
        if (!$order) {
            Toastr::error(translate('order_not_found'));
            return back();
        }

        $shopId = $order->seller?->shop?->id ?? $request['shop_id'];
        if (!$shopId) {
            Toastr::error(translate('Shop_not_found'));
            return back();
        }

        $sentimentTags = is_array($request['sentiment_tags'])
            ? $request['sentiment_tags']
            : (is_string($request['sentiment_tags']) ? explode(',', $request['sentiment_tags']) : null);
        $verifiedLocation = Review::resolveVerifiedLocation($order);

        $oldReview = Review::where(['order_id' => $order->id, 'shop_id' => $shopId])->first();
        if ($oldReview) {
            $oldReview->update([
                'rating' => $request['rating'],
                'comment' => $request['comment'] ?? $oldReview->comment,
                'sentiment_tags' => $sentimentTags ?? $oldReview->sentiment_tags,
                'verified_location' => $verifiedLocation,
            ]);
        } else {
            Review::create([
                'id' => $order->id . '9',
                'customer_id' => $customerId,
                'order_id' => $order->id,
                'shop_id' => $shopId,
                'rating' => $request['rating'],
                'comment' => $request['comment'],
                'sentiment_tags' => $sentimentTags,
                'verified_location' => $verifiedLocation,
                'status' => 1,
            ]);
        }

        Toastr::success(translate('successfully_added_store_review'));
        return back();
    }

    /**
     * [AI] Helpful vote handler for web storefront.
     */
    public function voteHelpful(Request $request, $reviewId): JsonResponse
    {
        $review = Review::active()->where('id', $reviewId)->first();
        if (!$review) {
            return response()->json(['status' => false, 'message' => translate('Review_not_found')], 404);
        }

        $customerId = auth('customer')->id();
        $ip = $request->ip();

        $existing = \App\Models\ReviewHelpfulVote::where('review_id', $reviewId)
            ->where(function ($q) use ($customerId, $ip) {
                if ($customerId) {
                    $q->where('customer_id', $customerId);
                } else {
                    $q->where('ip_address', $ip);
                }
            })->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => translate('You_have_already_marked_this_review_as_helpful'),
                'helpful_count' => $review->helpful_count,
            ], 200);
        }

        \App\Models\ReviewHelpfulVote::create([
            'review_id' => $reviewId,
            'customer_id' => $customerId,
            'ip_address' => $ip,
        ]);

        $review->increment('helpful_count');

        return response()->json([
            'status' => true,
            'message' => translate('Marked_as_helpful'),
            'helpful_count' => $review->fresh()->helpful_count,
        ], 200);
    }

    public function deleteReviewImage(Request $request): JsonResponse
    {
        // [AI] Ownership Guard: Only allow deleting images on customer's own review
        $review = Review::where('id', $request['id'])->where('customer_id', auth('customer')->id())->first();
        if (!$review) {
            return response()->json(['error' => translate('unauthorized_access')], 403);
        }
        $array = [];

        foreach ($review['attachment'] as $image) {
            $imageName = isset($image['file_name']) ? $image['file_name'] : $image;
            if ($imageName != $request['name']) {
                $array[] = $image;
            } else {
                $this->delete('review/' . $request['name']);
            }
        }

        $review->attachment = $array;
        $review->save();
        return response()->json(['message' => translate('review_image_removed_successfully')], 200);
    }

}
