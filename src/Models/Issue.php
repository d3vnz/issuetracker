<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Models;

use D3vnz\IssueTracker\Traits\GithubTrait;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issue extends Model
{
    use SoftDeletes;
    use GithubTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'closed_at' => 'timestamp',
        ];
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public static function getForm($issueType = null): array
    {
        return [
            Grid::make(12)->schema([
                TextInput::make('title')
                    ->columnSpan(6)
                    ->label('Issue / Request Title')
                    ->required(),
                Select::make('labels.name')
                    ->columnSpan(3)
                    ->formatStateUsing(function (?Model $record) use ($issueType) {
                        if ($issueType) {
                            return $issueType;
                        }
                        return $record->labels['name'] ?? null;
                    })
                    ->label('Issue Type')
                    ->options(function () {
                        $issue = new Issue();
                        return collect($issue->getLabels())->pluck('name', 'name');

                    }),
                Placeholder::make('created_at')->label('Issue Created')
                    ->columnSpan(3)
                    ->visible(fn(?Model $record) => isset($record->created_at))
                    ->content(fn(?Model $record): string => isset($record->created_at) ? $record->created_at->format('D dS M Y H:i') : null),
            ]),
            RichEditor::make('body')
                ->label('Issue / Request Description')
                ->required()
                ->columnSpanFull()
        ];
    }


}
