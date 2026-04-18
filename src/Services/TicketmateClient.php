<?php

namespace D3vnz\IssueTracker\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Reads issue data from a remote TicketMate instance.
 *
 * The repository identity is implicit in the API token — TicketMate scopes
 * each token to a single repository row.
 */
class TicketmateClient
{
    public static function isEnabled(): bool
    {
        return (bool) config('issuetracker.ticketmate.enabled');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listIssues(?string $since = null, ?bool $includeClosed = null, int $limit = 50): array
    {
        if (! self::isEnabled()) return [];

        $resp = $this->request()->get('issues', array_filter([
            'since' => $since,
            'include_closed' => $includeClosed === null ? null : ($includeClosed ? 'true' : 'false'),
            'limit' => $limit,
        ]));

        if (! $resp->ok()) return [];
        return (array) $resp->json('data', []);
    }

    public function getIssue(int $githubIssueNumber): ?array
    {
        if (! self::isEnabled()) return null;

        $resp = $this->request()->get("issues/{$githubIssueNumber}");
        if (! $resp->ok()) return null;
        return (array) $resp->json();
    }

    /**
     * Tell TicketMate who actually filed the issue (the logged-in user in the
     * consuming app, not the GitHub bot account that posted via API).
     * TicketMate will create / link a Contact and use this for confirmation emails.
     *
     * Safe to call before the GitHub webhook has hit TicketMate — TM will
     * upsert a placeholder ticket and reconcile when the webhook arrives.
     */
    public function attachCreator(int $githubIssueNumber, ?string $creatorEmail, ?string $creatorName, ?string $creatorAppUrl = null): bool
    {
        if (! self::isEnabled()) return false;

        try {
            $resp = $this->request()->post('issues/attach-creator', [
                'github_issue_number' => $githubIssueNumber,
                'creator_email' => $creatorEmail,
                'creator_name' => $creatorName,
                'creator_app_url' => $creatorAppUrl,
            ]);
            return $resp->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(config('issuetracker.ticketmate.url') . '/api/v1/')
            ->withToken((string) config('issuetracker.ticketmate.token'))
            ->acceptJson()
            ->timeout((int) config('issuetracker.ticketmate.http_timeout', 10));
    }
}
