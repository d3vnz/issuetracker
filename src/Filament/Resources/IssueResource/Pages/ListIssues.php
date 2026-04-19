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

    /**
     * In Filament 3 we can't feed the table a cached array (no Table::records()),
     * so we render the TicketMate-cached issues via a custom Blade view that
     * bypasses the Filament table component. Filament 5 keeps using the
     * native ->records() API on the resource's table().
     */
    public function getView(): string
    {
        if (TicketmateClient::isEnabled() && ! method_exists(\Filament\Tables\Table::class, 'records')) {
            return 'd3vnz-issuetracker::filament.list-issues-ticketmate-v3';
        }
        return parent::getView();
    }

    /** Used by the v3 custom Blade only. */
    public function getTicketmateRows(): array
    {
        return app(\D3vnz\IssueTracker\Services\TicketmateIssuesCache::class)->all()->all();
    }

    public function refreshTicketmate(): void
    {
        $cache = app(\D3vnz\IssueTracker\Services\TicketmateIssuesCache::class);
        $cache->bust();
        $rows = $cache->refresh();
        Notification::make()
            ->title('Refreshed from TicketMate')
            ->body(count($rows) . ' issue(s) loaded.')
            ->success()
            ->send();
    }

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
