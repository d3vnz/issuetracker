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

class Notification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $issue;
    private $result;
    public $url;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $issue, $result)
    {
        //
        $this->user = $user;
        $this->issue = $issue;
        $this->url = $result['html_url'];

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Request Logged by ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'd3vnz-issuetracker::mail.issue.notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
