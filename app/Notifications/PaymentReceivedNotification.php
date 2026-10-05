<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $payment
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmation de paiement - HOPE Health and Care')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous confirmons la réception de votre paiement.')
            ->line('Référence : ' . $this->payment->reference)
            ->line('Montant : ' . number_format($this->payment->amount, 0, ',', ' ') . ' FCFA')
            ->line('Merci pour votre confiance.')
            ->salutation('HOPE Health and Care');
    }
}
