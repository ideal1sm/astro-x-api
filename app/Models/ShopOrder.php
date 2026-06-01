<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'yookassa_payment_id',
        'items_total',
        'total',
        'payment_amount',
        'payment_confirmation_url',
        'payment_initiated_at',
        'payment_paid_at',
        'payment_failure_reason',
        'payment_payload',
        'customer_name',
        'customer_phone',
        'customer_email',
        'personal_data_consent_at',
        'delivery_method',
        'delivery_price',
        'delivery_payload',
        'recipient_name',
        'recipient_phone',
        'delivery_city',
        'delivery_address',
        'delivery_pickup_point',
        'delivery_comment',
        'delivery_address_id',
        'notes',
    ];

    protected $casts = [
        'status'                   => OrderStatus::class,
        'payment_method'           => PaymentMethod::class,
        'payment_status'           => PaymentStatus::class,
        'items_total'              => 'decimal:2',
        'delivery_price'           => 'decimal:2',
        'delivery_payload'         => 'array',
        'total'                    => 'decimal:2',
        'payment_amount'           => 'decimal:2',
        'payment_payload'          => 'array',
        'personal_data_consent_at' => 'datetime',
        'payment_initiated_at'     => 'datetime',
        'payment_paid_at'          => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'delivery_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
