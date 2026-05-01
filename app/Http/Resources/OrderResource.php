<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_by' => $this->created_by,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_ico' => $this->customer_ico,
            'customer_dic' => $this->customer_dic,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'address_city' => $this->address_city,
            'address_country' => $this->address_country,
            'address_zip' => $this->address_zip,
            'total_price' => $this->total_price,
            'items' => OrderProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
