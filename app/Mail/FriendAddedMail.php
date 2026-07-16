<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FriendAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly User $friend,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "@{$this->friend->login} хочет с вами дружить");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.friend-added');
    }
}
