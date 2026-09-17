<?php

namespace App\Mail;

use App\Models\BusinessDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BusinessDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BusinessDocument $document) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: BusinessDocument::TYPES[$this->document->type]." {$this->document->number} from BRON Highworks",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.business-document',
        );
    }
}
