<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
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
            ->allowedFilters('name', 'unit_price')
            ->allowedSorts('name', 'unit_price')
            ->allowedIncludes('created_by')
            ->paginate();

        return $this->success(new OrderResource($orders));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $append = [
            'status' => OrderStatus::PENDING,
        ];

        $values = array_merge($validated, $append);

        /** @var User $user */
        $user = request()->user();

        $order = $user->orders()->create($values);

        foreach ($validated['products'] as $product) {
            $order->products()->attach($product['id'], [
                'quantity' => $product['quantity'],
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
        //
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
