<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\PatchProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

final class ProductController extends ApiController
{
    /**
     * Get all Products
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);

        $products = QueryBuilder::for(Product::class)
            ->allowedFilters('name', 'unit_price')
            ->allowedSorts('name', 'unit_price')
            ->allowedIncludes('created_by')
            ->paginate();

        return $this->success(ProductResource::collection($products));
    }

    /**
     * Store new Product
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $product = $user->createdProducts()->create($validated);

        return $this->created(new ProductResource($product));
    }

    /**
     * Get Product
     */
    public function show(Product $product): JsonResponse
    {
        Gate::authorize('view', $product);

        return $this->success(new ProductResource($product));
    }

    /**
     * Update the Product
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        $product->update($validated);

        return $this->success(new ProductResource($product->fresh()));
    }
    /**
     *
     * Partially the Product
     */
    public function patch(PatchProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        $product->update($validated);

        return $this->success(new ProductResource($product->fresh()));
    }

    /**
     * Remove the Product.
     */
    public function destroy(Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);

        // TODO: add check if product is not used in any order

        $product->delete();

        return $this->noContent();
    }
}
