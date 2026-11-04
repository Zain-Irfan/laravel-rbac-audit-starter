<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('admin@example.com');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_viewer_can_list_products_but_not_change_them(): void
    {
        $viewer = $this->userWithRole('viewer');
        $product = Product::factory()->create();

        $this->actingAs($viewer)->get(route('products.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('New product')
            ->assertDontSee(route('products.edit', $product));

        $this->actingAs($viewer)->get(route('products.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('products.store'), Product::factory()->raw())->assertForbidden();
        $this->actingAs($viewer)->put(route('products.update', $product), ['name' => 'Hacked'] + $product->toArray())->assertForbidden();
        $this->actingAs($viewer)->delete(route('products.destroy', $product))->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name]);
    }

    public function test_user_without_any_role_cannot_see_products(): void
    {
        $this->actingAs(User::factory()->create())->get(route('products.index'))->assertForbidden();
    }

    public function test_manager_can_create_and_edit_but_not_delete_products(): void
    {
        $manager = $this->userWithRole('manager');
        $product = Product::factory()->create();

        $this->actingAs($manager)
            ->post(route('products.store'), ['name' => 'Walnut shelf', 'sku' => 'WS-1000', 'price' => 89.5, 'stock' => 4])
            ->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['sku' => 'WS-1000']);

        $this->actingAs($manager)
            ->put(route('products.update', $product), ['name' => 'Renamed', 'sku' => $product->sku, 'price' => 10, 'stock' => 1])
            ->assertRedirect(route('products.index'));
        $this->assertSame('Renamed', $product->fresh()->name);

        $this->actingAs($manager)->delete(route('products.destroy', $product))->assertForbidden();
    }

    public function test_admin_can_delete_products(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->userWithRole('admin'))
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertModelMissing($product);
    }

    public function test_can_middleware_protects_admin_and_audit_pages(): void
    {
        $viewer = $this->userWithRole('viewer');
        $manager = $this->userWithRole('manager');
        $admin = $this->userWithRole('admin');

        $this->actingAs($viewer)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('audit-logs.index'))->assertOk();

        foreach ([$viewer, $manager] as $user) {
            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
        }

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
    }

    public function test_navigation_only_shows_links_the_user_may_open(): void
    {
        $this->actingAs($this->userWithRole('viewer'))->get(route('dashboard'))
            ->assertSee(route('products.index'))
            ->assertDontSee(route('audit-logs.index'))
            ->assertDontSee(route('admin.roles.index'));

        $this->actingAs($this->userWithRole('admin'))->get(route('dashboard'))
            ->assertSee(route('audit-logs.index'))
            ->assertSee(route('admin.users.index'))
            ->assertSee(route('admin.roles.index'));
    }

    public function test_role_middleware_checks_role_membership(): void
    {
        Route::middleware(['web', 'auth', 'role:admin,manager'])->get('/_test/role', fn () => 'ok');

        $this->actingAs($this->userWithRole('viewer'))->get('/_test/role')->assertForbidden();
        $this->actingAs($this->userWithRole('manager'))->get('/_test/role')->assertOk();
        $this->actingAs($this->userWithRole('admin'))->get('/_test/role')->assertOk();
    }

    public function test_role_blade_directive(): void
    {
        $template = "@role('admin') admin-only @endrole\n@role('viewer', 'manager') staff @endrole";

        $this->actingAs($this->userWithRole('manager'));
        $this->assertSame('staff', trim(Blade::render($template)));

        $this->actingAs($this->userWithRole('admin'));
        $this->assertSame('admin-only', trim(Blade::render($template)));
    }

    public function test_registered_users_get_the_default_role(): void
    {
        $this->userWithRole('viewer'); // seeds the roles

        $this->post('/register', [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(User::firstWhere('email', 'new@example.com')->hasRole('viewer'));
    }
}
