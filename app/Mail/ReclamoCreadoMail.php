<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Aviso de un reclamo nuevo: al cliente, a su vendedor y a Rejovot. */
class ReclamoCreadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Claim $reclamo)
    {
    }

    public function build(): self
    {
        return $this->subject("Reclamo {$this->reclamo->numero} · {$this->reclamo->customer->name}")
            ->view('emails.reclamo-creado')
            ->with(['reclamo' => $this->reclamo]);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return $this->reclamo->fotos
            ->filter(fn ($foto) => Storage::disk('local')->exists($foto->archivo))
            ->map(fn ($foto) => Attachment::fromPath(Storage::disk('local')->path($foto->archivo))
                ->as($foto->archivo_original ?: basename($foto->archivo)))
            ->values()
            ->all();
    }
}
