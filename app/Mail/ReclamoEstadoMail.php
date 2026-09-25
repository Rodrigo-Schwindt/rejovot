<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Aviso al cliente de que su reclamo cambió de estado. */
class ReclamoEstadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Claim $reclamo)
    {
    }

    public function build(): self
    {
        return $this->subject("Tu reclamo {$this->reclamo->numero}: {$this->reclamo->estado_nombre}")
            ->view('emails.reclamo-estado')
            ->with(['reclamo' => $this->reclamo]);
    }
}
