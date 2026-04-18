<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Livewire\Global;

use D3vnz\IssueTracker\Mail\Issue\Confirmation;
use D3vnz\IssueTracker\Mail\Issue\Notification as MailNotification;
use D3vnz\IssueTracker\Models\Issue;
use D3vnz\IssueTracker\Services\TicketmateClient;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class IssueQuickAction extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function createIssueAction(): Action
    {
        return Action::make('createIssue')
            ->modalHeading(fn (array $arguments) => 'Report ' . ucwords($arguments['type'] ?? 'an Issue'))
            ->form(fn (array $arguments) => Issue::getForm($arguments['type'] ?? null))
            ->action(function (array $data) {
                $kind = $data['labels']['name'] ?? 'bug';
                $user = auth()->user();
                $creatorName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? null);

                if (TicketmateClient::isEnabled()) {
                    // Centralised mode: TicketMate creates the GitHub issue with its own
                    // token, then mirrors a Ticket. The consuming app needs no GITHUB_TOKEN.
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

                    // Mirror into local cache so the existing IssueResource UI sees it
                    // immediately (the next ticketmate:sync run also catches it).
                    Issue::updateOrCreate(
                        ['number' => (int) $created['github_issue_number']],
                        [
                            'title' => $data['title'],
                            'body' => $data['body'],
                            'user_id' => $user?->id,
                            'state' => 'open',
                            'status' => 'received',
                            'kind' => $kind,
                            'labels' => ['name' => $kind, 'color' => null, 'id' => null],
                        ],
                    );
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

    public function render()
    {
        return view('d3vnz-issuetracker::livewire.global.issue-quick-action');
    }
}
