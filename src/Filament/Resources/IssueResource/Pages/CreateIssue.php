<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;

use D3vnz\IssueTracker\Filament\Resources\IssueResource;

use App\Models\Issue;
use D3vnz\IssueTracker\Mail\Issue\Confirmation;
use D3vnz\IssueTracker\Mail\Issue\Notification as MailNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;


    public function handleRecordCreation(array $data): Model
    {

        $issue = new Issue();
        $res = $issue->createIssue([
            'title' => $data['title'],
            'body' => $data['body'],
            'assignees' => ['aotearoait'],
            'labels' => [
                'name' => $data['labels']['name']
            ]
        ]);


        $record = Issue::create([
            'id' => $res['id'],
            'number' => $res['number'],
            'title' => $data['title'],
            'body' => $data['body'],
            'user_id' => auth()->id(),
            'state' => $res['state'],
            'labels' => collect($res['labels'])->map(function ($issue) {
                return [
                    'name' => $issue['labels'][0]['name'] ?? 'bug',
                    'color' => $issue['labels'][0]['color'] ?? null,
                    'id' => $issue['labels'][0]['id'] ?? null
                ];
            })
        ]);

        Mail::to(auth()->user())->send(new Confirmation(auth()->user(), $record));
        Mail::to('joel@d3v.nz')->send(new MailNotification(auth()->user(), $record, $res));

        Notification::make()
            ->title('Your ' . ucwords($data['labels']['name']) . ' has been created')
            ->body('A developer will respond to you if required and you will be notified via email as well of any updates.')
            ->success()
            ->send();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
