<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */


use App\Filament\Resources\IssueResource\Pages;
use App\Filament\Resources\IssueResource\RelationManagers\CommentsRelationManager;
use App\Models\Issue;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
            ->columns([
                TextColumn::make('title')
                    ->label('Issue Title'),
                TextColumn::make('author.name')
                    ->label('Logged By'),
                TextColumn::make('labels.name')
                    ->color(function ($state) {

                        switch ($state) {
                            case 'bug':
                                return 'danger';
                            case 'enhancement':
                                return 'warning';
                            case 'feature request':
                                return 'success';
                        }


                    })
                    ->badge()
                    ->label('Issue Type'),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->alignRight()
            ])
            ->filters([
                //  TrashedFilter::make(),
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
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
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
