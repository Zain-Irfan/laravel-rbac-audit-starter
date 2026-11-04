<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'label', 'group'])]
class Permission extends Model
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Create or update the permission rows defined in config/rbac.php.
     *
     * @return array{created: int, updated: int}
     */
    public static function syncFromConfig(): array
    {
        $created = $updated = 0;

        foreach (config('rbac.permissions') as $group => $permissions) {
            foreach ($permissions as $name => $label) {
                $permission = static::updateOrCreate(['name' => $name], ['label' => $label, 'group' => $group]);

                $permission->wasRecentlyCreated ? $created++ : ($permission->wasChanged() ? $updated++ : null);
            }
        }

        return compact('created', 'updated');
    }
}
