<?php

namespace App\Models\Concerns;

use App\Models\User;

/**
 * Field-level permissions: individual attributes that only users holding a
 * given permission may see or change.
 *
 * The same map drives form validation, the views and audit-log masking, so a
 * protected value never leaks through a side door.
 */
trait HasFieldPermissions
{
    /**
     * Map of attribute => permission required to see or edit it.
     *
     * @return array<string, string>
     */
    abstract public function fieldPermissions(): array;

    /**
     * Attributes the given user is not allowed to see or edit.
     *
     * @return array<int, string>
     */
    public function hiddenFieldsFor(?User $user): array
    {
        return collect($this->fieldPermissions())
            ->reject(fn (string $permission) => $user?->can($permission))
            ->keys()
            ->all();
    }

    public function canAccessField(?User $user, string $field): bool
    {
        return ! in_array($field, $this->hiddenFieldsFor($user), true);
    }
}
