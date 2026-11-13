<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Keranjang belanja berbasis session (pengganti CartContext + localStorage di FE lama).
 * Baris keranjang digabung berdasarkan produk + varian + tingkat gula + catatan,
 * persis perilaku CartContext lama.
 */
class CartService
{
    private const KEY = 'cart';

    public function items(): array
    {
        return Session::get(self::KEY, []);
    }

    /** Baris keranjang siap tampil (dilengkapi row_id & subtotal). */
    public function rows(): array
    {
        return collect($this->items())
            ->map(function (array $item, string $rowId) {
                $item['row_id'] = $rowId;
                $item['subtotal'] = (float) $item['unit_price'] * (int) $item['qty'];

                return $item;
            })
            ->values()
            ->all();
    }

    public function count(): int
    {
        return (int) collect($this->items())->sum('qty');
    }

    public function subtotal(): float
    {
        return (float) collect($this->items())
            ->sum(fn (array $item) => (float) $item['unit_price'] * (int) $item['qty']);
    }

    public function isEmpty(): bool
    {
        return empty($this->items());
    }

    public function add(int $productId, ?int $variantId, string $sweet, string $note, int $qty = 1): void
    {
        $product = Product::with('variants')->find($productId);

        if (! $product || ! $product->is_active) {
            throw ValidationException::withMessages([
                'cart' => 'Produk tidak ditemukan atau sudah tidak aktif.',
            ]);
        }

        $variant = null;

        if ($variantId) {
            $variant = $product->variants->firstWhere('id', $variantId);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'cart' => 'Varian produk tidak ditemukan.',
                ]);
            }

            if ($variant->stock <= 0) {
                throw ValidationException::withMessages([
                    'cart' => 'Stok varian ini sedang habis.',
                ]);
            }
        }

        $qty = max(1, $qty);
        $unitPrice = (float) $product->base_price + ($variant ? (float) $variant->additional_price : 0);

        $rowId = md5($product->id.'|'.($variant?->id ?? 'null').'|'.$sweet.'|'.$note);
        $cart = $this->items();

        if (isset($cart[$rowId])) {
            $cart[$rowId]['qty'] += $qty;
        } else {
            $cart[$rowId] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name,
                'variant_name' => $variant?->variant_name,
                'tone' => $product->tone,
                'image' => $product->image,
                'sweet' => $sweet,
                'note' => $note,
                'unit_price' => $unitPrice,
                'qty' => $qty,
            ];
        }

        Session::put(self::KEY, $cart);
    }

    public function update(string $rowId, int $qty): void
    {
        $cart = $this->items();

        if (! isset($cart[$rowId])) {
            return;
        }

        if ($qty <= 0) {
            unset($cart[$rowId]);
        } else {
            $cart[$rowId]['qty'] = $qty;
        }

        Session::put(self::KEY, $cart);
    }

    public function remove(string $rowId): void
    {
        $cart = $this->items();
        unset($cart[$rowId]);
        Session::put(self::KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }
}
