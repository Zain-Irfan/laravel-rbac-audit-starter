<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Gives a model (normally User) roles, and permissions through those roles.
 *
 * Permission names are resolved once per model instance and memoised, so a
 * page that runs dozens of @can checks still costs a single query.
 */
trait HasRoles
{
    /**
     * Memoised permission names for this instance.
     *
     * @var Collection<int, string>|null
     */
    protected ?Collection $permissionNameCache = null;

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Scope the query to models that hold any of the given roles.
     */
    public function scopeRole(Builder $query, string|array $roles): Builder
    {
        return $query->whereHas('roles', fn (Builder $q) => $q->whereIn('name', (array) $roles));
    }

    /**
     * Determine if the model has any of the given roles.
     */
    public function hasRole(string|array|Role ...$roles): bool
    {
        $names = collect($roles)->flatten()
            ->map(fn ($role) => $role instanceof Role ? $role->name : $role);

        return $this->roles->pluck('name')->intersect($names)->isNotEmpty();
    }

    /**
     * Determine if the model holds the configured super role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('rbac.super_role'));
    }

    /**
     * Determine if any of the model's roles grants the given permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissionNames()->contains($permission);
    }

    /**
     * Names of every permission granted through the model's roles.
     *
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        return $this->permissionNameCache ??= $this->loadMissing('roles.permissions')
            ->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values();
    }

    /**
     * Give the model one or more roles, keeping the ones it already has.
     */
    public function assignRole(string|array|Role ...$roles): static
    {
        $this->roles()->syncWithoutDetaching($this->resolveRoleIds($roles));

        return $this->flushRoleCache();
    }

    /**
     * Take one or more roles away from the model.
     */
    public function removeRole(string|array|Role ...$roles): static
    {
        $this->roles()->detach($this->resolveRoleIds($roles));

        return $this->flushRoleCache();
    }

    /**
     * Replace the model's roles with exactly the given set.
     */
    public function syncRoles(string|array|Role ...$roles): static
    {
        $this->roles()->sync($this->resolveRoleIds($roles));

        return $this->flushRoleCache();
    }

    /**
     * Sync roles and, when the model is Auditable, record the before/after
     * role names (pivot changes don't fire Eloquent model events).
     *
     * @param  array<int, int|string|Role>  $roles
     */
    public function syncRolesAudited(array $roles): static
    {
        $before = $this->roles()->orderBy('name')->pluck('name')->all();

        $this->syncRoles($roles);

        $after = $this->roles()->orderBy('name')->pluck('name')->all();

        if ($before !== $after && method_exists($this, 'audit')) {
            $this->audit('roles_synced', ['roles' => $before], ['roles' => $after]);
        }

        return $this;
    }

    /**
     * Forget loaded roles and memoised permissions so the next check re-reads them.
     */
    public function flushRoleCache(): static
    {
        $this->unsetRelation('roles');
        $this->permissionNameCache = null;

        return $this;
    }

    /**
     * Turn role names, ids or models into a list of role ids.
     *
     * @param  array<int, mixed>  $roles
     * @return array<int, int>
     */
    protected function resolveRoleIds(array $roles): array
    {
        $roles = collect($roles)->flatten();

        $ids = $roles->map(fn ($role) => match (true) {
            $role instanceof Role => $role->getKey(),
            is_int($role) => $role,
            default => null,
        })->filter();

        $names = $roles->filter(fn ($role) => is_string($role));

        if ($names->isNotEmpty()) {
            $found = Role::whereIn('name', $names)->pluck('id', 'name');

            if ($missing = $names->diff($found->keys())->first()) {
                throw new \InvalidArgumentException("Role [{$missing}] does not exist.");
            }

            $ids = $ids->merge($found->values());
        }

        return $ids->unique()->values()->all();
    }
}
