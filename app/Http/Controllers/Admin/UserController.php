<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->value();

        $users = User::with('roles')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User, 'roles' => Role::orderBy('label')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, new User);

        $user = User::create($data);
        $user->syncRolesAudited($data['roles'] ?? []);

        return to_route('admin.users.index')->with('status', "User {$user->name} created.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user->load('roles'), 'roles' => Role::orderBy('label')->get()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        $this->preventSelfLockout($request->user(), $user, $data['roles'] ?? []);

        $user->update(array_filter($data, fn ($value, $key) => $key !== 'password' || filled($value), ARRAY_FILTER_USE_BOTH));
        $user->syncRolesAudited($data['roles'] ?? []);

        return to_route('admin.users.index')->with('status', "User {$user->name} updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account from here.');
        }

        $user->delete();

        return to_route('admin.users.index')->with('status', "User {$user->name} deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, User $user): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user->exists ? 'nullable' : 'required', Password::defaults()],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
        ]);
    }

    /**
     * An admin must not be able to remove their own super role and lock
     * themselves (and possibly everyone) out of the admin screens.
     *
     * @param  array<int, int|string>  $roleIds
     */
    protected function preventSelfLockout(User $actor, User $user, array $roleIds): void
    {
        $superRole = Role::firstWhere('name', config('rbac.super_role'));

        if ($actor->is($user) && $superRole && $user->hasRole($superRole) && ! in_array($superRole->id, array_map('intval', $roleIds), true)) {
            throw ValidationException::withMessages([
                'roles' => "You cannot remove the {$superRole->label} role from your own account.",
            ]);
        }
    }
}
