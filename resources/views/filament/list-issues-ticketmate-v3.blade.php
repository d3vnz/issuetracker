<x-filament-panels::page>
    @php
        $rows = $this->getTicketmateRows();
        $kindColors = [
            'bug' => 'bg-red-100 text-red-700',
            'feature' => 'bg-emerald-100 text-emerald-700',
            'change' => 'bg-amber-100 text-amber-700',
            'question' => 'bg-blue-100 text-blue-700',
        ];
        $stateColors = [
            'received' => 'bg-gray-100 text-gray-700',
            'investigating' => 'bg-blue-100 text-blue-700',
            'implementing' => 'bg-amber-100 text-amber-700',
            'pending_review' => 'bg-indigo-100 text-indigo-700',
            'deployed' => 'bg-emerald-100 text-emerald-700',
        ];
    @endphp

    <div class="flex justify-end mb-4">
        <button
            type="button"
            wire:click="refreshTicketmate"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-primary-600 text-white text-sm font-semibold hover:bg-primary-500 disabled:opacity-50"
        >
            <svg class="w-4 h-4" wire:loading.remove wire:target="refreshTicketmate" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            <svg class="w-4 h-4 animate-spin" wire:loading wire:target="refreshTicketmate" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
            Refresh from TicketMate
        </button>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 overflow-hidden">
        @if (empty($rows))
            <div class="p-8 text-center text-sm text-gray-500">
                No issues yet. Issues created from this app via the Report-a-Bug modal will appear here.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="text-[10px] uppercase tracking-wider text-gray-500 bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="text-left px-4 py-3">Title</th>
                        <th class="text-left px-4 py-3">Type</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">AI summary</th>
                        <th class="text-right px-4 py-3">Age</th>
                        <th class="text-right px-4 py-3">Open</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 align-top max-w-md">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['title'] ?? '(untitled)' }}</div>
                                @if (! empty($row['ticket_number']))
                                    <div class="text-[11px] font-mono text-gray-500 mt-0.5">{{ $row['ticket_number'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if (! empty($row['kind']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $kindColors[$row['kind']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($row['kind']) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if (! empty($row['workflow_state']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $stateColors[$row['workflow_state']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ ucwords(str_replace('_', ' ', $row['workflow_state'])) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-gray-600 dark:text-gray-400 max-w-sm">
                                {{ \Illuminate\Support\Str::limit($row['ai_summary'] ?? '—', 120) }}
                            </td>
                            <td class="px-4 py-3 align-top text-right text-xs text-gray-500 whitespace-nowrap">
                                @if (! empty($row['updated_at']))
                                    {{ \Illuminate\Support\Carbon::parse($row['updated_at'])->diffForHumans() }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-right whitespace-nowrap">
                                @if (! empty($row['ticketmate_url']))
                                    <a href="{{ $row['ticketmate_url'] }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-primary-600 hover:underline">
                                        TicketMate
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7m0-7L10 14M5 5v14h14"/></svg>
                                    </a>
                                @endif
                                @if (! empty($row['github_url']))
                                    <a href="{{ $row['github_url'] }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:underline ml-2">
                                        GitHub
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-filament-panels::page>
