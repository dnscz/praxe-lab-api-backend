<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $created_by
 * @property OrderStatus $status
 * @property string $customer_name
 * @property string|null $customer_ico
 * @property string|null $customer_dic
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property string $address_line_1
 * @property string|null $address_line_2
 * @property string $address_city
 * @property string $address_country
 * @property string $address_zip
 * @property string $total_price
 */
#[Fillable([
    'created_by',
    'status',
    'customer_name',
    'customer_ico',
    'customer_dic',
    'contact_email',
    'contact_phone',
    'address_line_1',
    'address_line_2',
    'address_city',
    'address_country',
    'address_zip',
])]
#[Appends([
    'total_price',
])]
final class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity', 'unit_price', 'bulk_price');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    /**
     * @return Attribute<float, never>
     */
    protected function totalPrice(): Attribute
    {
        return Attribute::get(
            fn () => $this->products->reduce(
                fn (string $carry, Product $product): string => bcadd(
                    $carry,
                    $product->pivot->line_total,  // delegate to pivot
                    scale: 4
                ),
                '0.0000'
            )
        );
    }
}
