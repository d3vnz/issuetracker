<x-filament-panels::page>
    @php
        $rows = $this->getTicketmateRows();
        $counts = $this->getTicketmateCounts();
        $activeFilter = $this->ticketmateFilter ?? 'open';
        // Inline kind/state colour pairs — done as raw CSS rather than Tailwind
        // classes so this works regardless of whether the host app's Tailwind
        // build includes our package views in its content paths.
        $kindColors = [
            'bug' =>      ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
            'feature' =>  ['bg' => '#d1fae5', 'fg' => '#047857'],
            'change' =>   ['bg' => '#fef3c7', 'fg' => '#b45309'],
            'question' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
        ];
        $stateColors = [
            'received' =>      ['bg' => '#f3f4f6', 'fg' => '#374151'],
            'investigating' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
            'implementing' =>  ['bg' => '#fef3c7', 'fg' => '#b45309'],
            'pending_review' => ['bg' => '#e0e7ff', 'fg' => '#4338ca'],
            'deployed' =>      ['bg' => '#d1fae5', 'fg' => '#047857'],
        ];
    @endphp

    {{-- Self-contained styles. CSS variables flip on .dark or [data-theme=dark]
         so we follow whichever convention Filament uses. Custom-property values
         use inherit-friendly RGB so the theme switch is instant. --}}
    <style>
        .tm-list { --tm-fg: #111827; --tm-fg-muted: #6b7280; --tm-fg-subtle: #9ca3af;
                   --tm-bg: #ffffff; --tm-bg-alt: #f9fafb; --tm-border: #e5e7eb;
                   --tm-link: #2563eb; --tm-link-hover: #1d4ed8;
                   --tm-row-hover: #f9fafb; }
        .dark .tm-list, [data-theme="dark"] .tm-list {
                   --tm-fg: #f3f4f6; --tm-fg-muted: #9ca3af; --tm-fg-subtle: #6b7280;
                   --tm-bg: #111827; --tm-bg-alt: #1f2937; --tm-border: #374151;
                   --tm-link: #60a5fa; --tm-link-hover: #93c5fd;
                   --tm-row-hover: rgba(255,255,255,0.03); }
        .tm-list .tm-card     { border: 1px solid var(--tm-border); background: var(--tm-bg); border-radius: 12px; overflow: hidden; }
        .tm-list table        { width: 100%; font-size: 14px; border-collapse: collapse; }
        .tm-list thead        { background: var(--tm-bg-alt); }
        .tm-list th           { text-align: left; padding: 10px 16px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tm-fg-muted); font-weight: 600; }
        .tm-list td           { padding: 12px 16px; vertical-align: top; border-top: 1px solid var(--tm-border); color: var(--tm-fg); }
        .tm-list tbody tr:hover { background: var(--tm-row-hover); }
        .tm-list .tm-title    { font-weight: 600; color: var(--tm-fg); }
        .tm-list .tm-meta     { font-size: 11px; color: var(--tm-fg-muted); margin-top: 2px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .tm-list .tm-summary  { font-size: 12px; color: var(--tm-fg-muted); max-width: 22rem; }
        .tm-list .tm-age      { font-size: 11px; color: var(--tm-fg-subtle); white-space: nowrap; text-align: right; }
        .tm-list .tm-empty    { padding: 32px; text-align: center; font-size: 14px; color: var(--tm-fg-muted); }
        .tm-list .tm-badge    { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 500; line-height: 1.4; }
        .tm-list .tm-link     { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: var(--tm-link); text-decoration: none; }
        .tm-list .tm-link:hover { color: var(--tm-link-hover); text-decoration: underline; }
        .tm-list .tm-link.tm-link-muted { color: var(--tm-fg-muted); margin-left: 8px; }
        .tm-list .tm-toolbar  { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 16px; }
        .tm-list .tm-tabs     { display: inline-flex; gap: 4px; padding: 4px; border-radius: 10px; background: var(--tm-bg-alt); border: 1px solid var(--tm-border); }
        .tm-list .tm-tab      { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; color: var(--tm-fg-muted); background: transparent; border: 0; cursor: pointer; }
        .tm-list .tm-tab:hover:not(.tm-tab-active) { color: var(--tm-fg); background: rgba(0,0,0,0.04); }
        .dark .tm-list .tm-tab:hover:not(.tm-tab-active), [data-theme="dark"] .tm-list .tm-tab:hover:not(.tm-tab-active) { background: rgba(255,255,255,0.04); }
        .tm-list .tm-tab-active { color: #ffffff; background: #2563eb; }
        .tm-list .tm-tab-count { font-size: 11px; opacity: 0.75; font-variant-numeric: tabular-nums; }
        .tm-list .tm-btn      { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; background: #2563eb; color: #ffffff; font-size: 14px; font-weight: 600; border: 0; cursor: pointer; }
        .tm-list .tm-btn:hover:not(:disabled) { background: #1d4ed8; }
        .tm-list .tm-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .tm-list .tm-spin     { animation: tm-spin 1s linear infinite; }
        @keyframes tm-spin { to { transform: rotate(360deg); } }
    </style>

    <div class="tm-list">
        <div class="tm-toolbar">
            <div class="tm-tabs" role="tablist">
                @foreach (['open' => 'Open', 'closed' => 'Closed', 'all' => 'All'] as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        wire:click="setTicketmateFilter('{{ $key }}')"
                        class="tm-tab {{ $activeFilter === $key ? 'tm-tab-active' : '' }}"
                        aria-selected="{{ $activeFilter === $key ? 'true' : 'false' }}"
                    >
                        {{ $label }}
                        <span class="tm-tab-count">({{ $counts[$key] ?? 0 }})</span>
                    </button>
                @endforeach
            </div>
            <div style="display:flex;gap:8px">
                {{-- Mounts the same Filament Action defined in
                     ListIssues::getHeaderActions() — opens the standard
                     "Report a bug / new issue" modal in both v3 and v5. --}}
                <button type="button" wire:click="mountAction('createIssue')" class="tm-btn">
                    <svg style="width:16px;height:16px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Issue
                </button>
                <button type="button" wire:click="refreshTicketmate" wire:loading.attr="disabled" class="tm-btn" style="background:#475569">
                    <svg style="width:16px;height:16px" wire:loading.remove wire:target="refreshTicketmate" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <svg style="width:16px;height:16px" class="tm-spin" wire:loading wire:target="refreshTicketmate" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" style="opacity:0.75"/></svg>
                    Refresh from TicketMate
                </button>
            </div>
        </div>

        <div class="tm-card">
            @if (empty($rows))
                <div class="tm-empty">
                    @if ($activeFilter === 'closed')
                        No closed issues in TicketMate yet.
                    @elseif ($activeFilter === 'all')
                        No issues yet. Issues created from this app via the Report-a-Bug modal will appear here.
                    @else
                        No open issues. Switch to <strong>Closed</strong> or <strong>All</strong> to see resolved tickets.
                    @endif
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>AI summary</th>
                            <th style="text-align:right">Age</th>
                            <th style="text-align:right">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td style="max-width:24rem">
                                    <div class="tm-title">{{ $row['title'] ?? '(untitled)' }}</div>
                                    @if (! empty($row['ticket_number']))
                                        <div class="tm-meta">{{ $row['ticket_number'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['kind']))
                                        @php $c = $kindColors[$row['kind']] ?? ['bg' => '#f3f4f6', 'fg' => '#374151']; @endphp
                                        <span class="tm-badge" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }}">{{ ucfirst($row['kind']) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['workflow_state']))
                                        @php $c = $stateColors[$row['workflow_state']] ?? ['bg' => '#f3f4f6', 'fg' => '#374151']; @endphp
                                        <span class="tm-badge" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }}">{{ ucwords(str_replace('_', ' ', $row['workflow_state'])) }}</span>
                                    @endif
                                </td>
                                <td class="tm-summary">{{ \Illuminate\Support\Str::limit($row['ai_summary'] ?? '—', 120) }}</td>
                                <td class="tm-age">
                                    @if (! empty($row['updated_at']))
                                        {{ \Illuminate\Support\Carbon::parse($row['updated_at'])->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="text-align:right;white-space:nowrap">
                                    @if (! empty($row['id']))
                                        <button
                                            type="button"
                                            wire:click="openInTicketmate({{ (int) $row['id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="openInTicketmate({{ (int) $row['id'] }})"
                                            class="tm-link"
                                            style="background:transparent;border:0;cursor:pointer;padding:0"
                                            title="Sign you in to TicketMate as {{ auth()->user()?->email }} and open this ticket"
                                        >
                                            TicketMate
                                            <svg style="width:12px;height:12px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7m0-7L10 14M5 5v14h14"/></svg>
                                        </button>
                                    @endif
                                    @if (! empty($row['github_url']))
                                        <a href="{{ $row['github_url'] }}" target="_blank" class="tm-link tm-link-muted">GitHub</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-filament-panels::page>
