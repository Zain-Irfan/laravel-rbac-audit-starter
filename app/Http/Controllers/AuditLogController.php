<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Every audit entry, filterable by user, model type, event and date range.
     */
    public function index(Request $request): View
    {
        $types = AuditLog::auditableTypes();

        $filters = $request->validate([
            'user' => ['nullable', 'integer'],
            'model' => ['nullable', Rule::in(array_keys($types))],
            'event' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AuditLog::with('user')
            ->filter($filters)
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'types' => $types,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'events' => AuditLog::query()->distinct()->orderBy('event')->pluck('event'),
        ]);
    }

    /**
     * The full history of one record, which still works after it was deleted.
     */
    public function history(string $type, int $id): View
    {
        abort_unless(array_key_exists($type, AuditLog::auditableTypes()), 404);

        $logs = AuditLog::with('user')
            ->where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->latest('id')
            ->paginate(20);

        abort_if($logs->isEmpty() && $logs->currentPage() === 1, 404);

        return view('audit-logs.history', [
            'logs' => $logs,
            'type' => $type,
            'id' => $id,
            'subject' => Relation::getMorphedModel($type)::find($id),
        ]);
    }
}
