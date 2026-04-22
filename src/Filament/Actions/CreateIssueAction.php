<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Filament\Actions;

use D3vnz\IssueTracker\Mail\Issue\Confirmation;
use D3vnz\IssueTracker\Mail\Issue\Notification as MailNotification;
use D3vnz\IssueTracker\Models\Issue;
use D3vnz\IssueTracker\Services\TicketmateClient;
use D3vnz\IssueTracker\Services\TicketmateIssuesCache;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/**
 * Single source of truth for the "Report a bug / new issue" Filament
 * Action. Used by:
 *   - IssueQuickAction (the floating quick-action component on every page)
 *   - ListIssues header (the consuming app's IssueResource list page)
 *   - Filament v3 blade toolbar (via wire:click="mountAction('createIssue')")
 *
 * Picks the right backend automatically: TicketMate-mode posts to the
 * central API (which creates GitHub + a Ticket); local-mode falls back
 * to the original direct-to-GitHub flow.
 */
class CreateIssueAction
{
    public static function make(string $name = 'createIssue'): Action
    {
        return Action::make($name)
            ->label('New Issue')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading(fn (array $arguments) => 'Report ' . ucwords($arguments['type'] ?? 'an Issue'))
            ->form(fn (array $arguments) => Issue::getForm($arguments['type'] ?? null))
            ->action(function (array $data) {
                $kind = $data['labels']['name'] ?? 'bug';
                $user = auth()->user();
                $creatorName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? null);

                if (TicketmateClient::isEnabled()) {
                    // Centralised mode: TicketMate creates the GitHub issue with
                    // its own token, then mirrors a Ticket. The consuming app
                    // needs no GITHUB_TOKEN.
                    $created = (new TicketmateClient())->createIssue(
                        title: $data['title'],
                        body: $data['body'],
                        kind: $kind,
                        creatorEmail: $user?->email,
                        creatorName: $creatorName,
                        creatorAppUrl: config('app.url'),
                    );

                    if (! $created) {
                        Notification::make()
                            ->title('Could not create issue')
                            ->body('TicketMate did not respond. Try again or contact support.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Bust the local cache so the next IssueResource render
                    // pulls fresh from TicketMate (now includes this new issue).
                    app(TicketmateIssuesCache::class)->bust();
                } else {
                    // Local-only fallback: original GitHub-direct flow.
                    $issue = new Issue();
                    $res = $issue->createIssue([
                        'title' => $data['title'],
                        'body' => $data['body'],
                        'assignees' => ['aotearoait'],
                        'labels' => ['name' => $kind],
                    ]);

                    $record = Issue::create([
                        'id' => $res['id'],
                        'number' => $res['number'],
                        'title' => $data['title'],
                        'body' => $data['body'],
                        'user_id' => $user?->id,
                        'state' => $res['state'],
                        'labels' => [
                            'name' => $res['labels'][0]['name'] ?? 'bug',
                            'color' => $res['labels'][0]['color'] ?? null,
                            'id' => $res['labels'][0]['id'] ?? null,
                        ],
                    ]);

                    if (! config('issuetracker.ai.enabled')) {
                        Mail::to($user)->send(new Confirmation($user, $record));
                    }
                    Mail::to('joel@d3v.nz')->send(new MailNotification($user, $record, $res));
                }

                Notification::make()
                    ->title('Your ' . ucwords($kind) . ' has been created')
                    ->body(TicketmateClient::isEnabled()
                        ? 'A developer will get back to you and you\'ll receive email updates as it progresses.'
                        : 'A developer will respond to you if required and you will be notified via email as well of any updates.')
                    ->success()
                    ->send();
            });
    }
}
