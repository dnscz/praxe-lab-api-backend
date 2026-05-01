<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property string $unit_price
 * @property string $line_total
 */
#[Fillable([
    'order_id',
    'product_id',
    'quantity',
])]
#[Appends([
    'line_total',
])]
final class OrderProduct extends Pivot
{
    use HasUuids;

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'string',
    ];

    /**
     * @return Attribute<float, never>
     */
    protected function lineTotal(): Attribute
    {
        return Attribute::get(
            fn () => bcmul(
                (string) $this->unit_price,
                (string) $this->quantity,
                scale: 4
            )
        );
    }
}
