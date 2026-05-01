<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        return $user->can('create', Order::class);
    }

    /**
     * @return list<array{product_id: string, quantity: int}>
     */
    public function productItems(): array
    {
        /** @var list<array{product_id: string, quantity: int}> $items */
        $items = $this->validated('products', []);

        return $items;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_ico' => ['sometimes', 'string', 'max:40'],
            'customer_dic' => ['sometimes', 'string', 'max:40'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'string', 'max:60'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'string', 'max:255'],
            'address_city' => ['required', 'string', 'max:255'],
            'address_country' => ['required', 'string', 'max:255'],
            'address_zip' => ['required', 'string', 'max:12'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
