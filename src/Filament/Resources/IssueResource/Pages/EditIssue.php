<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;

use D3vnz\IssueTracker\Filament\Resources\IssueResource;
use D3vnz\IssueTracker\Mail\Issue\Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\Action as HeaderAction;

use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;



class EditIssue extends EditRecord
{
    protected static string $resource = IssueResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->updateIssue($record->number, [
            'title' => $data['title'],
            'body' => $data['body'],
        ]);
        $record->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'user_id' => $record == null ? auth()->id() : $record->user_id
        ]);


        Notification::make()
            ->title($data['labels']['name'] . ' has been updated')
            ->success()
            ->send();
        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->action(function (?Model $record) {
                $record->removeIssue($record);
                $record->delete();
                return redirect()->to(IssueResource::getUrl('index'));
            }),
            HeaderAction::make('Close Issue')

                    ->visible(function(?Model $record){
                        return $record->state != 'closed';
                    })
                        ->requiresConfirmation()
                        ->color('danger')

                        ->action(function(?Model $record){

                            $record->update([
                                'state' => 'closed',
                                'closed_at' => now()
                            ]);
                            $record->updateIssue($record->number, [
                                'state' => 'closed'
                            ]);

                            Mail::to(\App\Models\User::find($record->user_id))->send(new Closure(\App\Models\User::find($record->user_id),$record));


                            return redirect()->to(IssueResource::getUrl('index'));
                }),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
