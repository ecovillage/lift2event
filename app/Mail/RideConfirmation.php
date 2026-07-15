<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Ride;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RideConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $confirmUrl;
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

        $this->confirmUrl = "{$base}/e/{$slug}/ride/{$id}/confirm?token={$tok}";
        $this->editUrl    = "{$base}/e/{$slug}/ride/{$id}/edit?token={$tok}";
        $this->deleteUrl  = "{$base}/e/{$slug}/ride/{$id}/delete?token={$tok}";
    }

    public function envelope(): Envelope
    {
        $orgName = Setting::instance()->organisation_name ?? 'Lift2Event';
        $subjectKey = $this->ride->type === 'offer' ? 'mail.subject_offer' : 'mail.subject_request';

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(config('mail.from.address'), $orgName),
            subject: __($subjectKey, ['event' => $this->event->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ride_confirmation',
        );
    }
}
