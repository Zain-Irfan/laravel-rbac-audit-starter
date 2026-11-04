<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(AuditLogger $audit): void
    {
        // Base data is not interesting history, so keep it out of the audit log.
        [$admin, $manager] = $audit->withoutAuditing(function () {
            $this->call(RbacSeeder::class);

            $users = collect([
                'admin' => ['Ada Admin', 'admin@example.com'],
                'manager' => ['Max Manager', 'manager@example.com'],
                'viewer' => ['Vic Viewer', 'viewer@example.com'],
            ])->map(function (array $person, string $role) {
                $user = User::factory()->create(['name' => $person[0], 'email' => $person[1]]);

                return $user->assignRole($role);
            });

            Product::factory(24)->create();

            return [$users['admin'], $users['manager']];
        });

        $this->seedDemoHistory($admin, $manager);
    }

    /**
     * A few real, audited edits so the Audit Log page has something to show.
     */
    protected function seedDemoHistory(User $admin, User $manager): void
    {
        $products = Product::orderBy('id')->take(3)->get();

        Auth::setUser($manager);
        $products[0]->update(['price' => $products[0]->price + 10, 'stock' => $products[0]->stock + 40]);
        $products[1]->update(['name' => $products[1]->name.' (2nd gen)']);
        Product::factory()->create(['name' => 'Desk lamp', 'sku' => 'DL-0001']);

        Auth::setUser($admin);
        $products[0]->update(['cost' => round($products[0]->cost * 1.08, 2)]);
        $products[2]->delete();

        Auth::forgetUser();
    }
}
