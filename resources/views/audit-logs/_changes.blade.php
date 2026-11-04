{{-- Field-by-field diff of one audit entry. Expects $log. --}}
@php($changes = $log->changesFor(auth()->user()))

@if ($changes === [])
    <p class="text-sm text-gray-400">No field changes recorded.</p>
@else
    <table class="w-full table-fixed text-sm">
        <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="w-1/5 py-1 pe-4 font-medium">Field</th>
                <th class="w-2/5 py-1 pe-4 font-medium">Before</th>
                <th class="w-2/5 py-1 font-medium">After</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($changes as $change)
                <tr class="align-top">
                    <td class="py-1.5 pe-4 font-mono text-xs text-gray-600">{{ $change['field'] }}</td>
                    @if ($change['masked'])
                        <td colspan="2" class="py-1.5 text-gray-400 italic">
                            <span title="You don't have permission to see this field">&#128274; hidden</span>
                        </td>
                    @else
                        <td class="py-1.5 pe-4 text-red-700/90"><x-audit-value :value="$change['old']" /></td>
                        <td class="py-1.5 text-green-700"><x-audit-value :value="$change['new']" /></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
