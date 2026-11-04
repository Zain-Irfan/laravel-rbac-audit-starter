<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Role
    |--------------------------------------------------------------------------
    |
    | Users holding this role pass every Gate check (see RbacServiceProvider).
    | Its permissions cannot be edited from the UI, which keeps at least one
    | role that can always get back into the admin screens.
    |
    */

    'super_role' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | Assigned to newly registered users. Set to null to register users
    | without any role.
    |
    */

    'default_role' => 'viewer',

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | The single source of truth for every permission the application knows
    | about, grouped for display. Run `php artisan rbac:sync` after editing
    | this list to create the matching database rows.
    |
    */

    'permissions' => [
        'Products' => [
            'products.view' => 'View products',
            'products.create' => 'Create products',
            'products.update' => 'Edit products',
            'products.delete' => 'Delete products',
            'products.edit-cost' => 'See and edit the cost field',
        ],
        'Administration' => [
            'users.manage' => 'Manage users and their roles',
            'roles.manage' => 'Manage roles and permissions',
            'audit.view' => 'View the audit log',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeded Roles
    |--------------------------------------------------------------------------
    |
    | Roles created by the seeder, with their initial permissions. The super
    | role needs no permissions listed because it bypasses every check.
    |
    */

    'roles' => [
        'admin' => [
            'label' => 'Admin',
            'description' => 'Full access to everything, including roles and permissions.',
            'permissions' => [],
        ],
        'manager' => [
            'label' => 'Manager',
            'description' => 'Runs the product catalogue and can read the audit log. Cannot see cost prices.',
            'permissions' => ['products.view', 'products.create', 'products.update', 'audit.view'],
        ],
        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Read-only access to products.',
            'permissions' => ['products.view'],
        ],
    ],

];
