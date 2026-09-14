<?php
namespace App\Mail; use App\Models\Payment; use Illuminate\Bus\Queueable; use Illuminate\Mail\Mailable; use Illuminate\Queue\SerializesModels;
class PaymentConfirmationMail extends Mailable {use Queueable,SerializesModels; public function __construct(public Payment $payment){} public function build(){return $this->subject('Confirmation de votre paiement — HOPE')->view('emails.payment-confirmation');}}
