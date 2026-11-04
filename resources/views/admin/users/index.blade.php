<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Users') }}</h2>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                New user
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" class="mb-4 flex gap-2">
                <x-text-input name="search" type="search" :value="$search" placeholder="Search name or email" class="w-full sm:w-80 text-sm" />
                <x-secondary-button type="submit">Search</x-secondary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Roles</th>
                                <th class="px-4 py-3">Joined</th>
                                <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $user->name }}
                                        @if ($user->is(auth()->user()))
                                            <span class="text-xs text-gray-400">(you)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        @forelse ($user->roles as $role)
                                            <x-badge :color="$role->isSuper() ? 'indigo' : 'gray'">{{ $role->label }}</x-badge>
                                        @empty
                                            <span class="text-gray-400">No role</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $user->created_at->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-3">
                                            @can('audit.view')
                                                <a href="{{ route('audit-logs.history', ['user', $user->id]) }}" class="text-gray-500 hover:text-gray-800">History</a>
                                            @endcan
                                            <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                                            @unless ($user->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete {{ addslashes($user->name) }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-500">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
