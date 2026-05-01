<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class OrderProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->pivot->id,
            'product_id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'unit_price' => $this->pivot->unit_price,
            'quantity' => $this->pivot->quantity,
            'line_total' => $this->pivot->line_total,
        ];
    }
}
