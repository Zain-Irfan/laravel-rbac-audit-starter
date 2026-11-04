<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Writes audit entries, attaching who made the change and from where.
 *
 * Scoped to the request lifecycle so it is safe under Octane / queue workers.
 */
#[Scoped]
class AuditLogger
{
    protected bool $enabled = true;

    /**
     * Record an event against a model.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function log(Model $model, string $event, array $old = [], array $new = []): ?AuditLog
    {
        if (! $this->enabled) {
            return null;
        }

        // Console commands (seeders, jobs) have no real request to describe.
        $fromRequest = ! app()->runningInConsole() || app()->runningUnitTests();
        $request = request();

        return AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $fromRequest ? $request->ip() : null,
            'user_agent' => $fromRequest ? Str::limit((string) $request->userAgent(), 255, '') : null,
            'url' => $fromRequest ? $request->fullUrl() : null,
        ]);
    }

    /**
     * Run the callback with auditing switched off (bulk imports, seeding, etc).
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function withoutAuditing(callable $callback): mixed
    {
        $previous = $this->enabled;
        $this->enabled = false;

        try {
            return $callback();
        } finally {
            $this->enabled = $previous;
        }
    }
}
