<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Filament\Resources;

use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\RelationManagers\CommentsRelationManager;
use D3vnz\IssueTracker\Mail\Issue\Comment;
use D3vnz\IssueTracker\Mail\Issue\StatusUpdate;
use D3vnz\IssueTracker\Models\Issue;
use Filament\Forms\Form;
use D3vnz\IssueTracker\Mail\Issue\Closure;
use Filament\Forms;
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
use Filament\Tables\Actions\ActionGroup;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static ?string $slug = 'issues';

    protected static ?string $navigationLabel = 'Application Issues';

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function getNavigationIcon(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-bug-ant';
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
            ->recordClasses(function (Model $record) {

                $titleClasses = str_contains(strtolower($record->title), 'urgent')
                    ? 'urgent'
                    : '';

                // Combine both sets of classes
                return trim("$titleClasses");
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->label('Issue Title'),
                TextColumn::make('labels.name')
                    ->alignCenter()
                    ->placeholder('None')
                    ->state(fn (?Model $record) => $record?->labels['name'] ?? null)
                    ->formatStateUsing(fn ($state) => $state ? ucfirst((string) $state) : null)
                    ->badge()
                    ->color(function(?Model $record){
                        if(isset($record->labels['name'])){
                            if($record->labels['name'] == 'bug'){
                                return 'danger';
                            }elseif($record->labels['name'] == 'feature'){
                                return 'success';
                            }elseif($record->labels['name'] == 'change'){
                                return 'warning';
                                }
                        }
                        return 'primary';
                    })
                    ->label('Request Type'),
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
                    ->formatStateUsing(fn($state) => ucfirst((string) $state))
                    ->color(function($state){
                        return match($state){
                            'open' => 'gray',
                            'investigating' => 'info',
                            'implementing' => 'warning',
                            'pending' => 'primary',
                            'closed' => 'success',
                            default => 'gray',
                        };
                    })
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Age')
                    ->since()
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('closed_at')
                    ->label('Closed')
                    ->since()
                    ->sortable()
                    ->placeholder('Sill Open')
                    ->alignEnd(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('markInvestigating')
                        ->label('Mark Investigating')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('info')
                        ->visible(fn(?Model $record) => $record && ! in_array($record->state, ['investigating', 'closed']))
                        ->form([
                            Forms\Components\RichEditor::make('note')
                                ->label('Optional note to include in the notification email')
                                ->columnSpanFull(),
                        ])
                        ->action(function (array $data, ?Model $record) {
                            $record->update(['state' => 'investigating']);
                            $user = \App\Models\User::find($record->user_id);
                            if ($user) {
                                Mail::to($user)->send(new StatusUpdate($user, $record, 'investigating', $data['note'] ?? null));
                            }
                        }),
                    Action::make('markImplementing')
                        ->label('Mark Implementing')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->color('warning')
                        ->visible(fn(?Model $record) => $record && ! in_array($record->state, ['implementing', 'closed']))
                        ->form([
                            Forms\Components\RichEditor::make('note')
                                ->label('Optional note to include in the notification email')
                                ->columnSpanFull(),
                        ])
                        ->action(function (array $data, ?Model $record) {
                            $record->update(['state' => 'implementing']);
                            $user = \App\Models\User::find($record->user_id);
                            if ($user) {
                                Mail::to($user)->send(new StatusUpdate($user, $record, 'implementing', $data['note'] ?? null));
                            }
                        }),
                    Action::make('markPending')
                        ->label('Mark Pending')
                        ->icon('heroicon-o-clock')
                        ->color('primary')
                        ->visible(fn(?Model $record) => $record && ! in_array($record->state, ['pending', 'closed']))
                        ->form([
                            Forms\Components\RichEditor::make('note')
                                ->label('Optional note to include in the notification email')
                                ->columnSpanFull(),
                        ])
                        ->action(function (array $data, ?Model $record) {
                            $record->update(['state' => 'pending']);
                            $user = \App\Models\User::find($record->user_id);
                            if ($user) {
                                Mail::to($user)->send(new StatusUpdate($user, $record, 'pending', $data['note'] ?? null));
                            }
                        }),
                    Action::make('closeIssue')
                        ->label('Close Issue')
                        ->icon('heroicon-o-x-circle')
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
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->persistFiltersInSession()

            ->defaultPaginationPageOption(25)
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('state')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'investigating' => 'Investigating',
                        'implementing' => 'Implementing',
                        'pending' => 'Pending',
                        'closed' => 'Closed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query->where('state', '!=', 'closed');
                        }
                        return $query->where('state', $data['value']);
                    }),
                \Filament\Tables\Filters\SelectFilter::make('label')
                    ->options(function(){
                        $issue = new Issue();
                        return collect($issue->getLabels())->pluck('name', 'name');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereJsonContains('labels', ['name' => $data['value']]);
                    })
            ])
            ;
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
    protected function getContrastColor($hexColor)
    {
        // Remove # if present
        $hexColor = ltrim($hexColor, '#');

        // Convert to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Calculate luminance - standard formula for contrast
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // Return black or white based on luminance
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
}
