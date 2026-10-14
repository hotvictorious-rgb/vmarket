<?php

namespace App\Models;

use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class Review
 *
 * @property int $id
 * @property int $product_id
 * @property int $customer_id
 * @property int|null $delivery_man_id
 * @property int|null $order_id
 * @property string|null $comment
 * @property array|null $attachment
 * @property int $rating
 * @property int $status
 * @property bool $is_saved
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 * */
class Review extends Model
{
    use StorageTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'product_id',
        'shop_id',
        'customer_id',
        'delivery_man_id',
        'order_id',
        'comment',
        'sentiment_tags',
        'attachment',
        'verified_location',
        'rating',
        'status',
        'helpful_count',
        'is_disputed',
        'dispute_reason',
        'dispute_status',
        'disputed_at',
        'is_saved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'rating' => 'integer',
        'status' => 'integer',
        'helpful_count' => 'integer',
        'is_disputed' => 'boolean',
        'disputed_at' => 'datetime',
        'is_saved' => 'boolean',
        'attachment' => 'array',
        'sentiment_tags' => 'array',
    ];

    public function scopeActive($query): mixed
    {
        return $query->where('status', 1);
    }

    public function user():HasOne
    {
        return $this->hasOne('App\User', 'id', 'customer_id');
    }

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(ReviewReply::class, 'id', 'review_id');
    }

    public function helpfulVotes()
    {
        return $this->hasMany(ReviewHelpfulVote::class, 'review_id');
    }

    /**
     * [AI] Returns authentic Nigerian sentiment quick chips grouped by category or type.
     */
    public static function getSentimentChips(?string $type = null): array
    {
        $chips = [
            'product' => [
                'positive' => [
                    '100% Original',
                    'Tested & Trusted',
                    'Neat Packaging',
                    'Long Battery Life',
                    'Exactly as Described',
                    'Heavy / Premium Material',
                ],
                'negative' => [
                    'Imitation / Fake',
                    'Weak Battery',
                    'Substandard Finish',
                    'Damaged on Arrival',
                    'Wrong Variant / Color',
                ],
            ],
            'shop' => [
                'positive' => [
                    'Fast Counter Service',
                    'Very Polite Attendant',
                    'Shop Easy to Locate',
                    'Allowed In-Store Testing',
                    'Item Held As Promised',
                ],
                'negative' => [
                    'Long Waiting Time',
                    'Unfriendly Attendant',
                    'Hard to Locate Store',
                    'Item Not Ready at Counter',
                ],
            ],
            'delivery' => [
                'positive' => [
                    'Very Fast Delivery',
                    'Courteous Rider',
                    'Clear Directions Call',
                    'Careful Parcel Handling',
                ],
                'negative' => [
                    'Late Delivery',
                    'Rude Courier',
                    'Demanded Extra Fuel / Tip',
                    'Damaged Outer Box',
                ],
            ],
        ];

        if ($type && isset($chips[$type])) {
            return $chips[$type];
        }

        return $chips;
    }

    /**
     * [AI] Resolves a human-friendly verified location context string from an order.
     */
    public static function resolveVerifiedLocation(Order $order): string
    {
        if ($order->order_type === 'pickup') {
            $shopName = $order->seller?->shop?->name ?? 'Physical Store';
            return "Verified In-Shop Pickup • {$shopName}";
        }

        $address = $order->shippingAddress ?? null;
        if ($address) {
            $city = $address->city ?? '';
            $state = $address->state?->name ?? '';
            $loc = trim("{$city}, {$state}", ", ");
            if (!empty($loc)) {
                return "Verified Buyer • {$loc}";
            }
        }

        return "Verified Buyer";
    }

    public function getAttachmentFullUrlAttribute(): array|null
    {
        $images = [];
        $value = $this->attachment;
        if ($value) {
            foreach ($value as $item) {
                $item = isset($item['file_name']) ? $item : ['file_name' => $item, 'storage' => 'public'];
                $images[] = $this->storageLink('review', $item['file_name'], $item['storage'] ?? 'public');
            }
        }
        return $images;
    }
    protected $appends = ['attachment_full_url'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('active', function (Builder $builder) {
            if (str_contains(url()->current(), url('/') . '/admin') || str_contains(url()->current(), url('/') . '/seller') || str_contains(url()->current(), url('/') . '/vendor') || str_contains(url()->current(), url('/') . '/api/v2') || str_contains(url()->current(), url('/') . '/api/v3')) {
                return $builder;
            } else {
                return $builder->where('status', 1);
            }
        });

        static::saved(function ($model) {
            cacheRemoveByType(type: 'reviews');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'reviews');
        });
    }
}
