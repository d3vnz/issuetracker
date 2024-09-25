<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */
namespace D3vnz\IssueTracker\Filament\Resources\IssueResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\RichEditor::make('body')
                    ->required()
                    ->maxLength(255)
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('issue.title')
            ->columns([
                Stack::make([

                    Tables\Columns\TextColumn::make('body')
                        ->formatStateUsing(function ($state) {
                            return new HtmlString($state);
                        }),
                    Tables\Columns\TextColumn::make('author.name')
                        ->formatStateUsing(function ($state, ?Model $record) {
                            if ($state != null)
                                return new HtmlString('<span style="color:gray">by<strong> ' . $state . '</strong> at ' . $record->created_at->format('D dS M \a\t h:ia') . '</span>');

                        })
                        ->label('Author'),
                    Tables\Columns\TextColumn::make('id')
                        ->formatStateUsing(function (?Model $record) {

                            return new HtmlString('<span style="color:gray">by<strong> D3V Services Limited </strong> at ' . $record->created_at->format('D dS M \a\t h:ia') . '</span>');

                        })
                        ->visible(fn(?Model $record) => !isset($record->user_id) || $record->user_id == null),
                ])
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add Comment')->modalHeading('Create Comment')
                    ->action(function (?Model $record, $data) {
                        $issue = $this->getOwnerRecord();
                        $res = $issue->setComment($issue, $data);


                        $comment = $issue->comments()->create([
                            'id' => $res['id'],
                            'body' => $data['body'],
                            'user_id' => auth()->id(),
                        ]);
                        Mail::to('joel@d3v.nz')->send(new \D3vnz\IssueTracker\Mail\Issue\Comment($issue, $comment, auth()->user()));
                        return $comment;
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()->label('Edit Comment')->modalHeading('Edit Issue Comment')
                        ->action(function (?Model $record, $data) {

                            $record->setComment($record, $data, $record->id);
                            return $record->update([
                                'body' => $data['body'],
                                'user_id' => auth()->id(),
                            ]);
                        })
                    ,
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->action(function (?Model $record) {
                            $record->removeComment($record->id);
                            $record->delete();
                        })
                ]),
            ]);
    }
}
