<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id                    Customer who initiated chat
 * @property int $seller_id                  Vendor (seller) in conversation
 * @property int $delivery_man_id            Delivery personnel
 * @property int $admin_id                   Admin user
 * @property int $order_id                   Associated order (if any)
 * @property string $message                 Chat message content
 * @property bool $sent_by_customer          Message sender flag
 * @property bool $sent_by_seller            Message sender flag
 * @property bool $sent_by_delivery_man      Message sender flag
 * @property bool $sent_by_admin             Message sender flag
 * @property bool $seen_by_customer          Message read status
 * @property bool $seen_by_seller            Message read status
 * @property bool $seen_by_delivery_man      Message read status
 * @property bool $seen_by_admin             Message read status
 * @property string $chat_type               Type: 'customer_to_admin', 'customer_to_delivery', 'vendor_to_admin', 'vendor_to_delivery'
 * @property bool $is_active                 Whether chat is active based on order assignment
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Chat extends Model
{
    protected $table = 'chatting';

    protected $fillable = [
        'user_id',
        'seller_id',
        'delivery_man_id',
        'admin_id',
        'order_id',
        'message',
        'sent_by_customer',
        'sent_by_seller',
        'sent_by_delivery_man',
        'sent_by_admin',
        'seen_by_customer',
        'seen_by_seller',
        'seen_by_delivery_man',
        'seen_by_admin',
        'chat_type',
        'is_active',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'seller_id' => 'integer',
        'delivery_man_id' => 'integer',
        'admin_id' => 'integer',
        'order_id' => 'integer',
        'message' => 'string',
        'sent_by_customer' => 'boolean',
        'sent_by_seller' => 'boolean',
        'sent_by_delivery_man' => 'boolean',
        'sent_by_admin' => 'boolean',
        'seen_by_customer' => 'boolean',
        'seen_by_seller' => 'boolean',
        'seen_by_delivery_man' => 'boolean',
        'seen_by_admin' => 'boolean',
        'chat_type' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Customer relationship
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Seller/Vendor relationship
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Delivery person relationship
     */
    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    /**
     * Admin relationship
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Associated order relationship
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Chat attachments (media, files)
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ChatAttachment::class, 'chat_id');
    }

    /**
     * Scope: Active chats only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: By user (customer)
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('user_id', $customerId);
    }

    /**
     * Scope: By seller/vendor
     */
    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope: By delivery man
     */
    public function scopeByDeliveryMan($query, $deliveryManId)
    {
        return $query->where('delivery_man_id', $deliveryManId);
    }

    /**
     * Scope: By admin
     */
    public function scopeByAdmin($query)
    {
        return $query->where('admin_id', 1);
    }

    /**
     * Scope: By order
     */
    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope: Customer to Admin conversations
     */
    public function scopeCustomerToAdmin($query)
    {
        return $query->where('chat_type', 'customer_to_admin')
            ->whereNotNull('user_id')
            ->whereNotNull('admin_id');
    }

    /**
     * Scope: Customer to Delivery Person conversations
     */
    public function scopeCustomerToDelivery($query)
    {
        return $query->where('chat_type', 'customer_to_delivery')
            ->whereNotNull('user_id')
            ->whereNotNull('delivery_man_id')
            ->whereNotNull('order_id');
    }

    /**
     * Scope: Vendor to Admin conversations
     */
    public function scopeVendorToAdmin($query)
    {
        return $query->where('chat_type', 'vendor_to_admin')
            ->whereNotNull('seller_id')
            ->whereNotNull('admin_id');
    }

    /**
     * Scope: Vendor to Delivery Person conversations
     */
    public function scopeVendorToDelivery($query)
    {
        return $query->where('chat_type', 'vendor_to_delivery')
            ->whereNotNull('seller_id')
            ->whereNotNull('delivery_man_id')
            ->whereNotNull('order_id');
    }
}
