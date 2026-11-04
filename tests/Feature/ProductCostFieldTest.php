<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCostFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_is_hidden_from_users_without_permission(): void
    {
        $product = Product::factory()->create(['price' => 100, 'cost' => 61.37]);

        $this->actingAs($this->userWithRole('manager'))
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('data-testid="cost-column"', false)
            ->assertDontSee('61.37');

        $this->actingAs($this->userWithRole('manager'))
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertDontSee('name="cost"', false)
            ->assertDontSee('61.37');
    }

    public function test_cost_is_visible_and_editable_for_admin(): void
    {
        $product = Product::factory()->create(['price' => 100, 'cost' => 61.37]);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('products.index'))
            ->assertSee('data-testid="cost-column"', false)
            ->assertSee('61.37');

        $this->actingAs($admin)->get(route('products.edit', $product))
            ->assertSee('name="cost"', false);

        $this->actingAs($admin)
            ->put(route('products.update', $product), [
                'name' => $product->name, 'sku' => $product->sku, 'price' => 100, 'stock' => $product->stock, 'cost' => 55,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals(55, $product->fresh()->cost);
    }

    public function test_a_crafted_request_cannot_change_cost_without_permission(): void
    {
        $product = Product::factory()->create(['cost' => 40]);

        $this->actingAs($this->userWithRole('manager'))
            ->from(route('products.edit', $product))
            ->put(route('products.update', $product), [
                'name' => 'Renamed', 'sku' => $product->sku, 'price' => 99, 'stock' => 1, 'cost' => 1,
            ])
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors(['cost' => 'You do not have permission to change the cost.']);

        $product->refresh();
        $this->assertEquals(40, $product->cost);
        $this->assertNotSame('Renamed', $product->name, 'The whole request is rejected, not just the cost.');
    }

    public function test_cost_cannot_be_set_on_create_without_permission(): void
    {
        $this->actingAs($this->userWithRole('manager'))
            ->post(route('products.store'), ['name' => 'Lamp', 'sku' => 'LA-1', 'price' => 20, 'stock' => 1, 'cost' => 5])
            ->assertSessionHasErrors('cost');

        $this->assertDatabaseMissing('products', ['sku' => 'LA-1']);
    }

    public function test_granting_the_permission_to_a_role_reveals_the_field(): void
    {
        $manager = $this->userWithRole('manager');
        Product::factory()->create(['cost' => 61.37]);

        Role::firstWhere('name', 'manager')->givePermissionTo('products.edit-cost');

        $this->actingAs($manager->fresh())->get(route('products.index'))->assertSee('61.37');
    }

    public function test_hidden_fields_are_resolved_per_user(): void
    {
        $product = new Product;

        $this->assertSame(['cost'], $product->hiddenFieldsFor($this->userWithRole('viewer')));
        $this->assertSame([], $product->hiddenFieldsFor($this->userWithRole('admin')));
        $this->assertSame(['cost'], $product->hiddenFieldsFor(null));
    }
}
