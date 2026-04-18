# D3VNZ IssueTracker

Filament-based issue tracker that creates GitHub issues from your app, mirrors them locally, and can optionally surface enriched data from a central [TicketMate](https://github.com/d3vnz/ticketmate) instance.

## Requirements

- PHP 8.3+
- Laravel 11 / 12 / 13
- Filament 3 / 4 / 5
- (Optional) TicketMate instance at `https://helpdesk.d3v.nz`

## Installation

```bash
composer require d3vnz/issuetracker
php artisan vendor:publish --tag=d3vnz-issuetracker-migrations
php artisan migrate
php artisan vendor:publish --provider="GrahamCampbell\GitHub\GitHubServiceProvider"
```

## .env

```env
GITHUB_TOKEN=ghp_yourPersonalAccessToken
GITHUB_OWNER=d3vnz
GITHUB_REPO=mostech-v2
```

In `config/services.php` add:
```php
'github' => [
    'token' => env('GITHUB_TOKEN'),
    'owner' => env('GITHUB_OWNER'),
    'repo'  => env('GITHUB_REPO'),
],
```

## Console schedule

```php
Schedule::command('github:sync-issues')->everyThirtyMinutes();
```

---

## TicketMate integration (recommended)

Instead of standalone polling + per-app email plumbing, point the package at a central TicketMate. TicketMate watches the GitHub repo via webhook, runs AI triage, captures URL screenshots, surfaces issues alongside support tickets and uptime alerts in one inbox.

### One-time setup in TicketMate

1. Open `https://helpdesk.d3v.nz/admin/repositories` and create a record for the repo (e.g. `d3vnz/mostech-v2`). Set the brand and link a client/domain.
2. Copy the **Webhook URL** and **Webhook secret**, add them to GitHub → Settings → Webhooks (events: *Issues* + *Issue comments*, content-type `application/json`).
3. Copy the **API token** shown on the same page.

### .env additions for the consuming app

```env
TICKETMATE_API_URL=https://helpdesk.d3v.nz
TICKETMATE_API_TOKEN=the-token-from-the-repo-page
TICKETMATE_USE_REMOTE_LISTINGS=true
```

When these are set:

- New issues created via `IssueQuickAction` still go to GitHub. The package then immediately POSTs the *real* logged-in user's name + email to TicketMate so the ticket has a proper requester (instead of just the GitHub bot account).
- TicketMate handles the **confirmation email** to that creator (using its branded templates). The package's own `Confirmation` / `Notification` mails are skipped.
- The local `issues` table becomes a cache. Replace the GitHub poller with TicketMate's:

```php
// console.php
// REMOVE: Schedule::command('github:sync-issues')->everyThirtyMinutes();
Schedule::command('ticketmate:sync')->everyTenMinutes();
```

The `ticketmate:sync` command pulls workflow state, status, and closure timestamps from TicketMate, so the existing `IssueResource` UI keeps working unchanged but the source of truth is now TicketMate (which itself watches GitHub via webhook).

When `TICKETMATE_*` env vars are NOT set, the package keeps its full original behaviour (GitHub poll + local emails).

## Configuration reference

See [`config/issuetracker.php`](config/issuetracker.php) for full options.
