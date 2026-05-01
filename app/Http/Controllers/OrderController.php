<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

final class OrderController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $query = Order::query()->where('created_by', auth()->id());

        $orders = QueryBuilder::for($query)
            ->allowedFilters('customer_name', 'status')
            ->allowedSorts('customer_name', 'status', 'created_at')
            ->allowedIncludes('created_by')
            ->paginate();

        return $this->success(OrderResource::collection($orders));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $order = $user->orders()->create(
            collect($validated)->except('products')->put('status', OrderStatus::PENDING)->all()
        );

        $productItems = $request->productItems();
        $productIds = collect($productItems)->pluck('product_id')->all();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($productItems as $item) {
            $product = $products->get($item['product_id']);
            if (! $product instanceof Product) {
                continue;
            }

            $order->products()->attach($item['product_id'], [
                'quantity' => $item['quantity'],
                'unit_price' => $product->unit_price,
            ]);
        }

        $order->load('products')->refresh();

        return $this->created(new OrderResource($order));
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return $this->success(new OrderResource($order));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $validated = $request->validated();

        $order->update(collect($validated)->except('products')->all());

        $productItems = $request->productItems();
        $productIds = collect($productItems)->pluck('product_id')->all();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $syncData = [];
        foreach ($productItems as $item) {
            $product = $products->get($item['product_id']);
            if (! $product instanceof Product) {
                continue;
            }

            $syncData[$item['product_id']] = [
                'quantity' => $item['quantity'],
                'unit_price' => $product->unit_price,
            ];
        }

        $order->products()->sync($syncData);

        $order->load('products')->refresh();

        return $this->success(new OrderResource($order));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): JsonResponse
    {
        Gate::authorize('delete', $order);

        $order->delete();

        return $this->noContent();
    }
}
