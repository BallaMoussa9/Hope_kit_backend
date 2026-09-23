<?php

namespace App\Mail;

use App\Models\DeliveryNote;
use App\Services\SimplePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryNoteEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DeliveryNote $delivery) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'HOPE - Bon de livraison ' . $this->delivery->delivery_number);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-available',
            with: [
                'title' => 'Votre bon de livraison HOPE ' . $this->delivery->delivery_number,
                'document' => $this->delivery,
                'recipient' => $this->delivery->order?->customer?->email,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                function () {
                    $this->delivery->loadMissing(['order.customer', 'lines.product']);
                    $lines = [
                        'Bon de livraison : ' . $this->delivery->delivery_number,
                        'Commande : ' . ($this->delivery->order->order_number ?? '—'),
                        'Client : ' . ($this->delivery->order->customer->name ?? '—'),
                        'Date : ' . $this->delivery->delivery_date,
                        '',
                    ];
                    foreach ($this->delivery->lines as $line) {
                        $lines[] = ($line->product->name ?? 'Produit') . ' | Quantité : ' . $line->quantity;
                    }
                    $lines[] = '';
                    $lines[] = 'Statut : ' . ($this->delivery->status ?? '—');
                    return app(SimplePdfService::class)->make('BON DE LIVRAISON', $lines);
                },
                $this->delivery->delivery_number . '.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
