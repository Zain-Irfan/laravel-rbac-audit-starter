<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_with_roles(): void
    {
        $admin = $this->userWithRole('admin');
        $manager = Role::firstWhere('name', 'manager');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Sam Staff',
            'email' => 'sam@example.com',
            'password' => 'a-long-password',
            'roles' => [$manager->id],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue(User::firstWhere('email', 'sam@example.com')->hasRole('manager'));
    }

    public function test_assigning_roles_is_audited(): void
    {
        $admin = $this->userWithRole('admin');
        $user = $this->userWithRole('viewer');
        $manager = Role::firstWhere('name', 'manager');

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => [$manager->id],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame(['manager'], $user->fresh()->roles->pluck('name')->all());

        $log = $user->audits()->where('event', 'roles_synced')->sole();
        $this->assertSame(['roles' => ['viewer']], $log->old_values);
        $this->assertSame(['roles' => ['manager']], $log->new_values);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_admin_cannot_remove_their_own_super_role(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => [Role::firstWhere('name', 'viewer')->id],
        ])->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');

        $this->assertModelExists($admin);
    }

    public function test_permission_matrix_toggles_permissions_per_role_and_is_audited(): void
    {
        $admin = $this->userWithRole('admin');
        $manager = Role::firstWhere('name', 'manager');
        $viewer = Role::firstWhere('name', 'viewer');
        $ids = Permission::pluck('id', 'name');

        $this->actingAs($admin)->put(route('admin.roles.permissions.update'), [
            'permissions' => [
                $manager->id => [$ids['products.view'], $ids['products.edit-cost']],
                // viewer omitted entirely = all boxes unticked
            ],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertEqualsCanonicalizing(['products.view', 'products.edit-cost'], $manager->permissions()->pluck('name')->all());
        $this->assertCount(0, $viewer->permissions);

        $log = AuditLog::where('auditable_type', 'role')->where('auditable_id', $manager->id)->where('event', 'permissions_synced')->sole();
        $this->assertContains('products.edit-cost', $log->new_values['permissions']);
        $this->assertContains('products.update', $log->old_values['permissions']);
    }

    public function test_matrix_changes_apply_to_users_immediately(): void
    {
        $admin = $this->userWithRole('admin');
        $viewer = $this->userWithRole('viewer');
        $this->assertFalse($viewer->can('audit.view'));

        $this->actingAs($admin)->put(route('admin.roles.permissions.update'), [
            'permissions' => [
                Role::firstWhere('name', 'viewer')->id => Permission::whereIn('name', ['products.view', 'audit.view'])->pluck('id')->all(),
            ],
        ]);

        $this->actingAs($viewer->fresh())->get(route('audit-logs.index'))->assertOk();
    }

    public function test_super_role_permissions_cannot_be_changed_or_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $superRole = Role::firstWhere('name', 'admin');

        $this->actingAs($admin)->put(route('admin.roles.permissions.update'), [
            'permissions' => [$superRole->id => [Permission::first()->id]],
        ]);
        $this->assertCount(0, $superRole->permissions);

        $this->actingAs($admin)->delete(route('admin.roles.destroy', $superRole))->assertForbidden();
        $this->assertModelExists($superRole);
    }

    public function test_admin_can_create_and_delete_a_role(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'support', 'label' => 'Support', 'description' => 'Helps customers',
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::firstWhere('name', 'support');
        $this->assertNotNull($role);

        $this->actingAs($admin)->delete(route('admin.roles.destroy', $role))->assertRedirect(route('admin.roles.index'));
        $this->assertModelMissing($role);
        $this->assertTrue(AuditLog::where('auditable_type', 'role')->where('event', 'deleted')->exists());
    }
}
