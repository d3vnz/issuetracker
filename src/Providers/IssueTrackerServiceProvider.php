<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Providers;


use D3vnz\IssueTracker\Livewire\Global\IssueTab;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use D3vnz\IssueTracker\Filament\Resources\IssueResource;
use Filament\Facades\Filament;
use D3vnz\IssueTracker\Filament\Resources\IssueResource\RelationManagers\CommentsRelationManager;
use D3vnz\IssueTracker\Console\Commands\SyncIssuesWithGithub;
class IssueTrackerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncIssuesWithGithub::class,
            ]);
        }
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../database/migrations' => database_path('migrations'),
            ], 'd3vnz-issuetracker-migrations');
        }

        Livewire::component('d3vnz-issue-tab', IssueTab::class);
        Livewire::component('d3vnz.issue-tracker.filament.resources.issue-resource.relation-managers.comments-relation-manager', CommentsRelationManager::class);
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'd3vnz-issuetracker');

        Filament::registerResources([
            IssueResource::class,
        ]);
        // Register Livewire component
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            function (): string {
                $currentUrl = request()->url();
                if (str_contains($currentUrl, 'login'))
                    return '';

                // Check if the current route is not in the excluded list
                try {
                    return Blade::render('<livewire:d3vnz-issue-tab />');
                } catch (\Exception $e) {
                    // Log the error or handle it as needed

                    return ''; // Return an empty string in case of an error
                }
                // return View::make('d3vnz-issuetracker::livewire.global.issue-tab')->render();
            }
        );
        $this->registerCommands();


//        if ($this->app->runningInConsole()) {
//            $this->publishes([
//                __DIR__.'/../../resources/css' => public_path('css/vendor/d3vnz-issuetracker'),
//            ], 'd3vnz-issuetracker-assets');
//        }
    }
}
