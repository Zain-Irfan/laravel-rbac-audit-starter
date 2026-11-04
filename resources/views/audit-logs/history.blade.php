<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ \Illuminate\Support\Str::headline($type) }} #{{ $id }} &middot; History
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($subject)
                        Currently: <span class="font-medium text-gray-700">{{ $subject->name ?? $subject->label ?? '#'.$id }}</span>
                    @else
                        This record has been deleted. Its history is kept.
                    @endif
                </p>
            </div>
            <a href="{{ route('audit-logs.index', ['model' => $type]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to audit log</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <ol class="relative border-s border-gray-200 ms-3 space-y-6">
                @foreach ($logs as $log)
                    <li class="ms-6">
                        <span class="absolute -start-1.5 mt-2 h-3 w-3 rounded-full border-2 border-white
                            {{ ['created' => 'bg-green-500', 'updated' => 'bg-amber-500', 'deleted' => 'bg-red-500'][$log->event] ?? 'bg-indigo-500' }}"></span>
                        <div class="bg-white shadow-sm sm:rounded-lg p-4">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <x-event-badge :event="$log->event" />
                                <span class="font-medium text-gray-800">{{ $log->user?->name ?? 'System' }}</span>
                                <span class="text-gray-500" title="{{ $log->created_at }}">{{ $log->created_at->format('M j, Y H:i:s') }}</span>
                                @if ($log->ip_address)
                                    <span class="font-mono text-xs text-gray-400">{{ $log->ip_address }}</span>
                                @endif
                            </div>
                            <div class="mt-3">@include('audit-logs._changes')</div>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="mt-6">{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
