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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\ListIssues;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\CreateIssue;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\EditIssue;

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
            ])
            ->actions([
//                EditAction::make(),
//                DeleteAction::make(),
//                RestoreAction::make(),
//                ForceDeleteAction::make(),
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
