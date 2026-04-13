<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 */

namespace D3vnz\IssueTracker\Mail\Issue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestMoreInfo extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public $issue,
        public ?string $reason = null,
        public $duplicate = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'More information needed for: ' . $this->issue->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'd3vnz-issuetracker::mail.issue.request-more-info',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
