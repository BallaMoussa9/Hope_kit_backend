<?php

namespace App\Mail;

use App\Services\SimplePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $type,
        public array $data,
        public string $dateFrom,
        public string $dateTo,
        public string $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'HOPE - Rapport ' . $this->type);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-available',
            with: [
                'title' => 'Rapport HOPE - ' . $this->type,
                'document' => null,
                'recipient' => $this->recipient,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(SimplePdfService::class)->report(
                    $this->type,
                    $this->data,
                    now()->parse($this->dateFrom)->startOfDay(),
                    now()->parse($this->dateTo)->endOfDay(),
                ),
                'rapport-' . $this->type . '.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
