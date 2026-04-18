<?php

namespace D3vnz\IssueTracker\Console\Commands;

use D3vnz\IssueTracker\Models\Issue;
use D3vnz\IssueTracker\Services\TicketmateClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * When TicketMate is configured, this command replaces `github:sync-issues`.
 *
 * Pulls every issue (with workflow_state, status, AI summary, etc.) from
 * TicketMate and mirrors them into the local `issues` table — creating
 * missing rows as well as updating existing ones — so the existing
 * IssueResource UI keeps working unchanged but the source of truth is now
 * TicketMate (which itself watches GitHub via webhook).
 */
class PullFromTicketmate extends Command
{
    protected $signature = 'ticketmate:sync
                            {--since= : ISO timestamp to fetch only changes since (defaults to last successful run)}
                            {--full : Ignore the saved cursor and pull everything}';

    protected $description = 'Mirror issues from a central TicketMate instance into the local issues cache.';

    public function handle(TicketmateClient $client): int
    {
        if (! TicketmateClient::isEnabled()) {
            $this->warn('TicketMate is not configured — set TICKETMATE_API_URL and TICKETMATE_API_TOKEN in .env.');
            return self::INVALID;
        }

        if ($this->option('full')) {
            $since = null;
        } else {
            $since = $this->option('since')
                ?: cache('ticketmate.last_pull')
                ?: now()->subDays(60)->toIso8601String();
        }

        $rows = $client->listIssues(since: $since, includeClosed: true, limit: 100);
        if (empty($rows)) {
            $this->info('No new ticket activity since ' . ($since ?: 'beginning of time'));
            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $number = (int) ($row['github_issue_number'] ?? 0);
            if (! $number) continue;

            $attrs = [
                'title' => $row['title'] ?? '(no title)',
                'status' => $row['workflow_state'] ?? 'received',
                'state' => in_array($row['status'] ?? '', ['resolved', 'closed'], true) ? 'closed' : 'open',
                'kind' => $row['kind'] ?? 'bug',
                'closed_at' => $this->parseClosedAt($row),
                'status_changed_at' => isset($row['updated_at']) ? Carbon::parse($row['updated_at']) : null,
                'labels' => [
                    'name' => $row['kind'] ?? 'bug',
                    'color' => null,
                    'id' => null,
                ],
            ];

            $issue = Issue::where('number', $number)->first();
            if ($issue) {
                $issue->forceFill($attrs)->save();
                $updated++;
            } else {
                $attrs['number'] = $number;
                Issue::create($attrs);
                $created++;
            }
        }

        cache()->put('ticketmate.last_pull', now()->toIso8601String(), now()->addDays(60));
        $this->info("TicketMate sync: created {$created}, updated {$updated}.");

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
