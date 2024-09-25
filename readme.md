# D3VNZ IssueTracker

This package provides a GitHub issue tracking integration for Laravel applications using Filament 3.

## Requirements

- PHP 8.0+
- Laravel 9.0+
- Filament 3.0+

## Installation

You can install the package via composer:
    
    composer require d3vnz/issuetracker
    php artisan vendor:publish --tag=d3vnz-issuetracker-migrations
    php artisan migrate 
    php artisan vendor:publish --provider="GrahamCampbell\GitHub\GitHubServiceProvider"

## Config
In your services.php add the following:

```php
    
        'github' => [
            'token' => env('GITHUB_TOKEN'),
            'owner' => env('GITHUB_OWNER'),
            'repo' => env('GITHUB_REPO'),
        ],
    
```
## Console
Add the following to your console
```php
Schedule::command('github:sync-issues')->everyThirtyMinutes();
```