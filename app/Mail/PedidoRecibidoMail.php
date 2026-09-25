<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de un pedido nuevo cargado desde la web. Va al cliente, a su vendedor
 * y a la casilla fija de Rejovot.
 *
 * @param  array{numero:string, cliente:string, entrega:string, observaciones:string,
 *               lineas:array<int, array{codigo:string, nombre:string, cantidad:int, precio:float, subtotal:float}>,
 *               totales:array<string, float>, sin_stock:array<int, string>}  $pedido
 */
class PedidoRecibidoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $pedido)
    {
    }

    public function build(): self
    {
        return $this->subject("Pedido {$this->pedido['numero']} · {$this->pedido['cliente']}")
            ->view('emails.pedido-recibido')
            ->with(['pedido' => $this->pedido]);
    }
}
