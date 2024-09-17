<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class IssueTrackerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'd3vnz-issuetracker');

//        FilamentAsset::register([
//            Css::make('issue-css', __DIR__ . '/../../resources/css/issue-tab.css'),
//
//
//        ]);
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => Blade::render('@livewire(\'d3vnz-issuetracker::global.issue-tab\')')
        );

//        if ($this->app->runningInConsole()) {
//            $this->publishes([
//                __DIR__.'/../../resources/css' => public_path('css/vendor/d3vnz-issuetracker'),
//            ], 'd3vnz-issuetracker-assets');
//        }
    }
}
