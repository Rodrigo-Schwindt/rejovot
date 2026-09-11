<?php

namespace App\Livewire\Vistas\Pagos;

use App\Mail\ComprobantePagoMail;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\PaymentReceipt;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.public')]
class InfoPagosPage extends Component
{
    use WithFileUploads;

    public string $fecha = '';
    public string $importe = '';
    public string $banco = '';
    public string $sucursal = '';
    public string $facturas = '';
    public string $observaciones = '';
    public $archivo;

    protected function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'importe' => ['required', 'numeric', 'min:0'],
            'banco' => ['required', 'string', 'max:120'],
            'sucursal' => ['required', 'string', 'max:120'],
            'facturas' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'archivo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    protected function messages(): array
    {
        return [
            'fecha.required' => 'Ingresá la fecha del pago.',
            'importe.required' => 'Ingresá el importe.',
            'importe.numeric' => 'El importe tiene que ser un número.',
            'banco.required' => 'Ingresá el banco.',
            'sucursal.required' => 'Ingresá la sucursal.',
            'archivo.required' => 'Adjuntá el comprobante.',
            'archivo.mimes' => 'El comprobante tiene que ser JPG, PNG, WebP o PDF.',
            'archivo.max' => 'El comprobante no puede superar los 10 MB.',
        ];
    }

    public function enviar(ClienteActivo $clienteActivo): void
    {
        $datos = $this->validate();

        $cliente = $clienteActivo->actual();

        $comprobante = PaymentReceipt::create([
            // Queda atado al cliente activo para poder imputarlo.
            'customer_id' => $cliente?->id,
            'user_id' => auth('sitio')->id(),
            'fecha' => $datos['fecha'],
            'importe' => $datos['importe'],
            'banco' => $datos['banco'],
            'sucursal' => $datos['sucursal'],
            'facturas_canceladas' => $datos['facturas'] ?: null,
            'observaciones' => $datos['observaciones'] ?: null,
            'archivo' => $this->archivo->storeAs(
                'comprobantes',
                Str::random(40) . '.' . strtolower($this->archivo->getClientOriginalExtension()),
                'public',
            ),
            'archivo_original' => $this->archivo->getClientOriginalName(),
            'estado' => 'pendiente',
        ]);

        $this->avisarPorMail($comprobante);

        $this->reset(['fecha', 'importe', 'banco', 'sucursal', 'facturas', 'observaciones', 'archivo']);

        $this->dispatch('show-toast', message: 'Recibimos tu comprobante. ¡Gracias!', type: 'success');
    }

    /**
     * Avisa al mail de contacto que cargaron un comprobante. Si el mail falla,
     * el comprobante ya quedó guardado: no se pierde nada.
     */
    protected function avisarPorMail(PaymentReceipt $comprobante): void
    {
        $destino = Contact::first()?->mail_adm;

        if (! $destino) {
            return;
        }

        try {
            Mail::to($destino)->send(new ComprobantePagoMail($comprobante->fresh('customer', 'user')));
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar del comprobante ' . $comprobante->id . ': ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.vistas.pagos.info-pagos-page', [
            'cuentas' => BankAccount::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }
}
