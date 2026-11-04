<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasFieldPermissions;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'sku', 'price', 'cost', 'stock'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Auditable, HasFactory, HasFieldPermissions;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    /**
     * Cost price is commercially sensitive: only some roles may see it.
     *
     * @return array<string, string>
     */
    public function fieldPermissions(): array
    {
        return ['cost' => 'products.edit-cost'];
    }

    /**
     * Gross margin as a percentage of price, or null when it can't be worked out.
     */
    public function marginPercent(): ?float
    {
        if ($this->cost === null || (float) $this->price <= 0) {
            return null;
        }

        return round(((float) $this->price - (float) $this->cost) / (float) $this->price * 100, 1);
    }
}
