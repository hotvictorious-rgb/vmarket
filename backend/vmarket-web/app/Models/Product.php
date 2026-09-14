<?php

namespace App\Models;

use App\Traits\CacheManagerTrait;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\TaxModule\app\Models\Taxable;

/**
 * @property int $user_id
 * @property int $shop_id
 * @property string $added_by
 * @property string $name
 * @property string $code
 * @property string $slug
 * @property int $category_id
 * @property int $sub_category_id
 * @property int $sub_sub_category_id
 * @property int $brand_id
 * @property string $unit
 * @property string $digital_product_type
 * @property string $product_type
 * @property string $details
 * @property int $min_qty
 * @property int $published
 * @property float $tax
 * @property string $tax_type
 * @property string $tax_model
 * @property float $unit_price
 * @property int $status
 * @property float $discount
 * @property int $current_stock
 * @property int $minimum_order_qty
 * @property int $free_shipping
 * @property int $request_status
 * @property int $featured_status
 * @property int $refundable
 * @property int $featured
 * @property int $flash_deal
 * @property int $seller_id
 * @property float $purchase_price
 * @property string $denied_note
 * @property float $shipping_cost
 * @property int $multiply_qty
 * @property float $temp_shipping_cost
 * @property string $thumbnail
 * @property string $thumbnail_storage_type
 * @property string $preview_file
 * @property string $preview_file_storage_type
 * @property string $digital_file_ready
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_image
 * @property int $is_shipping_cost_updated
 */
class Product extends Model
{
    use StorageTrait, CacheManagerTrait;

    protected $fillable = [
        'user_id',
        'shop_id',
        'added_by',
        'name',
        'code',
        'slug',
        'category_ids',
        'category_id',
        'sub_category_id',
        'sub_sub_category_id',
        'brand_id',
        'unit',
        'digital_product_type',
        'product_type',
        'details',
        'colors',
        'choice_options',
        'variation',
        'specifications',
        'digital_product_file_types',
        'digital_product_extensions',
        'unit_price',
        'purchase_price',
        'tax',
        'tax_type',
        'tax_model',
        'discount',
        'discount_type',
        'attributes',
        'current_stock',
        'minimum_order_qty',
        'video_provider',
        'video_url',
        'status',
        'featured_status',
        'featured',
        'request_status',
        'denied_note',
        'shipping_cost',
        'multiply_qty',
        'color_image',
        'images',
        'thumbnail',
        'thumbnail_storage_type',
        'preview_file',
        'preview_file_storage_type',
        'digital_file_ready',
        'meta_title',
        'meta_description',
        'meta_image',
        'digital_file_ready_storage_type',
        'is_shipping_cost_updated',
        'temp_shipping_cost',
        'price_updated_at',
        'price_expiry_notified_at',
        'deactivation_reason',
        'gtin',
        'mpn',
        'google_category_id',
        'marketplace_listing_status',
        'marketplace_availability',
        'marketplace_confirmed_at',
        'availability_confirmed_at',
        'availability_expires_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'user_id' => 'integer',
        'shop_id' => 'integer',
        'added_by' => 'string',
        'name' => 'string',
        'code' => 'string',
        'slug' => 'string',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'sub_sub_category_id' => 'integer',
        'brand_id' => 'integer',
        'unit' => 'string',
        'digital_product_type' => 'string',
        'product_type' => 'string',
        'details' => 'string',
        'min_qty' => 'integer',
        'published' => 'integer',
        'tax' => 'float',
        'tax_type' => 'string',
        'tax_model' => 'string',
        'unit_price' => 'float',
        'status' => 'integer',
        'discount' => 'float',
        'current_stock' => 'integer',
        'minimum_order_qty' => 'integer',
        'free_shipping' => 'integer',
        'request_status' => 'integer',
        'featured_status' => 'integer',
        'refundable' => 'integer',
        'featured' => 'integer',
        'flash_deal' => 'integer',
        'seller_id' => 'integer',
        'purchase_price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'denied_note' => 'string',
        'shipping_cost' => 'float',
        'multiply_qty' => 'integer',
        'temp_shipping_cost' => 'float',
        'thumbnail' => 'string',
        'preview_file' => 'string',
        'digital_file_ready' => 'string',
        'meta_title' => 'string',
        'meta_description' => 'string',
        'meta_image' => 'string',
        'is_shipping_cost_updated' => 'integer',
        'specifications' => 'array',
        'digital_product_file_types' => 'array',
        'digital_product_extensions' => 'array',
        'thumbnail_storage_type' => 'string',
        'digital_file_ready_storage_type' => 'string',
        'marketplace_confirmed_at'  => 'datetime',
        // [AI] Canonical availability lifecycle casts
        'availability_confirmed_at' => 'datetime',
        'availability_expires_at'   => 'datetime',
    ];

    protected $appends = ['is_shop_temporary_close', 'thumbnail_full_url', 'preview_file_full_url', 'color_images_full_url', 'meta_image_full_url', 'images_full_url', 'digital_file_ready_full_url'];

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Models\Translation', 'translationable');
    }

    public function scopeActive($query)
    {
        $brandSetting = getWebConfig(name: 'product_brand');
        $digitalProductSetting = getWebConfig(name: 'digital_product');
        $businessMode = getWebConfig(name: 'business_mode');
        $productType = $digitalProductSetting ? ['digital', 'physical'] : ['physical'];

        return $query->when($businessMode == 'single', function ($query) {
                $query->where(['added_by' => 'admin']);
            })
            ->when($brandSetting, function ($query) use ($brandSetting, $productType) {
                if (!in_array('digital', $productType)) {
                    $query->whereHas('brand', function ($query) {
                        $query->where('status', 1);
                    })->orWhere(function ($query) {
                        $query->whereNull('brand_id')->where('status', 1);
                    });
                }
            })
            ->when(!$brandSetting, function ($query) {
                $query->whereNull('brand_id')->where('status', 1);
            })
            ->where(['status' => 1])
            ->where(['request_status' => 1])
            ->SellerApproved()
            ->whereIn('product_type', $productType);
    }

    public function scopeSellerApproved($query): void
    {
        $query->whereHas('seller', function ($query) {
            $query->where(['status' => 'approved']);
        })->orWhere(function ($query) {
            $query->where(['added_by' => 'admin', 'status' => 1]);
        });
    }

    /**
     * [AI] Single Canonical Marketplace Eligibility Rule:
     * Product Active AND Seller Approved AND Marketplace Approved AND Listed AND Fresh Confirmation.
     */
    public function scopeMarketplaceEligible(Builder $query): Builder
    {
        $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;

        return $query->where('status', 1)
            ->where('request_status', 1)
            ->where('marketplace_listing_status', 'listed')
            ->where(function ($q) use ($confirmationDays) {
                // Admin products are exempt from periodic seller freshness confirmation
                $q->where('added_by', 'admin')
                  ->orWhere(function ($sellerQuery) use ($confirmationDays) {
                      $sellerQuery->where('added_by', 'seller')
                          ->whereNotNull('marketplace_confirmed_at')
                          ->where('marketplace_confirmed_at', '>=', now()->subDays($confirmationDays));
                  });
            })
            ->where(function ($q) {
                // Admin products are platform-owned; seller products must have both status='approved' AND marketplace_status='approved'
                $q->where('added_by', 'admin')
                  ->orWhereHas('seller', function ($sellerSubQuery) {
                      $sellerSubQuery->where('status', 'approved')
                                     ->where('marketplace_status', 'approved');
                  });
            });
    }

    /**
     * [AI] Marketplace Purchasability: Marketplace Eligible AND In Stock AND Not Expired.
     *
     * Admin products are permanently exempt from availability expiry.
     * Seller products must have availability_expires_at in the future (or
     * fall back to marketplace_confirmed_at + configured days for backcompat).
     */
    public function scopeMarketplacePurchasable(Builder $query): Builder
    {
        return $query->marketplaceEligible()
            ->where('marketplace_availability', 'in_stock')
            ->where(function ($q) {
                // [AI] Admin products: no expiry constraint
                $q->where('added_by', 'admin')
                  ->orWhere(function ($sellerQ) {
                      // [AI] Seller products: pre-calculated expiry must be in the future
                      $sellerQ->where('added_by', 'seller')
                               ->where('availability_expires_at', '>', now());
                  });
            });
    }

    /**
     * [AI] Helper to check whether product is fresh according to admin configuration.
     */
    public function isMarketplaceFresh(): bool
    {
        if ($this->added_by === 'admin') {
            return true;
        }
        if (empty($this->marketplace_confirmed_at)) {
            return false;
        }
        $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
        return $this->marketplace_confirmed_at->isAfter(now()->subDays($confirmationDays));
    }

    /**
     * [AI] Helper to check if seller is approved and marketplace approved.
     */
    public function isSellerMarketplaceApproved(): bool
    {
        if ($this->added_by === 'admin') {
            return true;
        }
        $seller = $this->seller;
        return $seller && $seller->status === 'approved' && $seller->marketplace_status === 'approved';
    }

    /**
     * [AI] Helper to check marketplace eligibility on loaded model.
     */
    public function isMarketplaceEligible(): bool
    {
        return (int)$this->status === 1
            && (int)$this->request_status === 1
            && $this->marketplace_listing_status === 'listed'
            && $this->isMarketplaceFresh()
            && $this->isSellerMarketplaceApproved();
    }

    /**
     * [AI] Authoritative runtime marketplace purchasability gate.
     *
     * INVARIANT: A product being displayed as available in the UI does NOT
     * authorize purchase. This method is re-evaluated inside the database
     * transaction at order generation time (see OrderManager::generateOrder).
     *
     * - Eligibility: status=1, request_status=1, marketplace_listing_status='listed',
     *   seller approved+marketplace_approved.
     * - In-stock: marketplace_availability = 'in_stock'.
     * - Freshness: availability_expires_at is in the future (seller products only).
     *   Admin products are permanently exempt from freshness expiry.
     * - Fallback: If availability_expires_at is null but availability_confirmed_at
     *   exists, compute on-the-fly (handles edge cases before backfill completes).
     */
    public function isMarketplacePurchasable(): bool
    {
        if (!$this->isMarketplaceEligible()) {
            return false;
        }
        if ($this->marketplace_availability !== 'in_stock') {
            return false;
        }
        // [AI] Admin products are platform-owned — permanently purchasable if eligible
        if ($this->added_by === 'admin') {
            return true;
        }
        // [AI] Seller products: enforce freshness via pre-calculated availability_expires_at
        if ($this->availability_expires_at) {
            return $this->availability_expires_at->isFuture();
        }
        // [AI] Fallback: calculate expiry on-the-fly (handles pre-backfill rows)
        if ($this->availability_confirmed_at) {
            $days = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
            return $this->availability_confirmed_at->copy()->addDays($days)->isFuture();
        }
        // [AI] No confirmation at all → reject purchase
        return false;
    }

    /**
     * [AI] Days remaining until this product's marketplace availability expires.
     * Admin products return 999 (exempt). Seller products use availability_expires_at
     * (preferred) or fall back to marketplace_confirmed_at + N days.
     */
    public function getDaysUntilMarketplaceExpiryAttribute(): int
    {
        if ($this->added_by === 'admin') {
            return 999;
        }
        // [AI] Use pre-calculated expiry field (canonical)
        if ($this->availability_expires_at) {
            $diff = (int)now()->diffInDays($this->availability_expires_at, false);
            return max(0, $diff);
        }
        // [AI] Fallback: legacy marketplace_confirmed_at + configured window
        if (!empty($this->marketplace_confirmed_at)) {
            $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
            $expiryDate = $this->marketplace_confirmed_at->copy()->addDays($confirmationDays);
            $diff = (int)now()->diffInDays($expiryDate, false);
            return max(0, $diff);
        }
        return 0;
    }


    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function clearanceSale(): HasOne
    {
        return $this->hasOne(StockClearanceProduct::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    //old relation: reviews_by_customer
    public function reviewsByCustomer(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id')->where('customer_id', auth('customer')->id())->whereNotNull('product_id')->whereNull('delivery_man_id');
    }

    public function digitalProductAuthors(): HasMany
    {
        return $this->hasMany(DigitalProductAuthor::class, 'product_id');
    }

    public function digitalProductPublishingHouse(): HasMany
    {
        return $this->hasMany(DigitalProductPublishingHouse::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function refundRequest(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'product_id', 'id');
    }

    public function scopeStatus($query): Builder
    {
        return $query->where('featured_status', 1);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'seller_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'user_id');
    }

    public function getIsShopTemporaryCloseAttribute($value): int
    {
        $inHouseTemporaryClose = Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS) ?? 0;
        if ($this->added_by == 'admin') {
            return $inHouseTemporaryClose ?? 0;
        } elseif ($this->added_by == 'seller') {
            return Cache::remember('product-shop-close-' . $this->id, 3600, function () {
                return $this?->seller?->shop?->temporary_close ?? 0;
            });
        }
        return 0;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    //old relation: sub_category
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    //old relation: sub_sub_category
    public function subSubCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_sub_category_id');
    }

    public function rating(): HasMany
    {
        return $this->hasMany(Review::class)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->whereNull('delivery_man_id')
            ->groupBy('product_id');
    }

    //old relation: order_details
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }

    public function seoInfo(): BelongsTo
    {
        return $this->belongsTo(ProductSeo::class, 'id', 'product_id');
    }

    //old relation: order_delivered
    public function orderDelivered(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'product_id')
            ->where('delivery_status', 'delivered');

    }

    //old relation: wish_list
    public function wishList(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }

    public function digitalVariation(): HasMany
    {
        return $this->hasMany(DigitalProductVariation::class, 'product_id');
    }

    public function tags(): BelongsToMany
    {
        if (strpos(url()->current(), '/api')) {
            return $this->belongsToMany(Tag::class)->limit(5);
        }
        return $this->belongsToMany(Tag::class);
    }

    public function taxVats(): MorphMany
    {
        return $this->morphMany(Taxable::class, 'taxable');
    }

    //old relation: flash_deal_product
    public function flashDealProducts(): HasMany
    {
        return $this->hasMany(FlashDealProduct::class);
    }

    public function scopeFlashDeal($query, $flashDealID)
    {
        return $query->whereHas('flashDealProducts.flashDeal', function ($query) use ($flashDealID) {
            return $query->where('id', $flashDealID);
        });
    }

    //old relation: compare_list
    public function compareList(): HasMany
    {
        return $this->hasMany(ProductCompare::class);
    }

    public function getNameAttribute($name): string|null
    {
        $segment = request()->segment(1);
        if ($segment === 'api') {
            return $this->translations[0]->value ?? $name;
        }
        if (in_array($segment, ['admin', 'vendor', 'seller'], true)) {
            return $name;
        }
        return $this->translations[0]->value ?? $name;
    }

    public function getDetailsAttribute($detail): string|null
    {
        $segment = request()->segment(1);
        if ($segment === 'api') {
            return $this->translations[1]->value ?? $detail;
        }
        if (in_array($segment, ['admin', 'vendor', 'seller'], true)) {
            return $detail;
        }
        return $this->translations[1]->value ?? $detail;
    }

    public function getThumbnailFullUrlAttribute(): string|null|array
    {
        $value = $this->thumbnail;
        return $this->storageLink('product/thumbnail', $value, $this->thumbnail_storage_type ?? 'public');
    }

    public function getPreviewFileFullUrlAttribute(): string|null|array
    {
        $value = $this->preview_file;
        return $this->storageLink('product/preview', $value, $this->preview_file_storage_type ?? 'public');
    }

    public function getMetaImageFullUrlAttribute(): array
    {
        $value = $this->meta_image;
        return $this->storageLink('product/meta', $value, 'public');
    }

    public function getDigitalFileReadyFullUrlAttribute(): array
    {
        $value = $this->digital_file_ready;
        return $this->storageLink('product/digital-product', $value, $this->digital_file_ready_storage_type ?? 'public');
    }

    public function getColorImagesFullUrlAttribute(): array
    {
        $images = [];
        $value = is_array($this->color_image) ? $this->color_image : json_decode($this->color_image);
        if ($value) {
            foreach ($value as $item) {
                $item = (array)$item;
                $images[] = [
                    'color' => $item['color'],
                    'image_name' => $this->storageLink('product', $item['image_name'], $item['storage'] ?? 'public')
                ];
            }
        }
        return $images;
    }

    public function getImagesFullUrlAttribute(): array
    {
        $images = [];
        $value = is_array($this->images) ? $this->images : json_decode($this->images);
        if ($value) {
            foreach ($value as $item) {
                $item = isset($item->image_name) ? (array)$item : ['image_name' => $item, 'storage' => 'public'];
                $images[] = $this->storageLink('product', $item['image_name'], $item['storage'] ?? 'public');
            }
        }
        return $images;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-populate SEO/Meta fields from product details if not specified
            if (empty($model->meta_title)) {
                $pName = $model->name;
                if (is_array($pName)) {
                    $pName = $pName[array_search('en', request()->input('lang', []))] ?? 'Product';
                } elseif (is_string($pName) && strpos($pName, '[') !== false) {
                    $decoded = json_decode($pName, true);
                    if (is_array($decoded)) {
                        $pName = $decoded[0]['value'] ?? 'Product';
                    }
                }
                $model->meta_title = is_string($pName) ? substr(trim(strip_tags($pName)), 0, 100) : 'Product';
            }

            if (empty($model->meta_description)) {
                $pDesc = $model->details;
                if (is_array($pDesc)) {
                    $pDesc = $pDesc[array_search('en', request()->input('lang', []))] ?? '';
                } elseif (is_string($pDesc) && strpos($pDesc, '[') !== false) {
                    $decoded = json_decode($pDesc, true);
                    if (is_array($decoded)) {
                        $pDesc = $decoded[0]['value'] ?? '';
                    }
                }
                $model->meta_description = is_string($pDesc) ? substr(trim(strip_tags($pDesc)), 0, 160) : '';
            }

            if (empty($model->meta_image)) {
                $model->meta_image = $model->thumbnail;
            }


        });

        static::saved(function ($model) {
            cacheRemoveByType(type: 'products');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'products');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                if (strpos(url()->current(), '/api')) {
                    return $query->where('locale', App::getLocale());
                } else {
                    return $query->where('locale', getDefaultLanguage());
                }
            }, 'reviews' => function ($query) {
                $segment = request()->segment(1);
                $query->whereNull('delivery_man_id')->when(!in_array($segment, ['admin', 'vendor', 'seller'], true), function ($query) use ($segment) {
                    return $query->active();
                });
            }]);
        });
    }
}
