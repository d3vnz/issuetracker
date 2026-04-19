<?php
/*
 *
 *  * Copyright (c) D3V Services Limited on behalf of their client.
 *  * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 *
 */

namespace D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;

use D3vnz\IssueTracker\Filament\Resources\IssueResource;
use D3vnz\IssueTracker\Mail\Issue\Confirmation;
use D3vnz\IssueTracker\Mail\Issue\Notification as MailNotification;
use D3vnz\IssueTracker\Models\Issue;
use D3vnz\IssueTracker\Services\TicketmateClient;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Mail;

class ListIssues extends ListRecords
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createIssue')
                ->label('New Issue')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(fn () => ! TicketmateClient::isEnabled())
                ->modalHeading(fn (array $arguments) => 'Report ' . ($arguments['type'] ?? 'an Issue'))
                ->form(fn (array $arguments) => Issue::getForm($arguments['type'] ?? null))
                ->action(function (array $data) {
                    $issue = new Issue();
                    $res = $issue->createIssue([
                        'title' => $data['title'],
                        'body' => $data['body'],
                        'assignees' => ['aotearoait'],
                        'labels' => [
                            'name' => $data['labels']['name'],
                        ],
                    ]);

                    $record = Issue::create([
                        'id' => $res['id'],
                        'number' => $res['number'],
                        'title' => $data['title'],
                        'body' => $data['body'],
                        'user_id' => auth()->id(),
                        'state' => $res['state'],
                        'labels' => [
                            'name' => $res['labels'][0]['name'] ?? 'bug',
                            'color' => $res['labels'][0]['color'] ?? null,
                            'id' => $res['labels'][0]['id'] ?? null,
                        ],
                    ]);

                    if (! TicketmateClient::isEnabled()) {
                        if (! config('issuetracker.ai.enabled')) {
                            Mail::to(auth()->user())->send(new Confirmation(auth()->user(), $record));
                        }
                        Mail::to('joel@d3v.nz')->send(new MailNotification(auth()->user(), $record, $res));
                    }

                    Notification::make()
                        ->title('Your ' . ucwords($data['labels']['name']) . ' has been created')
                        ->body('A developer will respond to you if required and you will be notified via email as well of any updates.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
