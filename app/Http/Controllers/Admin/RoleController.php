<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Roles x permissions matrix.
     */
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::with('permissions:id')->withCount('users')->orderBy('id')->get(),
            'permissionGroups' => Permission::orderBy('id')->get()->groupBy('group'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'alpha_dash', 'lowercase', Rule::unique('roles')],
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create($data);

        return to_route('admin.roles.index')->with('status', "Role {$role->label} created. Tick its permissions below.");
    }

    /**
     * Save the whole matrix in one go. The super role is skipped: it bypasses
     * permission checks, so its rows are never edited.
     */
    public function updatePermissions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        DB::transaction(function () use ($data) {
            Role::all()->reject->isSuper()->each(
                fn (Role $role) => $role->syncPermissionsAudited($data['permissions'][$role->id] ?? [])
            );
        });

        return to_route('admin.roles.index')->with('status', 'Permissions saved.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->isSuper(), 403, 'The super role cannot be deleted.');

        $role->delete();

        return to_route('admin.roles.index')->with('status', "Role {$role->label} deleted.");
    }
}
