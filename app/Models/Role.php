<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'label', 'description'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use Auditable, HasFactory;

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Whether this is the configured super role, which bypasses every check.
     */
    public function isSuper(): bool
    {
        return $this->name === config('rbac.super_role');
    }

    /**
     * Grant one or more permissions (by name) to this role.
     */
    public function givePermissionTo(string ...$permissions): static
    {
        $this->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        return $this;
    }

    /**
     * Revoke one or more permissions (by name) from this role.
     */
    public function revokePermissionTo(string ...$permissions): static
    {
        $this->permissions()->detach(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        return $this;
    }

    /**
     * Replace this role's permissions and record the change in the audit log.
     *
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissionsAudited(array $permissionIds): void
    {
        $before = $this->permissions()->orderBy('name')->pluck('name')->all();

        $this->permissions()->sync($permissionIds);

        $after = $this->permissions()->orderBy('name')->pluck('name')->all();

        if ($before !== $after) {
            $this->audit('permissions_synced', ['permissions' => $before], ['permissions' => $after]);
        }
    }
}
