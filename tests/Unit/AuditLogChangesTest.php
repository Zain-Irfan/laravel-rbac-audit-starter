<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Product;
use PHPUnit\Framework\TestCase;

class AuditLogChangesTest extends TestCase
{
    public function test_changes_pair_old_and_new_values_per_field(): void
    {
        $log = new AuditLog([
            'auditable_type' => 'not-a-model',
            'old_values' => ['name' => 'Before', 'stock' => 1],
            'new_values' => ['name' => 'After', 'price' => 5],
        ]);

        $this->assertSame([
            ['field' => 'name', 'old' => 'Before', 'new' => 'After', 'masked' => false],
            ['field' => 'stock', 'old' => 1, 'new' => null, 'masked' => false],
            ['field' => 'price', 'old' => null, 'new' => 5, 'masked' => false],
        ], $log->changesFor(null));
    }

    public function test_fields_guarded_by_a_permission_are_masked_for_guests(): void
    {
        $log = new AuditLog([
            'auditable_type' => Product::class,
            'old_values' => ['cost' => '10.00', 'name' => 'Lamp'],
            'new_values' => ['cost' => '12.00', 'name' => 'Lamp'],
        ]);

        $changes = collect($log->changesFor(null))->keyBy('field');

        $this->assertTrue($changes['cost']['masked']);
        $this->assertNull($changes['cost']['old']);
        $this->assertNull($changes['cost']['new']);
        $this->assertFalse($changes['name']['masked']);
    }

    public function test_margin_percent(): void
    {
        $this->assertSame(40.0, (new Product(['price' => 100, 'cost' => 60]))->marginPercent());
        $this->assertNull((new Product(['price' => 100, 'cost' => null]))->marginPercent());
        $this->assertNull((new Product(['price' => 0, 'cost' => 5]))->marginPercent());
    }
}
