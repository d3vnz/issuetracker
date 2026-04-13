<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 */

namespace D3vnz\IssueTracker\Jobs;

use D3vnz\IssueTracker\Mail\Issue\Confirmation;
use D3vnz\IssueTracker\Mail\Issue\RequestMoreInfo;
use D3vnz\IssueTracker\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AnalyzeIssueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public Issue $issue)
    {
        $this->afterCommit = true;
        $this->onQueue(config('issuetracker.ai.queue', 'default'));
    }

    public function handle(): void
    {
        $issue = $this->issue->fresh();
        if (! $issue) {
            return;
        }

        $creator = $issue->author;
        if (! $creator || ! $creator->email) {
            return;
        }

        if (! config('issuetracker.ai.enabled') || ! env('OPENAI_API_KEY')) {
            Mail::to($creator)->send(new Confirmation($creator, $issue));
            return;
        }

        $plainBody = trim(strip_tags((string) $issue->body));
        $imageUrls = $this->extractImageUrls((string) $issue->body);

        $candidates = Issue::query()
            ->where('id', '!=', $issue->id)
            ->where('state', '!=', 'closed')
            ->orderByDesc('updated_at')
            ->limit((int) config('issuetracker.ai.candidate_limit', 25))
            ->get(['id', 'number', 'title', 'body']);

        $verdict = $this->analyze($issue, $plainBody, $imageUrls, $candidates);

        $needsMore = (bool) ($verdict['needs_more_info'] ?? false);
        $duplicateOf = $verdict['duplicate_of'] ?? null;
        $reason = trim((string) ($verdict['reason'] ?? ''));

        if ($verdict && ($needsMore || $duplicateOf)) {
            $duplicate = null;
            if ($duplicateOf) {
                $duplicate = $candidates->firstWhere('number', (int) $duplicateOf)
                    ?? $candidates->firstWhere('id', (int) $duplicateOf);
            }

            Mail::to($creator)->send(new RequestMoreInfo($creator, $issue, $reason, $duplicate));
            return;
        }

        Mail::to($creator)->send(new Confirmation($creator, $issue));
    }

    protected function extractImageUrls(string $html): array
    {
        if ($html === '') {
            return [];
        }
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m);
        return array_slice(array_filter($m[1] ?? [], fn ($u) => str_starts_with($u, 'http')), 0, 3);
    }

    protected function analyze(Issue $issue, string $plainBody, array $imageUrls, $candidates): ?array
    {
        $minChars = (int) config('issuetracker.ai.min_body_chars', 40);
        $hasImage = ! empty($imageUrls);
        $bodyThin = mb_strlen($plainBody) < $minChars;

        if (! $bodyThin && $candidates->isEmpty()) {
            return null;
        }

        $candidateList = $candidates->map(fn ($c) => sprintf(
            "#%s | %s | %s",
            $c->number ?? $c->id,
            $c->title,
            mb_substr(trim(strip_tags((string) $c->body)), 0, 160)
        ))->implode("\n");

        $system = 'You are a triage assistant for a software issue tracker. '
            . 'Decide if a newly-created issue needs more information from the reporter, '
            . 'and whether it likely duplicates an existing open issue. '
            . 'If an image is provided, use its contents to judge whether the description is sufficient. '
            . 'The "reason" field MUST be written as a direct message to the reporter explaining '
            . 'what is unclear and what would help (e.g. "I need more information to understand '
            . 'what action triggered this — could you describe the steps you took before the error?"). '
            . 'Do not refer to yourself in the third person and do not mention JSON. '
            . 'Respond ONLY as compact JSON: '
            . '{"needs_more_info":bool,"duplicate_of":null|number,"reason":"direct message to the reporter"}';

        $userText = "NEW ISSUE\nTitle: {$issue->title}\nBody: {$plainBody}\n\n"
            . "OPEN ISSUES (number | title | snippet):\n" . ($candidateList ?: '(none)');

        $content = [['type' => 'text', 'text' => $userText]];
        foreach ($imageUrls as $url) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('issuetracker.ai.model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $content],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('IssueTracker AI call failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $json = $response->json('choices.0.message.content');
            if (! $json) {
                return null;
            }

            $parsed = json_decode($json, true);
            return is_array($parsed) ? $parsed : null;
        } catch (\Throwable $e) {
            Log::warning('IssueTracker AI exception: ' . $e->getMessage());
            return null;
        }
    }
}
