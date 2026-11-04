<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Record-level rules for products. Each method maps to a permission; this is
 * also the place to add ownership or state checks (e.g. "only drafts can be
 * deleted") on top of the permission.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete');
    }
}
