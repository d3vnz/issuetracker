<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */
namespace D3vnz\IssueTracker\Livewire\Global;

use App\Mail\Issue\Confirmation;
use App\Models\Issue;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class IssueTab extends Component implements HasForms, HasActions
{

    use InteractsWithActions;
    use InteractsWithForms;

    public function mount()
    {
        $this->form->fill();
    }

    public function render()
    {
        return view('d3vnz-issuetracker::livewire.global.issue-tab');
    }


    public function createQuickIssueAction(): Action
    {

        return Action::make('createIssue')
            ->form(function (array $arguments) {
                return Issue::getForm($arguments['type'] ?? null);
            })
            ->action(function (array $data) {
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
                    'user_id' => \App\Livewire\Global\auth()->id(),
                    'state' => $res['state'],
                    'labels' => [
                        'name' => $res['labels'][0]['name'] ?? 'bug',
                        'color' => $res['labels'][0]['color'] ?? null,
                        'id' => $res['labels'][0]['id'] ?? null
                    ]

                ]);

                Mail::to(\App\Livewire\Global\auth()->user())->send(new Confirmation(\App\Livewire\Global\auth()->user(), $record));
                Mail::to('joel@d3v.nz')->send(new \App\Mail\Issue\Notification(\App\Livewire\Global\auth()->user(), $record, $res));

                Notification::make()
                    ->title('Your ' . ucwords($data['labels']['name']) . ' has been created')
                    ->body('A developer will respond to you if required and you will be notified via email as well of any updates.')
                    ->success()
                    ->send();
            });

    }
}
