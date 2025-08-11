<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');      
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt'); 
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth','admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.products.index'));
        Route::get ('/products/export', [ProductController::class, 'export'])->name('products.export');
        Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulkDelete');
        Route::resource('products', ProductController::class);
    });

// require __DIR__.'/auth.php';
