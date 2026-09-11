<?php

namespace App\Mail;

use App\Models\PaymentReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Aviso a Rejovot de que un cliente cargó un comprobante de pago. */
class ComprobantePagoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PaymentReceipt $comprobante)
    {
    }

    public function build(): self
    {
        $cliente = $this->comprobante->customer?->name ?? 'Un cliente';

        return $this->subject("Comprobante de pago de {$cliente}")
            ->view('emails.comprobante-pago')
            ->with(['comprobante' => $this->comprobante]);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        if (! $this->comprobante->archivo || ! Storage::disk('public')->exists($this->comprobante->archivo)) {
            return [];
        }

        return [
            Attachment::fromPath(Storage::disk('public')->path($this->comprobante->archivo))
                ->as($this->comprobante->archivo_original ?: 'comprobante.pdf'),
        ];
    }
}
