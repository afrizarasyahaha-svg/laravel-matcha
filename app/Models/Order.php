<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING = 'PENDING_PAYMENT';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu Pembayaran',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'shipping_fee',
        'total_amount',
        'order_status',
    ];

    protected function casts(): array
    {
        return [
            'shipping_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->order_status] ?? $this->order_status;
    }

    public function getItemCountAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getDeliveryTypeAttribute(): string
    {
        return (float) $this->shipping_fee > 0 ? 'antar' : 'ambil';
    }

    /** Kode tampilan pesanan, mis. #MB-00012. */
    public function getCodeAttribute(): string
    {
        return '#MB-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
