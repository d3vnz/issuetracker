<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Mail\Issue;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Comment extends Mailable
{
    use Queueable, SerializesModels;

    public $request;
    public $comment;
    private $user;
    public $origin;
    public $to_name;

    /**
     * Create a new message instance.
     */
    public function __construct($request, $comment, $user)
    {
        //
        $this->request = $request;
        $this->comment = $comment;
        $this->user = $user;
        if ($comment->user_id == null) {
            $this->origin = 'A D3V Developer';
            $this->to_name = $this->user->name;
        } else {
            $this->origin = $this->user->name;
            $this->to_name = 'D3V Developers';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Comment for issue ' . $this->request->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.issue.comment',
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
