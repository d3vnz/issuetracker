<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Providers;


use D3vnz\IssueTracker\Console\Commands\SyncIssuesWithGithub;
use D3vnz\IssueTracker\Filament\Resources\IssueResource;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\RelationManagers\CommentsRelationManager;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class IssueTrackerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
        }

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
                        ->url(fn () => IssueResource::getUrl('create', ['type' => 'bug'])),
                    'd3vnz-issue-request-change' => MenuItem::make()
                        ->label('Request a Change')
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn () => IssueResource::getUrl('create', ['type' => 'enhancement'])),
                    'd3vnz-issue-request-feature' => MenuItem::make()
                        ->label('Request a Feature')
                        ->icon('heroicon-o-sparkles')
                        ->url(fn () => IssueResource::getUrl('create', ['type' => 'feature request'])),
                ]);
            }
        });

        $this->registerCommands();
    }
}
