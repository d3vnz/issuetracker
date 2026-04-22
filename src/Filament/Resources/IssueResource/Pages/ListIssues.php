<?php
/*
 *
 *  * Copyright (c) D3V Services Limited on behalf of their client.
 *  * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 *
 */

namespace D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;

use D3vnz\IssueTracker\Filament\Actions\CreateIssueAction;
use D3vnz\IssueTracker\Filament\Resources\IssueResource;
use D3vnz\IssueTracker\Services\TicketmateClient;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListIssues extends ListRecords
{
    protected static string $resource = IssueResource::class;

    /**
     * Lifecycle filter for TicketMate-mode listings. 'open' (default),
     * 'closed', or 'all'. Reflected to the URL as ?state=… so deep-links
     * are bookmarkable and a refresh keeps the user on the same view.
     */
    public string $ticketmateFilter = 'open';

    /**
     * @return array<string,array<string,string>|string>
     */
    protected function getQueryString(): array
    {
        // Don't include the default 'open' in the URL — keeps clean
        // /admin/issues by default, switches to ?state=closed only when needed.
        return [
            'ticketmateFilter' => ['as' => 'state', 'except' => 'open'],
        ];
    }

    public function setTicketmateFilter(string $filter): void
    {
        if (! in_array($filter, ['open', 'closed', 'all'], true)) return;
        $this->ticketmateFilter = $filter;
    }

    /**
     * Counts per filter bucket — used by the v3 Blade buttons and the
     * v5 header actions to show "Open (12) / Closed (47) / All (59)".
     *
     * @return array{open:int,closed:int,all:int}
     */
    public function getTicketmateCounts(): array
    {
        $cache = app(\D3vnz\IssueTracker\Services\TicketmateIssuesCache::class);
        $all = $cache->all('all');
        $closed = $all->filter(fn (array $r) => in_array($r['status'] ?? '', ['resolved', 'closed'], true))->count();
        $total = $all->count();
        return ['open' => $total - $closed, 'closed' => $closed, 'all' => $total];
    }

    /**
     * In Filament 3 we can't feed the table a cached array (no Table::records()),
     * so we render the TicketMate-cached issues via a custom Blade view that
     * bypasses the Filament table component. Filament 5 keeps using the
     * native ->records() API on the resource's table().
     */
    public function getView(): string
    {
        if ($this->isUsingV3CustomBladeView()) {
            return 'd3vnz-issuetracker::filament.list-issues-ticketmate-v3';
        }
        return parent::getView();
    }

    /** Used by the v3 custom Blade only. Filtered by $ticketmateFilter. */
    public function getTicketmateRows(): array
    {
        return app(\D3vnz\IssueTracker\Services\TicketmateIssuesCache::class)
            ->all($this->ticketmateFilter)
            ->all();
    }

    /**
     * Wire-clicked from the v3 Blade. Asks TicketMate to mint a magic-link
     * URL bound to THIS viewer's email (not the ticket's original requester),
     * then opens it in a new tab. Means staff clicking from the consuming
     * app's IssueResource auto-log into TM as themselves.
     */
    public function openInTicketmate(int $ticketId): void
    {
        $user = auth()->user();
        $email = $user?->email;
        $name = trim((string) ($user?->first_name ?? '') . ' ' . (string) ($user?->last_name ?? '')) ?: ($user?->name ?? null);
        $url = (new TicketmateClient())->loginAs($ticketId, $email, $name);
        $this->js('window.open(' . json_encode($url) . ', "_blank")');
    }

    public function refreshTicketmate(): void
    {
        $cache = app(\D3vnz\IssueTracker\Services\TicketmateIssuesCache::class);
        $cache->bust();
        $rows = $cache->refresh();
        Notification::make()
            ->title('Refreshed from TicketMate')
            ->body(count($rows) . ' issue(s) loaded.')
            ->success()
            ->send();
    }

    /**
     * Public method exposed so the v3 custom blade's
     * wire:click="mountAction('createIssue')" can resolve the action via
     * Filament's standard `{name}Action()` lookup. Without this, v3 was
     * silently failing because the cached header-actions array wasn't
     * being consulted by mountAction calls originating outside the
     * native header button.
     */
    public function createIssueAction(): Action
    {
        return CreateIssueAction::make();
    }

    protected function getHeaderActions(): array
    {
        // v3 path: render our own button inside the custom blade view
        // (see list-issues-ticketmate-v3.blade.php toolbar) — returning
        // the action here too would produce a duplicate "New Issue"
        // button stacked above the toolbar.
        if ($this->isUsingV3CustomBladeView()) {
            return [];
        }

        // v5 path: native ListRecords header actions render the button.
        return [CreateIssueAction::make()];
    }

    /**
     * v3 mode AND TicketMate-mode means we route to the custom blade
     * view via getView(). Same predicate used in getView() — kept here
     * as a single source of truth so the two stay in lock-step.
     */
    protected function isUsingV3CustomBladeView(): bool
    {
        return TicketmateClient::isEnabled()
            && ! method_exists(Table::class, 'records');
    }
}
