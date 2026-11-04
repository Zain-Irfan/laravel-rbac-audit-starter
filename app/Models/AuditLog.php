<?php

namespace App\Models;

use App\Models\Concerns\HasFieldPermissions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'event', 'auditable_type', 'auditable_id',
    'old_values', 'new_values', 'ip_address', 'user_agent', 'url',
])]
class AuditLog extends Model
{
    /**
     * Audit entries are append-only, so there is no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Apply the audit-log page filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['user'] ?? null, fn (Builder $q, $user) => $q->where('user_id', $user))
            ->when($filters['model'] ?? null, fn (Builder $q, $model) => $q->where('auditable_type', $model))
            ->when($filters['event'] ?? null, fn (Builder $q, $event) => $q->where('event', $event))
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('created_at', '<=', $to));
    }

    /**
     * Human label for the audited model type, e.g. "Product".
     */
    public function modelLabel(): string
    {
        return Str::headline($this->auditable_type);
    }

    /**
     * Field-by-field changes, with values the viewer may not see masked out.
     *
     * @return array<int, array{field: string, old: mixed, new: mixed, masked: bool}>
     */
    public function changesFor(?User $viewer): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $hidden = $this->hiddenFieldsFor($viewer);

        return collect(array_keys($old + $new))
            ->map(fn (string $field) => [
                'field' => $field,
                'old' => in_array($field, $hidden, true) ? null : ($old[$field] ?? null),
                'new' => in_array($field, $hidden, true) ? null : ($new[$field] ?? null),
                'masked' => in_array($field, $hidden, true),
            ])
            ->all();
    }

    /**
     * Fields of the audited model that the viewer is not permitted to see.
     *
     * @return array<int, string>
     */
    protected function hiddenFieldsFor(?User $viewer): array
    {
        $class = Relation::getMorphedModel($this->auditable_type) ?? $this->auditable_type;

        if (! class_exists($class) || ! in_array(HasFieldPermissions::class, class_uses_recursive($class), true)) {
            return [];
        }

        return (new $class)->hiddenFieldsFor($viewer);
    }

    /**
     * Every audited model type, keyed by its morph alias.
     *
     * @return array<string, string>
     */
    public static function auditableTypes(): array
    {
        return collect(Relation::morphMap())
            ->filter(fn (string $class) => in_array(Concerns\Auditable::class, class_uses_recursive($class), true))
            ->map(fn (string $class, string $alias) => Str::headline($alias))
            ->sort()
            ->all();
    }
}
