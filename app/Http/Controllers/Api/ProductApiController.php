<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;

class ProductApiController extends Controller
{

    /**
     * @OA\Post(
     *   path="/api/products",
     *   tags={"Products"},
     *   summary="Create product",
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(required={"name","category_id","price","stock","enabled"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="category_id", type="integer"),
     *       @OA\Property(property="description", type="string", nullable=true),
     *       @OA\Property(property="price", type="number", format="float"),
     *       @OA\Property(property="stock", type="integer"),
     *       @OA\Property(property="enabled", type="boolean")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Created")
     * )
     */

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        $product->load('category');

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201); // << needed for assertCreated()
    }

    /**
     * @OA\Get(
     *   path="/api/allproducts",
     *   tags={"Products"},
     *   summary="List products",
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="enabled", in="query", @OA\Schema(type="boolean")),
     *   @OA\Response(response=200, description="OK")
     * )
     */

    public function index(Request $request)
    {
        $q = Product::query()->with('category');

        if ($request->filled('category_id')) {
            $q->where('category_id', (int)$request->category_id);
        }
        if ($request->filled('enabled')) {
            $q->where('enabled', filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN));
        }

        // Must be paginate() to produce data/links/meta
        return ProductResource::collection($q->paginate(10));
    }

    /**
     * @OA\Get(
     *   path="/api/products/{product}",
     *   tags={"Products"},
     *   summary="Get product",
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(name="product", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */

    public function show(Product $product)
    {
        $product->load('category');
        return new ProductResource($product);
    }

    /**
     * @OA\Patch(
     *   path="/api/products/{product}",
     *   tags={"Products"},
     *   summary="Partially update product",
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(name="product", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="category_id", type="integer"),
     *       @OA\Property(property="description", type="string", nullable=true),
     *       @OA\Property(property="price", type="number", format="float"),
     *       @OA\Property(property="stock", type="integer"),
     *       @OA\Property(property="enabled", type="boolean")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        $product->load('category');
        return new ProductResource($product); // 200 OK
    }

    /**
     * @OA\Delete(
     *   path="/api/products/{product}",
     *   tags={"Products"},
     *   summary="Delete product (soft delete)",
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(name="product", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="No Content")
     * )
     */

    public function destroy(Product $product)
    {
        $product->delete(); // soft delete
        return response()->noContent(); // 204
    }

    /**
     * @OA\Post(
     *   path="/api/products/bulk-delete",
     *   tags={"Products"},
     *   summary="Bulk delete products",
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"ids"},
     *       @OA\Property(property="ids", type="array", @OA\Items(type="integer"))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $missing = Product::whereIn('id', $data['ids'])->pluck('id')->diff($data['ids']);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Some IDs do not exist',
                'missing_ids' => $missing->values(),
            ], 422);
        }

        Product::whereIn('id', $data['ids'])->delete();

        return response()->json(['deleted' => $data['ids']]);
    }

    /**
     * @OA\Get(
     *   path="/api/products/export",
     *   tags={"Products"},
     *   summary="Export products to Excel",
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="enabled", in="query", @OA\Schema(type="boolean")),
     *   @OA\Response(
     *     response=200,
     *     description="Excel file",
     *     content={
     *       @OA\MediaType(
     *         mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *       )
     *     }
     *   )
     * )
     */

    public function export(Request $request)
    {
        // NOTE: Binary response. The test should not use getJson().
        return Excel::download(new ProductsExport($request), 'products.xlsx');
    }
}
