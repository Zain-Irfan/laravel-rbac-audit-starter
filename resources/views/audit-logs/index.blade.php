<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Audit Log') }}</h2>
        <p class="mt-1 text-sm text-gray-500">Every create, update and delete on audited models, plus role and permission changes.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-2 gap-3 md:grid-cols-6 md:items-end">
                <div>
                    <x-input-label for="user" value="User" />
                    <select id="user" name="user" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Anyone</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user'] ?? null) == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="model" value="Model" />
                    <select id="model" name="model" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All models</option>
                        @foreach ($types as $alias => $label)
                            <option value="{{ $alias }}" @selected(($filters['model'] ?? null) === $alias)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="event" value="Event" />
                    <select id="event" name="event" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All events</option>
                        @foreach ($events as $event)
                            <option value="{{ $event }}" @selected(($filters['event'] ?? null) === $event)>{{ \Illuminate\Support\Str::headline($event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" value="From" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full text-sm" :value="$filters['from'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="to" value="To" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full text-sm" :value="$filters['to'] ?? ''" />
                </div>
                <div class="flex gap-2">
                    <x-primary-button>Filter</x-primary-button>
                    <a href="{{ route('audit-logs.index') }}" class="inline-flex items-center px-3 text-sm text-gray-500 hover:text-gray-800">Reset</a>
                </div>
                <x-input-error :messages="$errors->all()" class="col-span-full" />
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">When</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Event</th>
                                <th class="px-4 py-3">Record</th>
                                <th class="px-4 py-3 w-1/2">Changes</th>
                                <th class="px-4 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr class="align-top">
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600" title="{{ $log->created_at }}">
                                        {{ $log->created_at->format('M j, Y H:i') }}
                                        <div class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->user?->name ?? 'System' }}</td>
                                    <td class="px-4 py-3"><x-event-badge :event="$log->event" /></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('audit-logs.history', [$log->auditable_type, $log->auditable_id]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ $log->modelLabel() }} #{{ $log->auditable_id }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">@include('audit-logs._changes')</td>
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-gray-500">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-500">No audit entries match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
