<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;
    
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
        'order_number',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'shipping_fee',
        'subtotal',
        'discount_amount',
        'total_amount',
        'delivery_type',
        'order_status',
        'payment_status',
        'paid_at',
        'expires_at',
        'completed_at',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipping_fee' => 'decimal:2',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->order_status] ?? $this->order_status;
    }

    public function getItemCountAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getDeliveryTypeAttribute($value): string
    {
        // Mengambil langsung dari kolom database, dengan fallback aman jika kosong
        return $value ?? 'ambil';
    }

    /** Kode tampilan pesanan, mis. #MB-00012. */
    public function getCodeAttribute(): string
    {
        return '#MB-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}