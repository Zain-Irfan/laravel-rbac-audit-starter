<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user holding the given seeded role(s).
     *
     * Seeds permissions and the Admin / Manager / Viewer roles on first use.
     */
    protected function userWithRole(string ...$roles): User
    {
        if (! Role::query()->exists()) {
            $this->seed(RbacSeeder::class);
        }

        return User::factory()->create()->assignRole(...$roles);
    }
}
