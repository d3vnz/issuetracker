<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Console\Commands;

use D3vnz\IssueTracker\Mail\Issue\Closure;
use D3vnz\IssueTracker\Mail\Issue\Comment;
use D3vnz\IssueTracker\Mail\Issue\StatusUpdate;
use D3vnz\IssueTracker\Models\Issue;
use D3vnz\IssueTracker\Models\IssueComment;
use D3vnz\IssueTracker\Services\TicketmateClient;
use App\Models\User;
use D3vnz\IssueTracker\Traits\GithubTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SyncIssuesWithGithub extends Command
{
    use GithubTrait;

    protected $signature = 'github:sync-issues';

    protected $description = 'Sync issues from GitHub and emit notifications for status/state transitions.';

    public function handle()
    {
        if (TicketmateClient::isEnabled()) {
            $this->info('TicketMate is enabled — local GitHub sync is disabled. Use ticketmate:sync to refresh the cache.');
            return self::SUCCESS;
        }

        $issues = $this->getIssues();
        if (! is_array($issues) || count($issues) === 0) {
            return;
        }

        $statusesEnabled = (bool) config('issuetracker.statuses.enabled', true);
        $defaultStatus = (string) config('issuetracker.statuses.default', 'received');
        $emailOnTransition = (bool) config('issuetracker.statuses.email_on_sync_transition', true);

        foreach ($issues as $issue) {
            $rawLabels = is_array($issue['labels'] ?? null) ? $issue['labels'] : [];
            $githubStatus = Issue::extractStatusFromLabels($rawLabels);
            $kindLabel = Issue::firstKindLabel($rawLabels) ?? [
                'name' => 'bug',
                'color' => null,
                'id' => null,
            ];

            $existing = Issue::find($issue['id']);
            $previousState = $existing?->state;
            $previousStatus = $existing?->status;

            if ($statusesEnabled && $githubStatus === null) {
                try {
                    $this->setIssueStatusLabel($issue['number'], $defaultStatus);
                    $githubStatus = $defaultStatus;
                } catch (\Throwable $e) {
                    $this->warn("Could not seed status label on issue #{$issue['number']}: {$e->getMessage()}");
                }
            }

            $attributes = [
                'number' => $issue['number'],
                'title' => $issue['title'],
                'body' => $issue['body'],
                'state' => $issue['state'],
                'labels' => $kindLabel,
                // Mirror the kind label name to its own column so we can index +
                // query without poking JSON. Schema added in
                // 2026_04_17_000001_add_kind_to_issues.
                'kind' => $kindLabel['name'] ?? null,
                'created_at' => $issue['created_at'],
                'updated_at' => $issue['updated_at'],
            ];

            if ($statusesEnabled) {
                $attributes['status'] = $githubStatus;
                if ($previousStatus !== $githubStatus) {
                    $attributes['status_changed_at'] = now();
                }
            }

            if ($issue['state'] === 'closed' && $previousState !== 'closed') {
                $attributes['closed_at'] = $issue['closed_at'] ?? now();
            } elseif ($issue['state'] === 'open' && $previousState === 'closed') {
                $attributes['closed_at'] = null;
            }

            $recentlyTransitioned = $existing ? $existing->recentlyTransitioned() : false;

            $issue_res = Issue::updateOrCreate(['id' => $issue['id']], $attributes);

            if ($emailOnTransition && $existing && $issue_res->user_id && ! $recentlyTransitioned) {
                $this->dispatchTransitionMail($issue_res, $previousState, $previousStatus, $githubStatus);
            }

            if (($issue['comments'] ?? 0) > 0) {
                $comments = $this->getComments($issue);
                foreach ($comments as $comment) {
                    $comment_res = IssueComment::updateOrCreate([
                        'id' => $comment['id'],
                        'issue_id' => $issue['id'],
                    ], [
                        'body' => $comment['body'],
                        'created_at' => $comment['created_at'],
                        'updated_at' => $comment['updated_at'],
                    ]);
                    if ($comment_res->user_id == null && $issue_res->user_id != null && ! $comment_res->notified_author) {
                        Mail::to(User::find($issue_res->user_id))->send(new Comment($issue_res, $comment_res, $issue_res->author));
                        $comment_res->update(['notified_author' => true]);
                    }
                }
            }
        }
    }

    protected function dispatchTransitionMail(Issue $issue, ?string $previousState, ?string $previousStatus, ?string $githubStatus): void
    {
        $user = User::find($issue->user_id);
        if (! $user) {
            return;
        }

        if ($issue->state === 'closed' && $previousState !== 'closed') {
            Mail::to($user)->send(new Closure($user, $issue));
            return;
        }

        if (! config('issuetracker.statuses.enabled', true)) {
            return;
        }

        if ($githubStatus && $githubStatus !== $previousStatus && $previousStatus !== null) {
            Mail::to($user)->send(new StatusUpdate($user, $issue, $githubStatus));
        }
    }
}
