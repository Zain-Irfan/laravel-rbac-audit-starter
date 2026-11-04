<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rbac:sync {--prune : Delete permissions that are no longer listed in config/rbac.php}')]
#[Description('Create or update permission rows from config/rbac.php')]
class SyncPermissions extends Command
{
    public function handle(): int
    {
        ['created' => $created, 'updated' => $updated] = Permission::syncFromConfig();

        $this->components->info("Permissions synced: {$created} created, {$updated} updated.");

        if ($this->option('prune')) {
            $known = collect(config('rbac.permissions'))->flatMap(fn (array $group) => array_keys($group));
            $deleted = Permission::whereNotIn('name', $known)->delete();

            $this->components->info("Pruned {$deleted} stale permission(s).");
        }

        return self::SUCCESS;
    }
}
