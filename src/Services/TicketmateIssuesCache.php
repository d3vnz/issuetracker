<?php

namespace D3vnz\IssueTracker\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 10-minute cache of issues mirrored from a central TicketMate.
 *
 * Issues are stored ONLY in cache (Redis when configured, otherwise the
 * default cache store). The package writes nothing to a local database.
 *
 * Each issue is a plain array with at minimum a unique `key`, plus the
 * fields returned by TicketMate's /api/v1/issues endpoint, plus a derived
 * `ticketmate_url` for click-through.
 */
class TicketmateIssuesCache
{
    public const CACHE_KEY = 'd3vnz.issuetracker.ticketmate.issues';
    public const STAMP_KEY = 'd3vnz.issuetracker.ticketmate.issues.stamp';
    public const TTL_MINUTES = 10;

    public function __construct(protected TicketmateClient $client) {}

    /**
     * Returns the cached issues filtered by lifecycle state.
     *
     * The cache itself ALWAYS stores the full set (open + closed) — we
     * filter on read so toggling between Open / Closed / All is instant
     * with no extra TicketMate round-trip.
     *
     * @param  string  $filter  'open' (default) | 'closed' | 'all'
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    public function all(string $filter = 'open'): Collection
    {
        if (! TicketmateClient::isEnabled()) {
            return collect();
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (! is_array($cached) || ! $this->isFresh()) {
            $cached = $this->refresh();
        }

        $rows = collect($cached);

        return match ($filter) {
            'closed' => $rows->filter(fn (array $r) => in_array($r['status'] ?? '', ['resolved', 'closed'], true))->values(),
            'all'    => $rows->values(),
            default  => $rows->filter(fn (array $r) => ! in_array($r['status'] ?? '', ['resolved', 'closed'], true))->values(),
        };
    }

    public function isFresh(): bool
    {
        $stamp = Cache::get(self::STAMP_KEY);
        if (! $stamp) return false;
        try {
            return Carbon::parse($stamp)->gt(now()->subMinutes(self::TTL_MINUTES));
        } catch (\Throwable) {
            return false;
        }
    }

    public function bust(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::STAMP_KEY);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function refresh(): array
    {
        if (! TicketmateClient::isEnabled()) return [];

        $rows = $this->client->listIssues(includeClosed: true, limit: 100);
        $base = rtrim((string) config('issuetracker.ticketmate.url'), '/');

        $issues = collect($rows)
            ->map(function (array $r) use ($base) {
                // Prefer the URL the API gave us (TicketMate v11+ returns a
                // signed magic-link that auto-logs portal-enabled requesters
                // into /portal/tickets/{id}). Fall back to the staff /admin
                // URL only when the API didn't include one.
                $url = $r['ticketmate_url'] ?? ($base . '/admin/tickets/' . ($r['id'] ?? ''));
                return array_merge($r, [
                    'key' => (string) ($r['id'] ?? $r['github_issue_number'] ?? uniqid()),
                    'ticketmate_url' => $url,
                ]);
            })
            ->all();

        // Cache slightly longer than the TTL so a brief outage doesn't drop the snapshot.
        Cache::put(self::CACHE_KEY, $issues, now()->addMinutes(self::TTL_MINUTES + 5));
        Cache::put(self::STAMP_KEY, now()->toIso8601String(), now()->addMinutes(self::TTL_MINUTES + 5));

        return $issues;
    }
}
