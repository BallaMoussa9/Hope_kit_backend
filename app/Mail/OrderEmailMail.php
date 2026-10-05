<?php

namespace App\Mail;

use App\Models\SalesOrder;
use App\Services\SimplePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SalesOrder $order,
        public string $recipient
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'HOPE - Commande ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-available',
            with: [
                'title' => 'Commande HOPE ' . $this->order->order_number,
                'document' => $this->order,
                'recipient' => $this->recipient,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(SimplePdfService::class)
                    ->salesOrder($this->order),
                $this->order->order_number . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
