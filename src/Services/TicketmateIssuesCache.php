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
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    public function all(): Collection
    {
        if (! TicketmateClient::isEnabled()) {
            return collect();
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && $this->isFresh()) {
            return collect($cached);
        }

        return collect($this->refresh());
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
            ->map(fn (array $r) => array_merge($r, [
                'key' => (string) ($r['id'] ?? $r['github_issue_number'] ?? uniqid()),
                'ticketmate_url' => $base . '/admin/tickets/' . ($r['id'] ?? ''),
            ]))
            ->all();

        // Cache slightly longer than the TTL so a brief outage doesn't drop the snapshot.
        Cache::put(self::CACHE_KEY, $issues, now()->addMinutes(self::TTL_MINUTES + 5));
        Cache::put(self::STAMP_KEY, now()->toIso8601String(), now()->addMinutes(self::TTL_MINUTES + 5));

        return $issues;
    }
}
