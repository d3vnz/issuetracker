<?php

namespace D3vnz\IssueTracker\Console\Commands;

use D3vnz\IssueTracker\Services\TicketmateClient;
use D3vnz\IssueTracker\Services\TicketmateIssuesCache;
use Illuminate\Console\Command;

/**
 * Forces a refresh of the cached issues snapshot from TicketMate.
 *
 * The IssueResource lazily refreshes on-render every 10 minutes anyway,
 * so this command is mainly a cron safety-net and a manual reload trigger.
 */
class PullFromTicketmate extends Command
{
    protected $signature = 'ticketmate:sync {--quiet-on-empty}';

    protected $description = 'Refresh the cached issues from a central TicketMate instance.';

    public function handle(TicketmateIssuesCache $cache): int
    {
        if (! TicketmateClient::isEnabled()) {
            $this->warn('TicketMate is not configured — set TICKETMATE_API_URL and TICKETMATE_API_TOKEN in .env.');
            return self::INVALID;
        }

        $cache->bust();
        $rows = $cache->refresh();
        $count = count($rows);

        if ($count === 0 && $this->option('quiet-on-empty')) {
            return self::SUCCESS;
        }

        $this->info("TicketMate sync: cached {$count} issue(s).");
        return self::SUCCESS;
    }
}
