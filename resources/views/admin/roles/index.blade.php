<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Roles & Permissions') }}</h2>
        <p class="mt-1 text-sm text-gray-500">Tick a box to grant a permission to a role. Changes take effect on the next request and are written to the audit log.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <form method="POST" action="{{ route('admin.roles.permissions.update') }}" class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left">
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-gray-500">Permission</th>
                                @foreach ($roles as $role)
                                    <th class="px-4 py-3 text-center align-bottom">
                                        <div class="font-semibold text-gray-800">{{ $role->label }}</div>
                                        <div class="text-xs font-normal text-gray-400">{{ trans_choice(':count user|:count users', $role->users_count) }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        @foreach ($permissionGroups as $group => $permissions)
                            <tbody class="border-t border-gray-200">
                                <tr>
                                    <th colspan="{{ $roles->count() + 1 }}" class="bg-gray-50/60 px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $group }}</th>
                                </tr>
                                @foreach ($permissions as $permission)
                                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                                        <td class="px-4 py-2.5">
                                            <div class="text-gray-800">{{ $permission->label }}</div>
                                            <div class="font-mono text-xs text-gray-400">{{ $permission->name }}</div>
                                        </td>
                                        @foreach ($roles as $role)
                                            <td class="px-4 py-2.5 text-center">
                                                @if ($role->isSuper())
                                                    <input type="checkbox" checked disabled title="{{ $role->label }} always has every permission"
                                                           class="rounded border-gray-300 text-gray-400">
                                                @else
                                                    <input type="checkbox" name="permissions[{{ $role->id }}][]" value="{{ $permission->id }}"
                                                           aria-label="{{ $role->label }}: {{ $permission->label }}"
                                                           @checked($role->permissions->contains($permission->id))
                                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        @endforeach
                    </table>
                </div>

                <div class="flex items-center justify-between gap-4 border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">The {{ $roles->first(fn ($r) => $r->isSuper())?->label ?? 'super' }} role bypasses every check and cannot be edited.</p>
                    <x-primary-button>Save permissions</x-primary-button>
                </div>
            </form>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800">Roles</h3>
                    <ul class="mt-3 divide-y divide-gray-100">
                        @foreach ($roles as $role)
                            <li class="flex items-start justify-between gap-4 py-3">
                                <div>
                                    <div class="text-sm font-medium text-gray-800">{{ $role->label }} <span class="font-mono text-xs text-gray-400">{{ $role->name }}</span></div>
                                    <div class="text-xs text-gray-500">{{ $role->description }}</div>
                                </div>
                                <div class="flex shrink-0 items-center gap-3 text-sm">
                                    @can('audit.view')
                                        <a href="{{ route('audit-logs.history', ['role', $role->id]) }}" class="text-gray-500 hover:text-gray-800">History</a>
                                    @endcan
                                    @unless ($role->isSuper())
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete the {{ addslashes($role->label) }} role? Users keep their other roles.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:text-red-800">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <form method="POST" action="{{ route('admin.roles.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    @csrf
                    <h3 class="font-semibold text-gray-800">New role</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="label" value="Label" />
                            <x-text-input id="label" name="label" class="mt-1 block w-full" :value="old('label')" placeholder="Support agent" required />
                            <x-input-error :messages="$errors->get('label')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="name" value="Key" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full font-mono" :value="old('name')" placeholder="support" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="description" value="Description" />
                        <x-text-input id="description" name="description" class="mt-1 block w-full" :value="old('description')" />
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button>Create role</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
