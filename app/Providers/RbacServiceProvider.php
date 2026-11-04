<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class RbacServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Short, stable type names in polymorphic columns (audit_logs.auditable_type)
        // instead of PHP class names, so renaming a class never orphans history.
        Relation::enforceMorphMap([
            'user' => User::class,
            'role' => Role::class,
            'product' => Product::class,
        ]);

        // Every permission name doubles as a Gate ability, so the framework's own
        // tools work unchanged: $user->can('products.view'), @can, the `can:`
        // middleware and Gate::authorize(). Returning null (not false) lets
        // policies still decide abilities that aren't permission names.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin() || $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        // @role('admin', 'manager') ... @endrole: true if the user has any of them.
        Blade::if('role', fn (string ...$roles) => (bool) auth()->user()?->hasRole($roles));
    }
}
