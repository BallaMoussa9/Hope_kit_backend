<?php

namespace App\Mail;

use App\Models\Payment;
use App\Services\SimplePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Le paiement concerné.
     */
    public function __construct(
        public Payment $payment
    ) {
    }

    /**
     * Construire l'e-mail.
     */
    public function build()
    {
        // Charger les relations nécessaires
        $this->payment->loadMissing([
            'document',
            'customer',
            'recorder',
        ]);

        // Générer le reçu PDF
        $pdf = app(SimplePdfService::class)
            ->paymentReceipt($this->payment);

        return $this
            ->subject(
                'HOPE - Confirmation de paiement PAI-' .
                $this->payment->id
            )

            // Vue Blade de l'e-mail
            ->view('emails.payment-confirmation')

            // Ajouter le reçu PDF
            ->attachData(
                $pdf,
                'PAI-' . $this->payment->id . '.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}
