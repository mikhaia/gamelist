<?php

namespace App\Mail;

use App\Models\Game;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class InactivePlayerMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<int, Game> $playingGames */
    public function __construct(
        public readonly User $recipient,
        public readonly Collection $playingGames,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Как продвигаются ваши игры?');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inactive-player');
    }
}
