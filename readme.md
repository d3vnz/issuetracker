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

When these are set, the package runs in **fully centralised mode**:

- The consuming app does **NOT** need a `GITHUB_TOKEN` — TicketMate creates the GitHub issue with its own token and returns the result.
- The package writes **no issue data to your local database**. Issues live in TicketMate (and GitHub). Locally they're cached for 10 minutes in your default `Cache` store (Redis if configured).
- The Filament `IssueResource` table is rendered from that cache — first hit refreshes, subsequent hits within 10 minutes serve the snapshot.
- The package sends **no emails**. TicketMate handles the branded confirmation to the issue creator (and any subsequent status / comment notifications).
- Optional cron safety net (the lazy refresh on-render is usually sufficient):

```php
// console.php
Schedule::command('ticketmate:sync --quiet-on-empty')->everyTenMinutes();
```

Migrations from earlier versions (`issues` + `issue_comments` tables) are no longer needed when TicketMate is enabled. You can drop them safely:

```bash
php artisan tinker
>>> Schema::dropIfExists('issue_comments');
>>> Schema::dropIfExists('issues');
```

When `TICKETMATE_*` env vars are NOT set, the package keeps its original behaviour (GitHub poll + local DB + local emails).

## Configuration reference

See [`config/issuetracker.php`](config/issuetracker.php) for full options.
