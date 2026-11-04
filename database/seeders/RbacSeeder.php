<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Creates every permission from config/rbac.php and the Admin / Manager /
 * Viewer roles with their starting permissions. Safe to run repeatedly.
 */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        Permission::syncFromConfig();

        foreach (config('rbac.roles') as $name => $definition) {
            $role = Role::updateOrCreate(['name' => $name], [
                'label' => $definition['label'],
                'description' => $definition['description'],
            ]);

            $role->permissions()->sync(
                Permission::whereIn('name', $definition['permissions'])->pluck('id')
            );
        }
    }
}
