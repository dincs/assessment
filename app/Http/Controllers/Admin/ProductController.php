<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query()->with('category');

        if ($request->filled('search')) {
            $term = $request->string('search');
            $q->where('name', 'like', "%{$term}%");
        }
        if ($request->filled('category_id')) {
            $q->where('category_id', (int) $request->category_id);
        }
        if ($request->filled('enabled') && $request->enabled !== '') {
            $q->where('enabled', filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN));
        }

        $products   = $q->orderByDesc('id')->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get(['id','name']);

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get(['id','name']);
        $product    = new Product();
        return view('admin.products.create', compact('product', 'categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get(['id','name']);
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer'],
        ]);

        Product::whereIn('id', $data['ids'])->delete();
        return back()->with('success', 'Selected products deleted.');
    }

    public function export(Request $request)
    {
        // Reuse the same filtered export as the API:
        return Excel::download(new ProductsExport($request), 'products.xlsx');
    }
}
