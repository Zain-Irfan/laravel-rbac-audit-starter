<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_model_records_its_new_values(): void
    {
        $manager = $this->userWithRole('manager');

        $this->actingAs($manager)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post(route('products.store'), ['name' => 'Oak table', 'sku' => 'OT-1', 'price' => 450, 'stock' => 3]);

        $product = Product::firstWhere('sku', 'OT-1');
        $log = $product->audits()->sole();

        $this->assertSame('created', $log->event);
        $this->assertSame('product', $log->auditable_type);
        $this->assertTrue($log->user->is($manager));
        $this->assertSame('203.0.113.7', $log->ip_address);
        $this->assertSame(route('products.store'), $log->url);
        $this->assertNull($log->old_values);
        $this->assertSame('Oak table', $log->new_values['name']);
        $this->assertArrayNotHasKey('created_at', $log->new_values);
        $this->assertArrayNotHasKey('id', $log->new_values);
    }

    public function test_updating_a_model_records_only_changed_fields_with_old_and_new_values(): void
    {
        $product = Product::factory()->create(['name' => 'Old name', 'price' => 10, 'stock' => 5]);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->put(route('products.update', $product), [
            'name' => 'New name', 'sku' => $product->sku, 'price' => 10, 'stock' => 9, 'cost' => $product->cost,
        ]);

        $log = $product->audits()->where('event', 'updated')->sole();

        $this->assertEquals(['name' => 'Old name', 'stock' => 5], $log->old_values);
        $this->assertEquals(['name' => 'New name', 'stock' => 9], $log->new_values);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_saving_without_real_changes_does_not_write_an_entry(): void
    {
        $product = Product::factory()->create();
        $before = AuditLog::count();

        $product->touch();
        $product->update(['name' => $product->name]);

        $this->assertSame($before, AuditLog::count());
    }

    public function test_deleting_a_model_keeps_its_last_values(): void
    {
        $product = Product::factory()->create(['name' => 'Gone soon']);

        $this->actingAs($this->userWithRole('admin'))->delete(route('products.destroy', $product));

        $log = AuditLog::where('event', 'deleted')->sole();
        $this->assertSame($product->id, $log->auditable_id);
        $this->assertSame('Gone soon', $log->old_values['name']);
        $this->assertNull($log->new_values);
    }

    public function test_hidden_attributes_like_passwords_are_never_logged(): void
    {
        $user = User::factory()->create();
        $user->update(['password' => 'a-new-secret-password', 'name' => 'Renamed']);

        $log = $user->audits()->where('event', 'updated')->sole();

        $this->assertSame(['name'], array_keys($log->new_values));
        $this->assertArrayNotHasKey('password', $user->audits()->where('event', 'created')->sole()->new_values);
    }

    public function test_auditing_can_be_switched_off_for_a_block(): void
    {
        app(AuditLogger::class)->withoutAuditing(fn () => Product::factory()->count(3)->create());

        $this->assertSame(0, AuditLog::where('auditable_type', 'product')->count());
    }

    public function test_audit_page_lists_entries_and_filters_by_user_model_and_date(): void
    {
        $manager = $this->userWithRole('manager');
        $admin = $this->userWithRole('admin');

        $this->actingAs($manager);
        $byManager = Product::factory()->create(['name' => 'Made by manager']);

        $this->actingAs($admin);
        Carbon::setTestNow('2026-03-15 10:00:00');
        $byAdmin = Product::factory()->create(['name' => 'Made by admin']);
        Carbon::setTestNow();

        $this->actingAs($manager)->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Made by manager')
            ->assertSee('Made by admin');

        $this->actingAs($manager)->get(route('audit-logs.index', ['user' => $manager->id]))
            ->assertSee('Made by manager')
            ->assertDontSee('Made by admin');

        $this->actingAs($manager)->get(route('audit-logs.index', ['model' => 'user']))
            ->assertDontSee('Made by manager');

        $this->actingAs($manager)->get(route('audit-logs.index', ['from' => '2026-03-15', 'to' => '2026-03-15']))
            ->assertSee('Made by admin')
            ->assertDontSee('Made by manager');

        $this->actingAs($manager)->get(route('audit-logs.index', ['model' => 'not-a-model']))
            ->assertSessionHasErrors('model');
    }

    public function test_history_page_shows_every_change_to_one_record_even_after_deletion(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        $product = Product::factory()->create(['name' => 'Version 1']);
        $product->update(['name' => 'Version 2']);
        $other = Product::factory()->create(['name' => 'Unrelated']);
        $product->delete();

        $this->get(route('audit-logs.history', ['product', $product->id]))
            ->assertOk()
            ->assertSee('Version 1')
            ->assertSee('Version 2')
            ->assertSee('This record has been deleted')
            ->assertDontSee('Unrelated');

        $this->get(route('audit-logs.history', ['product', 999]))->assertNotFound();
        $this->get(route('audit-logs.history', ['spaceship', $other->id]))->assertNotFound();
    }

    public function test_restricted_fields_are_masked_in_the_audit_log(): void
    {
        $admin = $this->userWithRole('admin');
        $manager = $this->userWithRole('manager');

        $this->actingAs($admin);
        $product = Product::factory()->create(['cost' => 12.34]);
        $product->update(['cost' => 98.76]);

        $this->actingAs($manager)->get(route('audit-logs.history', ['product', $product->id]))
            ->assertOk()
            ->assertSee('hidden')
            ->assertDontSee('12.34')
            ->assertDontSee('98.76');

        $this->actingAs($admin)->get(route('audit-logs.history', ['product', $product->id]))
            ->assertSee('12.34')
            ->assertSee('98.76');
    }

    public function test_users_without_audit_permission_cannot_open_history(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->userWithRole('viewer'))
            ->get(route('audit-logs.history', ['product', $product->id]))
            ->assertForbidden();
    }
}
