<?php

namespace D3vnz\IssueTracker\Console\Commands;

use D3vnz\IssueTracker\Models\Issue;
use D3vnz\IssueTracker\Services\TicketmateClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * When TicketMate is configured, this command replaces `github:sync-issues`.
 *
 * It pulls ticket state (status, workflow_state, closed_at, AI summary)
 * from TicketMate and mirrors it into the local `issues` table so the
 * existing IssueResource keeps working unchanged — but the source of
 * truth is now TicketMate (which itself watches GitHub via webhook).
 */
class PullFromTicketmate extends Command
{
    protected $signature = 'ticketmate:sync
                            {--since= : ISO timestamp to fetch only changes since (defaults to last successful run)}';

    protected $description = 'Mirror issue state from a central TicketMate instance into the local issues cache.';

    public function handle(TicketmateClient $client): int
    {
        if (! TicketmateClient::isEnabled()) {
            $this->warn('TicketMate is not configured — set TICKETMATE_API_URL and TICKETMATE_API_TOKEN in .env.');
            return self::INVALID;
        }

        $since = $this->option('since')
            ?: cache('ticketmate.last_pull')
            ?: now()->subDays(30)->toIso8601String();

        $rows = $client->listIssues(since: $since, includeClosed: true, limit: 100);
        if (empty($rows)) {
            $this->info('No new ticket activity since ' . $since);
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($rows as $row) {
            $number = (int) ($row['github_issue_number'] ?? 0);
            if (! $number) continue;

            $issue = Issue::where('number', $number)->first();
            if (! $issue) continue; // local row not yet seeded — skip silently

            $issue->forceFill([
                'status' => $row['workflow_state'] ?? $issue->status,
                'state' => in_array($row['status'] ?? '', ['resolved', 'closed'], true) ? 'closed' : 'open',
                'closed_at' => $this->parseClosedAt($row),
                'status_changed_at' => isset($row['updated_at']) ? Carbon::parse($row['updated_at']) : $issue->status_changed_at,
            ])->save();

            $updated++;
        }

        cache()->put('ticketmate.last_pull', now()->toIso8601String(), now()->addDays(30));
        $this->info("Updated {$updated} local issue(s) from TicketMate.");

        return self::SUCCESS;
    }

    private function parseClosedAt(array $row): ?Carbon
    {
        if (in_array($row['status'] ?? '', ['resolved', 'closed'], true)) {
            return isset($row['updated_at']) ? Carbon::parse($row['updated_at']) : now();
        }
        return null;
    }
}
