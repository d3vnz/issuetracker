<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Filament\Resources;

use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\RelationManagers\CommentsRelationManager;
use D3vnz\IssueTracker\Models\Issue;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\ListIssues;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\CreateIssue;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\EditIssue;
use Illuminate\Support\Facades\Mail;
class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static ?string $slug = 'issues';


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Issue::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return Issue::query()->whereNull('deleted_at');
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Issue Title'),
                TextColumn::make('author.name')
                    ->label('Logged By'),
                \Filament\Tables\Columns\IconColumn::make('has_notes')
                    ->label('Comments')
                    ->boolean()
                    ->trueIcon('la-comment-solid')
                    ->falseIcon('heroicon-o-x-mark')
                    ->state(function ($record) {
                        return $record->comments()->exists();
                    }),
                TextColumn::make('state')
                    ->label('Status')
                    ->color(function($state){
                        if($state == 'open'){
                            return 'gray';
                        }else{
                            return 'success';
                        }
                    })
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->alignRight()
            ])
            ->actions([
                Action::make('Close Issue')
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


                    }),
                Action::make('Reopen Issue')
                    ->visible(function(?Model $record){
                        return $record->state != 'open';
                    })
                    ->form(function(){
                        return [
                            Forms\Components\RichEditor::make('body')
                                ->required()


                            ->columnSpanFull()
                            ->label('Reason for Reopening')
                        ];
                    })
                    ->action(function(array $data, ?Model $record){

                        $record->update([
                            'state' => 'open',
                            'closed_at' => null
                        ]);
                        $record->updateIssue($record->number, [
                            'state' => 'open'
                        ]);

                        if(isset($data['body']) && $data['body'] != ''){
                            $res = $record->setComment($record, $data);
                            $comment = $record->comments()->create([
                                'id' => $res['id'],
                                'body' => $data['body'],
                                'user_id' => auth()->id(),
                            ]);
                            Mail::to('joel@d3v.nz')->send(new \D3vnz\IssueTracker\Mail\Issue\Comment($record, $comment, auth()->user()));
                        }


                    })
            ])
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('state')
                    ->placeholder('Request Status')
                    ->trueLabel('Open')
                    ->falseLabel('Closed')
                    ->queries(
                        true: fn (Builder $query) => $query->where('state','open'),
                        false: fn (Builder $query) => $query->where('state','closed'),
                        blank: fn (Builder $query) => $query, // In this example, we do not want to filter the query when it is blank.
                    )
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIssues::route('/'),
            'create' => CreateIssue::route('/create'),
            'edit' => EditIssue::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
