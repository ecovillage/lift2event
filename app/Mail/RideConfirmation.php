<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Ride;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RideConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $editUrl;
    public readonly string $deleteUrl;

    public function __construct(
        public readonly Ride $ride,
        public readonly Event $event,
    ) {
        $base = rtrim(config('app.url'), '/');
        $slug = $event->slug;
        $id   = $ride->id;
        $tok  = $ride->edit_token;

        $this->editUrl   = "{$base}/e/{$slug}/ride/{$id}/edit?token={$tok}";
        $this->deleteUrl = "{$base}/e/{$slug}/ride/{$id}/delete?token={$tok}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Deine Mitfahrt bei ' . $this->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ride_confirmation',
        );
    }
}
