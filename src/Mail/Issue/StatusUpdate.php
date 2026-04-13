<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Mail\Issue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $afterCommit = true;

    public $user;
    public $issue;
    public $status;
    public $note;

    public function __construct($user, $issue, string $status, ?string $note = null)
    {
        $this->user = $user;
        $this->issue = $issue;
        $this->status = $status;
        $this->note = $note;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your issue: ' . $this->issue->title . ' (' . ucfirst($this->status) . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'd3vnz-issuetracker::mail.issue.status',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
