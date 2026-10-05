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

    public function __construct(
        public Payment $payment
    ) {}

    public function build()
    {
        $this->payment->loadMissing([
            'document',
            'customer',
            'recorder',
        ]);

        $pdf = app(SimplePdfService::class)
            ->paymentReceipt($this->payment);

        return $this
            ->subject(
                'HOPE - Confirmation de paiement PAI-' .
                $this->payment->id
            )
            ->view('emails.payment-confirmation')
            ->attachData(
                $pdf,
                'PAI-' . $this->payment->id . '.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}
