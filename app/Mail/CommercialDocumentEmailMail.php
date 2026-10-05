<?php

namespace App\Mail;

use App\Models\CommercialDocument;
use App\Services\SimplePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommercialDocumentEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public CommercialDocument $document,
        public string $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'HOPE - ' . $this->document->document_number);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-available',
            with: [
                'title' => 'HOPE ' . $this->document->document_number,
                'document' => $this->document,
                'recipient' => $this->recipient,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(SimplePdfService::class)->commercialDocument($this->document),
                $this->document->document_number . '.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
