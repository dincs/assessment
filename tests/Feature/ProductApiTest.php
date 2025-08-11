<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use App\Models\User;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsNonAdmin()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user, 'sanctum');
    }

    protected function actingAsAdmin()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->forceFill(['is_admin' => true])->save(); 
        $this->actingAs($user, 'sanctum');
    }

    /** @test */
    public function guest_cannot_access_products_api(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    /** @test */
    public function non_admin_is_forbidden(): void
    {
        $this->actingAsNonAdmin();
        $this->getJson('/api/products')->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_product(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $payload = [
            'name' => 'Desk Lamp',
            'category_id' => $category->id,
            'price' => 19.90,
            'stock' => 5,
            'enabled' => true,
            'description' => 'A nice lamp',
        ];

        $res = $this->postJson('/api/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Desk Lamp')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.name', $category->name);

        $this->assertDatabaseHas('product', [
            'name' => 'Desk Lamp',
            'category_id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_on_create(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/products', [])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'price', 'stock', 'enabled']);
    }

    /** @test */
    public function admin_can_list_products_with_filters_and_pagination(): void
    {
        $this->actingAsAdmin();
        $catA = Category::factory()->create();
        $catB = Category::factory()->create();

        Product::factory()->count(8)->create(['enabled' => true, 'category_id' => $catA->id]);
        Product::factory()->count(5)->create(['enabled' => false, 'category_id' => $catB->id]);

        $this->getJson('/api/products?enabled=true&category_id=' . $catA->id)
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.current_page', 1);
    }

    /** @test */
    public function admin_can_view_single_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        $this->getJson('/api/products/' . $product->id)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.category.id', $product->category_id);
    }

    /** @test */
    public function admin_can_update_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['name' => 'Before']);

        $this->putJson('/api/products/' . $product->id, ['name' => 'After'])
            ->assertOk()
            ->assertJsonPath('data.name', 'After');

        $this->assertDatabaseHas('product', [
            'id' => $product->id,
            'name' => 'After',
        ]);
    }

    /** @test */
    public function admin_can_soft_delete_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $this->deleteJson('/api/products/' . $product->id)->assertNoContent();
        $this->assertSoftDeleted('product', ['id' => $product->id]);
    }

    /** @test */
    public function admin_can_bulk_delete_products(): void
    {
        $this->actingAsAdmin();
        $ids = Product::factory()->count(3)->create()->pluck('id')->all();

        $this->postJson('/api/products/bulk-delete', ['ids' => $ids])
            ->assertOk()
            ->assertJsonPath('deleted', $ids);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('product', ['id' => $id]);
        }
    }

    /** @test */
    public function bulk_delete_validates_ids(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/products/bulk-delete', ['ids' => ['abc', 999999]])
            ->assertStatus(422);
    }

    /** @test */
    public function export_downloads_excel_using_facade(): void
    {
        $this->actingAsAdmin();
        Product::factory()->count(3)->create();

        Excel::fake();

        // Use GET not getJson()
        $this->get('/api/products/export?enabled=true')->assertOk();

        Excel::assertDownloaded('products.xlsx', function ($export) {
            return $export instanceof ProductsExport;
        });
    }
}
