<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Electronics', 'Home & Living', 'Books', 'Toys', 'Fashion'];
        $categories = collect($names)->map(fn ($name) =>
            Category::firstOrCreate(['name' => $name])
        );

        Product::factory()
            ->count(30)
            ->state(fn () => [
                'category_id' => $categories->random()->id,
            ])
            ->create();
    }
}
