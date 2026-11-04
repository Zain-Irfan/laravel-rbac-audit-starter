<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Registered automatically by the Auditable trait.
 *
 * Runs inside the model's own events, so `updated` still sees the original
 * values (Eloquent syncs them only after the event has fired).
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $model->audit('created', [], $model->auditableValues($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $new = $model->auditableValues($model->getChanges());

        // Only timestamps or excluded columns changed: nothing worth recording.
        if ($new === []) {
            return;
        }

        $old = [];

        foreach (array_keys($new) as $key) {
            $old[$key] = $model->getRawOriginal($key);
        }

        $model->audit('updated', $old, $new);
    }

    public function deleted(Model $model): void
    {
        $model->audit('deleted', $model->auditableValues($model->getRawOriginal()), []);
    }
}
