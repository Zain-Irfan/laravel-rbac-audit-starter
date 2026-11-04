<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $user = auth()->user();
        $groups = App\Models\Permission::orderBy('id')->get()->groupBy('group');
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-3">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Signed in as</h3>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ $user->name }}</p>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($user->roles as $role)
                        <x-badge :color="$role->isSuper() ? 'indigo' : 'gray'">{{ $role->label }}</x-badge>
                    @empty
                        <span class="text-sm text-gray-400">No role assigned</span>
                    @endforelse
                </div>

                @role(config('rbac.super_role'))
                    <p class="mt-4 rounded-md bg-indigo-50 p-3 text-sm text-indigo-800">
                        Your role bypasses every permission check.
                    </p>
                @endrole
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 lg:col-span-2">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Your effective permissions</h3>
                <p class="mt-1 text-sm text-gray-500">Log in as a different demo user to see the UI change.</p>

                <div class="mt-4 grid gap-6 sm:grid-cols-2">
                    @foreach ($groups as $group => $permissions)
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $group }}</h4>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($permissions as $permission)
                                    @can($permission->name)
                                        <li class="flex items-center gap-2 text-sm text-gray-800">
                                            <span class="text-green-600" aria-hidden="true">&#10003;</span>
                                            {{ $permission->label }}
                                            <span class="sr-only">(granted)</span>
                                        </li>
                                    @else
                                        <li class="flex items-center gap-2 text-sm text-gray-400">
                                            <span aria-hidden="true">&#10005;</span>
                                            <span class="line-through decoration-gray-300">{{ $permission->label }}</span>
                                            <span class="sr-only">(not granted)</span>
                                        </li>
                                    @endcan
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
