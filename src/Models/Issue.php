<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Models;

use App\Traits\GithubTrait;
use Filament\Forms\Components\Grid;
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getForm($issueType = null): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->label('Issue / Request Title')
                    ->required(),
                Select::make('labels.name')
                    ->formatStateUsing(function (?Model $record) use ($issueType) {
                        if ($issueType) {
                            return $issueType;
                        }
                        return $record->labels['id'] ?? null;
                    })
                    ->label('Issue Type')
                    ->options(function () {
                        $issue = new Issue();
                        return collect($issue->getLabels())->pluck('name', 'name');

                    })
            ]),
            RichEditor::make('body')
                ->label('Issue / Request Description')
                ->required()
                ->columnSpanFull()
        ];
    }


}
