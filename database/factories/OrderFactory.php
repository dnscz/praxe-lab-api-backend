<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'status' => $this->faker->randomElement(OrderStatus::cases()),
            'customer_name' => $this->faker->name(),
            'customer_ico' => $this->faker->numerify('###########'),
            'customer_dic' => $this->faker->numerify('###########'),
            'contact_email' => $this->faker->email(),
            'contact_phone' => $this->faker->phoneNumber(),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional()->streetAddress(),
            'address_city' => $this->faker->city(),
            'address_country' => $this->faker->country(),
            'address_zip' => $this->faker->postcode(),
        ];
    }
}
