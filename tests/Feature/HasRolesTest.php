<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HasRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_roles_can_be_assigned_removed_and_synced(): void
    {
        $user = User::factory()->create();

        $user->assignRole('viewer');
        $this->assertTrue($user->hasRole('viewer'));
        $this->assertFalse($user->hasRole('manager'));

        $user->assignRole('manager');
        $this->assertTrue($user->hasRole('viewer', 'manager'));

        $user->removeRole('viewer');
        $this->assertFalse($user->hasRole('viewer'));

        $user->syncRoles(Role::firstWhere('name', 'admin'));
        $this->assertSame(['admin'], $user->roles->pluck('name')->all());
    }

    public function test_assigning_an_unknown_role_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::factory()->create()->assignRole('does-not-exist');
    }

    public function test_permissions_are_granted_through_roles(): void
    {
        $manager = $this->userWithRole('manager');

        $this->assertTrue($manager->hasPermission('products.update'));
        $this->assertTrue($manager->can('products.update'));
        $this->assertFalse($manager->hasPermission('products.edit-cost'));
        $this->assertFalse($manager->can('products.edit-cost'));
    }

    public function test_permissions_from_multiple_roles_are_merged(): void
    {
        $user = $this->userWithRole('viewer');
        $this->assertFalse($user->can('audit.view'));

        $user->assignRole('manager');

        $this->assertTrue($user->can('audit.view'));
        $this->assertTrue($user->can('products.view'));
    }

    public function test_super_role_passes_every_gate_check(): void
    {
        $admin = $this->userWithRole('admin');

        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->can('products.edit-cost'));
        $this->assertTrue($admin->can('roles.manage'));
        $this->assertTrue($admin->can('some.permission-that-does-not-exist'));
    }

    public function test_a_user_without_roles_has_no_permissions(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('products.view'));
        $this->assertCount(0, $user->permissionNames());
    }

    public function test_permission_lookups_are_memoised_per_instance(): void
    {
        $manager = $this->userWithRole('manager');

        DB::enableQueryLog();
        foreach (range(1, 10) as $ignored) {
            $manager->can('products.view');
            $manager->can('products.delete');
        }

        $this->assertLessThanOrEqual(2, count(DB::getQueryLog()));
    }

    public function test_role_changes_flush_the_memoised_permissions(): void
    {
        $user = $this->userWithRole('viewer');
        $this->assertFalse($user->can('products.create'));

        $user->assignRole('manager');

        $this->assertTrue($user->can('products.create'));
    }

    public function test_role_scope_filters_users(): void
    {
        $this->userWithRole('viewer');
        $manager = $this->userWithRole('manager');

        $this->assertSame([$manager->id], User::role('manager')->pluck('id')->all());
    }
}
