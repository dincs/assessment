<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('products/export', [ProductApiController::class, 'export'])->name('products.export');

    Route::get('products', [ProductApiController::class, 'index'])->name('products.index');
    Route::post('products', [ProductApiController::class, 'store'])->name('products.store');
    Route::match(['put', 'patch'], 'products/{product}', [ProductApiController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductApiController::class, 'destroy'])->name('products.destroy');

    Route::get('products/{product}', [ProductApiController::class, 'show'])
        ->whereNumber('product')
        ->name('products.show');

    Route::post('products/bulk-delete', [ProductApiController::class, 'bulkDelete'])->name('products.bulk-delete');
});

Route::middleware('auth:sanctum')->post('logout', function (Request $request) {
    $request->user()->currentAccessToken()?->delete(); // if using tokens
    auth()->guard('web')->logout();
    return response()->noContent();
});
