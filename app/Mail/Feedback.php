<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Feedback extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $feedbackMessage,
        public readonly ?string $name,
        public readonly ?string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: Setting::organisationName() . ' – Feedback',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.feedback',
        );
    }
}
