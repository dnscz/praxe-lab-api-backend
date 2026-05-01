<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderProductFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $line_total
 */
#[Fillable([
    'order_id',
    'product_id',
    'quantity',
    'unit_price',
])]
#[Appends([
    'line_total',
])]
final class OrderProduct extends Pivot
{
    /** @use HasFactory<OrderProductFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'string',
    ];

    /**
     * @return Attribute<numeric-string, never>
     */
    protected function lineTotal(): Attribute
    {
        return Attribute::get(
            fn (): string => bcmul(
                $this->unit_price,
                (string) $this->quantity,
                scale: 4
            )
        );
    }
}
