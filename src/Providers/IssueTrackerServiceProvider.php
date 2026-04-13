<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Providers;


use D3vnz\IssueTracker\Console\Commands\SyncIssuesWithGithub;
use D3vnz\IssueTracker\Jobs\AnalyzeIssueJob;
use D3vnz\IssueTracker\Models\Issue;
use D3vnz\IssueTracker\Filament\Resources\IssueResource;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\RelationManagers\CommentsRelationManager;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class IssueTrackerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/issuetracker.php', 'issuetracker');

        if (! class_exists(\Filament\Forms\Form::class, false)
            && ! interface_exists(\Filament\Forms\Form::class, false)
            && class_exists(\Filament\Schemas\Schema::class)) {
            class_alias(\Filament\Schemas\Schema::class, \Filament\Forms\Form::class);
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncIssuesWithGithub::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (config('app.env') !== 'production' && ! env('ENABLE_ISSUE_TRACKER', true)) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'd3vnz-issuetracker-migrations');

            $this->publishes([
                __DIR__ . '/../../config/issuetracker.php' => config_path('issuetracker.php'),
            ], 'd3vnz-issuetracker-config');
        }

        if (config('issuetracker.ai.enabled')) {
            Issue::created(function (Issue $issue): void {
                AnalyzeIssueJob::dispatch($issue);
            });
        }

        Livewire::component('d3vnz-issue-quick-action', \D3vnz\IssueTracker\Livewire\Global\IssueQuickAction::class);
        Livewire::component('d3vnz.issue-tracker.filament.resources.issue-resource.relation-managers.comments-relation-manager', CommentsRelationManager::class);
        Livewire::component('d3vnz-issue-tracker.filament.resources.issue-resource.pages.list-issues', \D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\ListIssues::class);
        Livewire::component('d3vnz-issue-tracker.filament.resources.issue-resource.pages.edit-issue', \D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\EditIssue::class);
        Livewire::component('d3vnz-issue-tracker.filament.resources.issue-resource.pages.create-issue', \D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages\CreateIssue::class);

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'd3vnz-issuetracker');

        Filament::registerResources([
            IssueResource::class,
        ]);

        Filament::serving(function (): void {
            $user = auth()->user();
            if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
                return;
            }

            foreach (Filament::getPanels() as $panel) {
                $panel->userMenuItems([
                    'd3vnz-issue-report-bug' => MenuItem::make()
                        ->label('Report a Bug')
                        ->icon('heroicon-o-bug-ant')
                        ->url('#d3vnz-create-' . rawurlencode('bug')),
                    'd3vnz-issue-request-change' => MenuItem::make()
                        ->label('Request a Change')
                        ->icon('heroicon-o-pencil-square')
                        ->url('#d3vnz-create-' . rawurlencode('enhancement')),
                    'd3vnz-issue-request-feature' => MenuItem::make()
                        ->label('Request a Feature')
                        ->icon('heroicon-o-sparkles')
                        ->url('#d3vnz-create-' . rawurlencode('feature request')),
                ]);
            }
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            function (): string {
                $user = auth()->user();
                if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
                    return '';
                }
                try {
                    return Blade::render('<livewire:d3vnz-issue-quick-action />');
                } catch (\Exception $e) {
                    return '';
                }
            }
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): string => Blade::render(<<<'BLADE'
<style>
    .fi-sidebar-item a[href*="/issues"] .fi-sidebar-item-icon,
    .fi-sidebar-item a[href*="/issues"] svg {
        color: #dc2626 !important;
    }
</style>
BLADE)
        );

        $this->registerCommands();
    }
}
