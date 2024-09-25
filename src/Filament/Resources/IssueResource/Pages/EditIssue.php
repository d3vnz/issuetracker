<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;

use App\Filament\Resources\IssueResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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
            'user_id' => auth()->id()
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
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
