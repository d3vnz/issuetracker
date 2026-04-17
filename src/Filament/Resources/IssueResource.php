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

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
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
                return Issue::query()
                    ->whereNull('deleted_at')
                    ->withCount('comments');
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
                    ->label('Title'),
                TextColumn::make('kind')
                    ->alignCenter()
                    ->placeholder('None')
                    ->state(fn (?Model $record) => $record?->kind ?? ($record?->labels['name'] ?? null))
                    ->formatStateUsing(fn ($state) => Issue::displayLabel($state))
                    ->badge()
                    ->color(fn ($state) => Issue::labelColor($state))
                    ->label('Type'),
                TextColumn::make('author.name')
                    ->label('Logged By'),
                TextColumn::make('comments_count')
                    ->label('Notes')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray')
                    ->formatStateUsing(fn ($state) => (int) $state),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (?Model $record) {
                        if (! $record) {
                            return null;
                        }
                        if ($record->state === 'closed') {
                            return 'closed';
                        }
                        $status = $record->status ?: $record->state;
                        return $status ? (config('issuetracker.statuses.prefix', 'status:') . $status) : null;
                    })
                    ->formatStateUsing(fn ($state) => Issue::displayLabel($state))
                    ->color(fn ($state) => $state === 'closed' ? 'success' : Issue::labelColor($state))
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
                    ->placeholder('Still Open')
                    ->alignEnd(),
            ])
            ->actions([
                ActionGroup::make([
                    ...self::makeStatusActions(),
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
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'received' => 'Received',
                        'investigating' => 'Investigating',
                        'implementing' => 'Implementing',
                        'pending' => 'Pending',
                        'deployed' => 'Deployed',
                        'closed' => 'Closed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            // Default view: hide closed issues from the table
                            return $query->where('state', '!=', 'closed');
                        }
                        if ($data['value'] === 'closed') {
                            return $query->where('state', 'closed');
                        }
                        return $query
                            ->where('state', '!=', 'closed')
                            ->where('status', $data['value']);
                    }),
                \Filament\Tables\Filters\SelectFilter::make('kind')
                    ->label('Type')
                    ->options(function () {
                        $issue = new Issue();
                        $prefix = (string) config('issuetracker.statuses.prefix', 'status:');
                        return collect($issue->getLabels())
                            ->reject(fn ($l) => str_starts_with($l['name'] ?? '', $prefix))
                            ->mapWithKeys(fn ($l) => [
                                $l['name'] => Issue::displayLabel($l['name']),
                            ])
                            ->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        // Prefer the indexed `kind` column; fall back to JSON for
                        // rows that haven't been resynced since the migration.
                        return $query->where(function (Builder $q) use ($data) {
                            $q->where('kind', $data['value'])
                              ->orWhere('labels->name', $data['value']);
                        });
                    }),
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

    protected static function makeStatusActions(): array
    {
        $specs = [
            'markInvestigating' => ['label' => 'Mark Investigating', 'status' => 'investigating', 'icon' => 'heroicon-o-magnifying-glass', 'color' => 'info'],
            'markImplementing'  => ['label' => 'Mark Implementing',  'status' => 'implementing',  'icon' => 'heroicon-o-wrench-screwdriver', 'color' => 'warning'],
            'markPending'       => ['label' => 'Mark Pending',       'status' => 'pending',       'icon' => 'heroicon-o-clock', 'color' => 'primary'],
            'markDeployed'      => ['label' => 'Mark Deployed',      'status' => 'deployed',      'icon' => 'heroicon-o-rocket-launch', 'color' => 'success'],
        ];

        $actions = [];
        foreach ($specs as $name => $spec) {
            $actions[] = Action::make($name)
                ->label($spec['label'])
                ->icon($spec['icon'])
                ->color($spec['color'])
                ->visible(fn(?Model $record) => $record && $record->state !== 'closed' && $record->status !== $spec['status'])
                ->form([
                    Forms\Components\RichEditor::make('note')
                        ->label('Optional note to include in the notification email')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, ?Model $record) use ($spec): void {
                    $previousStatus = $record->status;

                    try {
                        $record->setIssueStatusLabel($record->number, $spec['status'], $previousStatus);
                    } catch (\Throwable $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Could not update GitHub label')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        return;
                    }

                    $record->update([
                        'status' => $spec['status'],
                        'status_changed_at' => now(),
                    ]);

                    $user = \App\Models\User::find($record->user_id);
                    if ($user) {
                        Mail::to($user)->send(new StatusUpdate($user, $record, $spec['status'], $data['note'] ?? null));
                    }
                });
        }

        return $actions;
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
