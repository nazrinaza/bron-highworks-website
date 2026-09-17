<?php

namespace App\Mail;

use App\Models\SiteVisit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAssessmentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SiteVisit $visit) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New site assessment: {$this->visit->name}",
            replyTo: [$this->visit->email],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-assessment-notification',
        );
    }
}
