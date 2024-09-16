<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Console\Commands\Commands;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use App\Traits\GithubTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SyncIssuesWithGithub extends Command
{
    use GithubTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:sync-issues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $issues = $this->getIssues();
        if (is_array($issues) && sizeof($issues) > 0) {
            foreach ($issues as $issue) {
                $issue_res = Issue::updateOrCreate([
                    'id' => $issue['id'],
                    'number' => $issue['number'],
                ], [
                    'title' => $issue['title'],
                    'body' => $issue['body'],
                    'state' => $issue['state'],
                    'labels' => [
                        'name' => $issue['labels'][0]['name'] ?? 'bug',
                        'color' => $issue['labels'][0]['color'] ?? null,
                        'id' => $issue['labels'][0]['id'] ?? null
                    ],
                    'created_at' => $issue['created_at'],
                    'updated_at' => $issue['updated_at'],
                ]);
                if ($issue['comments'] > 0) {
                    $comments = $this->getComments($issue);
                    foreach ($comments as $comment) {
                        $comment_res = IssueComment::updateOrCreate([
                            'id' => $comment['id'],
                            'issue_id' => $issue['id']
                        ], [
                            'body' => $comment['body'],
                            'created_at' => $comment['created_at'],
                            'updated_at' => $comment['updated_at'],
                        ]);
                        if ($comment_res->user_id == null && $issue_res->user_id != null && !$comment_res->notified_author) {
                            Mail::to(User::find($issue_res->user_id))->send(new \App\Mail\Issue\Comment($issue_res, $comment_res, $issue_res->author));
                            $comment_res->update([
                                'notified_author' => true
                            ]);

                        }


                    }
                }

            }
        }
    }
}
