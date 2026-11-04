<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Observers\AuditObserver;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;

/**
 * Records created / updated / deleted events for the model in `audit_logs`.
 *
 * Add `use Auditable;` to any Eloquent model. To keep extra attributes out of
 * the log, declare `protected array $auditExclude = ['column', ...];` on it.
 * The primary key, timestamps and the model's $hidden attributes are always left out.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        // Registered as plain listeners: Model::observe() would instantiate the
        // model, which Eloquent forbids while the model is still booting.
        static::created(fn (self $model) => app(AuditObserver::class)->created($model));
        static::updated(fn (self $model) => app(AuditObserver::class)->updated($model));
        static::deleted(fn (self $model) => app(AuditObserver::class)->deleted($model));
    }

    /**
     * @return MorphMany<AuditLog, $this>
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('id');
    }

    /**
     * Write a custom audit entry for this model, e.g. a pivot change.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function audit(string $event, array $old = [], array $new = []): ?AuditLog
    {
        return app(AuditLogger::class)->log($this, $event, $old, $new);
    }

    /**
     * Strip attributes that should never reach the audit log.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function auditableValues(array $values): array
    {
        $exclude = array_merge(
            [$this->getKeyName(), $this->getCreatedAtColumn(), $this->getUpdatedAtColumn()],
            $this->getHidden(),
            property_exists($this, 'auditExclude') ? $this->auditExclude : [],
        );

        return Arr::except($values, $exclude);
    }
}
