<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private Request $request) {}

    public function headings(): array
    {
        return ['ID','Name','Category','Price','Stock','Enabled','Created At'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            optional($product->category)->name,
            $product->price,
            $product->stock,
            $product->enabled ? 1 : 0,
            $product->created_at,
        ];
    }

    public function query()
    {
        $q = Product::query()->with('category');

        if ($this->request->filled('category_id')) {
            $q->where('category_id', (int)$this->request->category_id);
        }
        if ($this->request->filled('enabled')) {
            $q->where('enabled', filter_var($this->request->enabled, FILTER_VALIDATE_BOOLEAN));
        }

        return $q->select(['id','name','category_id','price','stock','enabled','created_at']);
    }
}
