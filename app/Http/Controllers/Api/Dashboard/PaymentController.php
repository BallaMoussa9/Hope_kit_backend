<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\PaymentConfirmationMail;
use App\Models\AuditLog;
use App\Models\CommercialDocument;
use App\Models\DocumentEmailLog;
use App\Models\Payment;
use App\Notifications\PaymentRecordedNotification;
use App\Services\SimplePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Liste des paiements.
     */
    public function index(Request $r)
    {
        $q = Payment::with([
            'document.order',
            'document.source',
            'customer',
            'recorder',
        ])->latest();

        if ($r->status) {
            $q->where('status', $r->status);
        }

        if ($r->search) {
            $search = $r->search;

            $q->where(function ($x) use ($search) {
                $x->where('reference', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        );
                    });
            });
        }

        return response()->json(
            $q->paginate(30)
        );
    }

    /**
     * Enregistrer un paiement.
     *
     * Workflow :
     *
     * 1. Validation
     * 2. Transaction DB
     * 3. Création du paiement
     * 4. Recalcul facture
     * 5. Audit
     * 6. Transaction terminée
     * 7. Notification interne agent
     * 8. Email client placé dans Redis
     */
    public function store(
        Request $r,
        CommercialDocument $document
    ) {
        if ($document->type !== 'invoice') {
            abort(
                422,
                'Un paiement ne peut être associé qu’à une facture.'
            );
        }

        if (!in_array(
            $document->status,
            ['accepted', 'partially_paid'],
            true
        )) {
            abort(
                422,
                'La facture doit être acceptée avant d’enregistrer un paiement.'
            );
        }

        $v = $r->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'nullable|date',
            'method' => 'required|string|max:50',
            'reference' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION DATABASE
        |--------------------------------------------------------------------------
        */

        $payment = DB::transaction(function () use (
            $document,
            $v,
            $r
        ) {
            $doc = CommercialDocument::lockForUpdate()
                ->findOrFail($document->id);

            $amount = (float) $v['amount'];

            $balance = (float) $doc->balance_amount;

            if ($amount > $balance + 0.01) {
                abort(
                    422,
                    'Le paiement dépasse le solde restant.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Création du paiement
            |--------------------------------------------------------------------------
            */

            $payment = Payment::create([
                'commercial_document_id' => $doc->id,
                'customer_id' => $doc->customer_id,
                'recorded_by' => $r->user()->id,
                'amount' => $amount,
                'paid_at' => $v['paid_at'] ?? now(),
                'method' => $v['method'],
                'status' => 'recorded',
                'reference' => $v['reference'] ?? null,
                'notes' => $v['notes'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Recalcul de la facture
            |--------------------------------------------------------------------------
            */

            $this->recalculateDocument($doc);

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            AuditLog::create([
                'user_id' => $r->user()->id,
                'action' => 'payment.created',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,

                'new_values' => [
                    'amount' => $amount,
                    'document' => $doc->document_number,
                    'balance' => $doc->balance_amount,
                ],

                'ip_address' => $r->ip(),
                'user_agent' => $r->userAgent(),
            ]);

            return $payment->load([
                'document',
                'customer',
                'recorder',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | La transaction DB est maintenant terminée.
        |
        | On ne contacte donc PAS Gmail à l'intérieur de DB::transaction().
        |
        */

        /*
        |--------------------------------------------------------------------------
        | 1. Notification interne à l'agent
        |--------------------------------------------------------------------------
        */

        try {
            $r->user()->notify(
                new PaymentRecordedNotification(
                    $payment
                )
            );
        } catch (\Throwable $e) {
            /*
            | Une erreur de notification interne ne doit pas
            | faire échouer l'enregistrement du paiement.
            */

            report($e);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Email automatique au client
        |--------------------------------------------------------------------------
        */

        $customerEmail = $payment->customer?->email;

        if (
            $customerEmail &&
            config('mail.default') !== 'log'
        ) {
            try {
                Mail::to($customerEmail)->queue(
                    new PaymentConfirmationMail(
                        $payment
                    )
                );
            } catch (\Throwable $e) {
                /*
                | L'email est secondaire par rapport au paiement.
                |
                | Le paiement reste enregistré même si la mise
                | en queue rencontre un problème.
                */

                report($e);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Réponse API
        |--------------------------------------------------------------------------
        */

        return response()->json(
            [
                'message' => 'Paiement enregistré avec succès.',

                'email_queued' => (bool) $customerEmail,

                'data' => $payment->load([
                    'document.order',
                    'document.source',
                    'customer',
                    'recorder',
                ]),
            ],
            201
        );
    }

    /**
     * Modifier le statut d'un paiement.
     */
    public function setStatus(
        Request $r,
        Payment $payment
    ) {
        $status = $r->validate([
            'status' => 'required|in:recorded,validated,cancelled',
        ])['status'];

        /*
        |--------------------------------------------------------------------------
        | Un paiement annulé ne peut pas être réactivé
        |--------------------------------------------------------------------------
        */

        if (
            $payment->status === 'cancelled' &&
            $status !== 'cancelled'
        ) {
            abort(
                422,
                'Un paiement annulé ne peut pas être réactivé.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Annulation
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'cancelled' &&
            $payment->status !== 'cancelled'
        ) {
            return DB::transaction(
                function () use ($payment, $r) {

                    $payment->load('document');

                    $payment->update([
                        'status' => 'cancelled',
                    ]);

                    $this->recalculateDocument(
                        $payment->document
                    );

                    AuditLog::create([
                        'user_id' => $r->user()->id,
                        'action' => 'payment.cancelled',
                        'auditable_type' => Payment::class,
                        'auditable_id' => $payment->id,

                        'new_values' => [
                            'document' =>
                                $payment->document->document_number,
                        ],

                        'ip_address' => $r->ip(),
                        'user_agent' => $r->userAgent(),
                    ]);

                    return response()->json(
                        $payment->fresh([
                            'document.order',
                            'document.source',
                            'customer',
                            'recorder',
                        ])
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Autres changements de statut
        |--------------------------------------------------------------------------
        */

        $payment->update([
            'status' => $status,
        ]);

        return response()->json(
            $payment->fresh([
                'document.order',
                'document.source',
                'customer',
                'recorder',
            ])
        );
    }

    /**
     * Recalculer les montants de la facture.
     */
    private function recalculateDocument(
        CommercialDocument $doc
    ): void {
        $paid = (float) Payment::where(
            'commercial_document_id',
            $doc->id
        )
            ->where(
                'status',
                '!=',
                'cancelled'
            )
            ->sum('amount');

        $balance = max(
            0,
            (float) $doc->total_amount - $paid
        );

        $status =
            $balance <= 0.01
                ? 'closed'
                : (
                    $paid > 0
                        ? 'partially_paid'
                        : (
                            $doc->status === 'cancelled'
                                ? 'cancelled'
                                : 'accepted'
                        )
                );

        $doc->update([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'status' => $status,
        ]);
    }

    /**
     * Télécharger / afficher le reçu PDF.
     */
    public function pdf(
        Payment $payment,
        SimplePdfService $pdf
    ) {
        $payment->load([
            'document.customer',
            'customer',
            'recorder',
        ]);

        return response(
            $pdf->paymentReceipt($payment),
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' =>
                    'inline; filename="PAI-' .
                    $payment->id .
                    '.pdf"',

                'Cache-Control' => 'no-store',
            ]
        );
    }

    /**
     * Envoyer manuellement le reçu par email.
     *
     * IMPORTANT :
     * L'envoi passe également par Redis.
     * Il ne bloque donc pas la requête HTTP.
     */
    public function email(
        Request $r,
        Payment $payment,
        SimplePdfService $pdf
    ) {
        $to = $r->validate([
            'email' => 'required|email',
        ])['email'];

        /*
        |--------------------------------------------------------------------------
        | Vérification SMTP
        |--------------------------------------------------------------------------
        */

        if (
            config('mail.default') === 'log'
        ) {
            abort(
                422,
                'SMTP non configuré. Configurez les paramètres MAIL_* dans .env.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Charger les relations nécessaires
        |--------------------------------------------------------------------------
        */

        $payment->load([
            'document.customer',
            'customer',
            'recorder',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Mise en queue du mail
        |--------------------------------------------------------------------------
        */

        try {

            Mail::to($to)->queue(
                new PaymentConfirmationMail(
                    $payment
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Journalisation
            |--------------------------------------------------------------------------
            |
            | Ici "queued" signifie :
            | le mail a été placé dans la file Redis.
            |
            | Ce n'est pas encore la confirmation que Gmail
            | l'a accepté.
            |
            */

            DocumentEmailLog::create([
                'commercial_document_id' =>
                    $payment->commercial_document_id,

                'recipient' => $to,

                'subject' =>
                    'HOPE - Reçu de paiement PAI-' .
                    $payment->id,

                'status' => 'queued',

                'sent_by' => $r->user()->id,
            ]);

            return response()->json([
                'message' =>
                    'Le reçu a été placé dans la file d’envoi.',

                'recipient' => $to,

                'status' => 'queued',
            ]);

        } catch (\Throwable $e) {

            report($e);

            /*
            |--------------------------------------------------------------------------
            | Journalisation de l'échec
            |--------------------------------------------------------------------------
            */

            DocumentEmailLog::create([
                'commercial_document_id' =>
                    $payment->commercial_document_id,

                'recipient' => $to,

                'subject' =>
                    'HOPE - Reçu de paiement PAI-' .
                    $payment->id,

                'status' => 'failed',

                'error' =>
                    app()->isLocal()
                        ? $e->getMessage()
                        : 'Erreur lors de la mise en queue',

                'sent_by' => $r->user()->id,
            ]);

            return response()->json(
                [
                    'message' =>
                        'Impossible de placer le reçu dans la file d’envoi.',

                    'error' =>
                        app()->isLocal()
                            ? $e->getMessage()
                            : null,
                ],
                500
            );
        }
    }
}
