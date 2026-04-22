<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Models;

use D3vnz\IssueTracker\Traits\GithubTrait;
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
            'status_changed_at' => 'datetime',
        ];
    }

    public function currentStatus(): ?string
    {
        return $this->status ?: null;
    }

    public function recentlyTransitioned(?int $withinSeconds = null): bool
    {
        $withinSeconds ??= (int) config('issuetracker.statuses.transition_dedupe_seconds', 600);
        if ($withinSeconds <= 0 || ! $this->status_changed_at) {
            return false;
        }
        return $this->status_changed_at->gt(now()->subSeconds($withinSeconds));
    }

    public static function extractStatusFromLabels(array $rawLabels): ?string
    {
        $prefix = (string) config('issuetracker.statuses.prefix', 'status:');
        foreach ($rawLabels as $label) {
            $name = is_array($label) ? ($label['name'] ?? null) : null;
            if ($name && str_starts_with($name, $prefix)) {
                return substr($name, strlen($prefix));
            }
        }
        return null;
    }

    public static function displayLabel(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }
        $prefix = (string) config('issuetracker.statuses.prefix', 'status:');
        if (str_starts_with($name, $prefix)) {
            return ucwords(substr($name, strlen($prefix)));
        }
        return ucwords($name);
    }

    public static function labelColor(?string $name): string
    {
        if ($name === null || $name === '') {
            return 'gray';
        }
        $prefix = (string) config('issuetracker.statuses.prefix', 'status:');
        if (str_starts_with($name, $prefix)) {
            return match (substr($name, strlen($prefix))) {
                'received' => 'gray',
                'investigating' => 'info',
                'implementing' => 'warning',
                'pending' => 'primary',
                'deployed' => 'success',
                default => 'gray',
            };
        }
        return match ($name) {
            'bug' => 'danger',
            'feature' => 'success',
            'change' => 'warning',
            default => 'primary',
        };
    }

    public static function firstKindLabel(array $rawLabels): ?array
    {
        $prefix = (string) config('issuetracker.statuses.prefix', 'status:');
        foreach ($rawLabels as $label) {
            $name = is_array($label) ? ($label['name'] ?? null) : null;
            if (! $name || str_starts_with($name, $prefix)) {
                continue;
            }
            return [
                'name' => $name,
                'color' => $label['color'] ?? null,
                'id' => $label['id'] ?? null,
            ];
        }
        return null;
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
                    ->columnSpan(function(?Model $record){
                        return isset($record->id) ? 6 : 9;
                    })
                    ->label('Issue / Request Title')
                    ->required(),
                Select::make('labels.name')
                    ->columnSpan(3)
                    ->required()
                    ->default('bug')
                    ->formatStateUsing(function (?Model $record) use ($issueType) {
                        if ($issueType) {
                            return $issueType;
                        }
                        return $record->labels['name'] ?? 'bug';
                    })
                    ->label('Issue Type')
                    ->options(function () {
                        // In TicketMate-mode the consuming app has no GitHub
                        // token / no repo of its own — TicketMate owns the
                        // GitHub side. Fall back to the canonical kind list
                        // (matches Ticket::KINDS on the TM side) so the
                        // Select doesn't blow up trying to call GitHub.
                        if (\D3vnz\IssueTracker\Services\TicketmateClient::isEnabled()) {
                            return [
                                'bug' => 'Bug',
                                'feature' => 'Feature',
                                'change' => 'Change',
                                'question' => 'Question',
                            ];
                        }

                        $issue = new Issue();
                        $prefix = (string) config('issuetracker.statuses.prefix', 'status:');
                        return collect($issue->getLabels())
                            ->reject(fn ($l) => str_starts_with($l['name'] ?? '', $prefix))
                            ->mapWithKeys(fn ($l) => [$l['name'] => self::displayLabel($l['name'])])
                            ->all();
                    }),
                TextInput::make('created_at_display')
                    ->label('Issue Created')
                    ->columnSpan(3)
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn(?Model $record) => isset($record->created_at))
                    ->formatStateUsing(fn(?Model $record) => $record?->created_at?->format('D dS M Y H:i')),
            ]),
            RichEditor::make('body')
                ->label('Issue / Request Description')
                ->required()
                ->extraInputAttributes([
                    'style' => 'height: 200px;'
                ])
                ->columnSpanFull()
        ];
    }


}
