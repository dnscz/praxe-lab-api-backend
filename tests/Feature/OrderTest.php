<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$orderPayload = fn (array $productIds): array => [
    'customer_name' => 'Acme Corp',
    'customer_ico' => '12345678',
    'customer_dic' => 'CZ12345678',
    'contact_email' => 'order@acme.com',
    'contact_phone' => '+420123456789',
    'address_line_1' => 'Main Street 1',
    'address_city' => 'Prague',
    'address_country' => 'Czech Republic',
    'address_zip' => '11000',
    'products' => collect($productIds)->map(fn ($id): array => [
        'product_id' => $id,
        'quantity' => 2,
    ])->all(),
];

describe('Order index', function () use ($orderPayload): void {
    it('returns paginated orders for authenticated user', function () use ($orderPayload): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/orders', $orderPayload([$product->id]));

        $response = $this->actingAs($user)->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('does not return orders belonging to other users', function () use ($orderPayload): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($other)->postJson('/api/v1/orders', $orderPayload([$product->id]));

        $response = $this->actingAs($user)->getJson('/api/v1/orders');

        $response->assertOk()->assertJsonCount(0, 'data');
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    });
});

describe('Order store', function () use ($orderPayload): void {
    it('creates an order with products', function () use ($orderPayload): void {
        $user = User::factory()->create();
        $product = Product::factory()->create(['unit_price' => '99.99']);

        $response = $this->actingAs($user)->postJson('/api/v1/orders', $orderPayload([$product->id]));

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'status', 'customer_name', 'total_price', 'items'],
            ])
            ->assertJsonPath('data.status', OrderStatus::PENDING->value)
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonStructure(['data' => ['items' => [['unit_price', 'quantity', 'line_total']]]]);

        $this->assertDatabaseHas('orders', [
            'created_by' => $user->id,
            'customer_name' => 'Acme Corp',
        ]);
    });

    it('fails validation with no products', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/orders', [
            'customer_name' => 'Acme Corp',
            'contact_email' => 'order@acme.com',
            'address_line_1' => 'Main Street 1',
            'address_city' => 'Prague',
            'address_country' => 'Czech Republic',
            'address_zip' => '11000',
            'products' => [],
        ])->assertUnprocessable();
    });

    it('requires authentication', function (): void {
        $this->postJson('/api/v1/orders', [])->assertUnauthorized();
    });
});

describe('Order show', function (): void {
    it('returns the order for its owner', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user, 'createdBy')->create();

        $this->actingAs($user)->getJson('/api/v1/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);
    });

    it("forbids viewing another user's order", function (): void {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->for($owner, 'createdBy')->create();

        $this->actingAs($other)->getJson('/api/v1/orders/'.$order->id)
            ->assertForbidden();
    });
});

describe('Order update', function () use ($orderPayload): void {
    it('updates order fields on a pending order', function () use ($orderPayload): void {
        $user = User::factory()->create();
        $product = Product::factory()->create(['unit_price' => '50.00']);
        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::PENDING]);
        $order->products()->attach($product->id, ['quantity' => 1, 'unit_price' => '50.00']);

        $newProduct = Product::factory()->create(['unit_price' => '25.00']);
        $payload = $orderPayload([$newProduct->id]);
        $payload['customer_name'] = 'Updated Corp';

        $response = $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, $payload);

        $response->assertOk()
            ->assertJsonPath('data.customer_name', 'Updated Corp')
            ->assertJsonPath('data.items.0.product_id', $newProduct->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'Updated Corp',
        ]);
    });

    it('replaces all products — removed products are detached', function () use ($orderPayload): void {
        $user = User::factory()->create();
        [$productA, $productB] = Product::factory()->count(2)->create(['unit_price' => '10.00']);
        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::PENDING]);
        $order->products()->attach($productA->id, ['quantity' => 1, 'unit_price' => '10.00']);
        $order->products()->attach($productB->id, ['quantity' => 2, 'unit_price' => '10.00']);

        $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, $orderPayload([$productB->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $productB->id);

        $this->assertDatabaseMissing('order_products', ['order_id' => $order->id, 'product_id' => $productA->id]);
        $this->assertDatabaseHas('order_products', ['order_id' => $order->id, 'product_id' => $productB->id]);
    });

    it('updates quantity for an existing product', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create(['unit_price' => '10.00']);
        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::PENDING]);
        $order->products()->attach($product->id, ['quantity' => 1, 'unit_price' => '10.00']);

        $payload = [
            'customer_name' => 'Acme Corp',
            'contact_email' => 'order@acme.com',
            'address_line_1' => 'Main Street 1',
            'address_city' => 'Prague',
            'address_country' => 'Czech Republic',
            'address_zip' => '11000',
            'products' => [['product_id' => $product->id, 'quantity' => 5]],
        ];

        $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, $payload)
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 5);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    });

    it('snapshots current product unit_price at update time', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create(['unit_price' => '10.00']);
        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::PENDING]);
        $order->products()->attach($product->id, ['quantity' => 1, 'unit_price' => '10.00']);

        $product->update(['unit_price' => '99.00']);

        $payload = [
            'customer_name' => 'Acme Corp',
            'contact_email' => 'order@acme.com',
            'address_line_1' => 'Main Street 1',
            'address_city' => 'Prague',
            'address_country' => 'Czech Republic',
            'address_zip' => '11000',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, $payload)
            ->assertOk();

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'unit_price' => '99.00',
        ]);
    });

    it('recalculates total_price after product sync', function (): void {
        $user = User::factory()->create();
        [$productA, $productB] = Product::factory()->count(2)->sequence(
            ['unit_price' => '10.00'],
            ['unit_price' => '5.00'],
        )->create();

        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::PENDING]);
        $order->products()->attach($productA->id, ['quantity' => 2, 'unit_price' => '10.00']); // 20.00
        $order->products()->attach($productB->id, ['quantity' => 3, 'unit_price' => '5.00']);  // 15.00

        $payload = [
            'customer_name' => 'Acme Corp',
            'contact_email' => 'order@acme.com',
            'address_line_1' => 'Main Street 1',
            'address_city' => 'Prague',
            'address_country' => 'Czech Republic',
            'address_zip' => '11000',
            'products' => [['product_id' => $productA->id, 'quantity' => 1]], // 10.00 only
        ];

        $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, $payload)
            ->assertOk()
            ->assertJsonPath('data.total_price', '10.0000');
    });

    it('handles multiple products in a single update', function (): void {
        $user = User::factory()->create();
        [$productA, $productB, $productC] = Product::factory()->count(3)->sequence(
            ['unit_price' => '1.00'],
            ['unit_price' => '2.00'],
            ['unit_price' => '3.00'],
        )->create();

        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::PENDING]);
        $order->products()->attach($productA->id, ['quantity' => 1, 'unit_price' => '1.00']);

        $payload = [
            'customer_name' => 'Acme Corp',
            'contact_email' => 'order@acme.com',
            'address_line_1' => 'Main Street 1',
            'address_city' => 'Prague',
            'address_country' => 'Czech Republic',
            'address_zip' => '11000',
            'products' => [
                ['product_id' => $productB->id, 'quantity' => 2],
                ['product_id' => $productC->id, 'quantity' => 3],
            ],
        ];

        $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, $payload)
            ->assertOk()
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseMissing('order_products', ['order_id' => $order->id, 'product_id' => $productA->id]);
        $this->assertDatabaseHas('order_products', ['order_id' => $order->id, 'product_id' => $productB->id, 'quantity' => 2]);
        $this->assertDatabaseHas('order_products', ['order_id' => $order->id, 'product_id' => $productC->id, 'quantity' => 3]);
    });

    it('forbids updating a non-pending order', function () use ($orderPayload): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::CONFIRMED]);

        $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, $orderPayload([$product->id]))
            ->assertForbidden();
    });

    it("forbids updating another user's order", function () use ($orderPayload): void {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->for($owner, 'createdBy')->create(['status' => OrderStatus::PENDING]);

        $this->actingAs($other)->putJson('/api/v1/orders/'.$order->id, $orderPayload([$product->id]))
            ->assertForbidden();
    });
});

describe('Order destroy', function (): void {
    it('deletes a pending order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::PENDING]);

        $this->actingAs($user)->deleteJson('/api/v1/orders/'.$order->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    });

    it('forbids deleting a non-pending order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user, 'createdBy')->create(['status' => OrderStatus::SHIPPED]);

        $this->actingAs($user)->deleteJson('/api/v1/orders/'.$order->id)
            ->assertForbidden();
    });
});
