<?php
namespace App\Mail; use App\Models\CommercialDocument; use Illuminate\Bus\Queueable; use Illuminate\Mail\Mailable; use Illuminate\Queue\SerializesModels;
class InvoiceAvailableMail extends Mailable {use Queueable,SerializesModels; public function __construct(public CommercialDocument $document, public string $url){} public function build(){return $this->subject('Votre facture HOPE — '.$this->document->document_number)->view('emails.invoice-available');}}
