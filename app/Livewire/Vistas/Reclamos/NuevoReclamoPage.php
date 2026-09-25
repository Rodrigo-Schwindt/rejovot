<?php

namespace App\Livewire\Vistas\Reclamos;

use App\Mail\ReclamoCreadoMail;
use App\Models\Claim;
use App\Models\Product;
use App\Services\Odoo\OdooException;
use App\Services\Odoo\OdooFacturas;
use App\Services\Sesion\ClienteActivo;
use App\Support\Destinatarios;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Carga de un reclamo.
 *
 * La factura se elige de las del cliente en Odoo, pero también se puede
 * escribir a mano (una factura vieja que no aparece en la lista, por ejemplo).
 * Si se elige de la lista, los artículos se sugieren de esa factura y la
 * cantidad no puede pasar de lo facturado.
 */
#[Layout('layouts.public')]
class NuevoReclamoPage extends Component
{
    use WithFileUploads;

    public const MAX_FOTOS = 5;

    public string $facturaNumero = '';

    public string $facturaFecha = '';

    /** @var array<int, array{codigo:string, cantidad:int|string, observacion:string}> */
    public array $items = [];

    public array $fotos = [];

    /** Lo que se elige en el input: se suma a $fotos en vez de reemplazarlas. */
    public array $nuevasFotos = [];

    /** Facturas del cliente en Odoo: [id, numero, fecha]. */
    public array $facturas = [];

    /** Artículos de la factura elegida: [codigo, nombre, cantidad]. */
    public array $lineasFactura = [];

    /** Si Odoo no respondió, se avisa que la factura va a mano. */
    public bool $sinOdoo = false;

    public function mount(ClienteActivo $clienteActivo, OdooFacturas $odoo): void
    {
        $cliente = $clienteActivo->actual();

        if (! $cliente) {
            $this->redirectRoute('reclamos', navigate: true);

            return;
        }

        $this->items = [$this->itemVacio()];

        try {
            $this->facturas = array_map(fn (array $f) => [
                'id' => $f['id'],
                'numero' => $f['name'],
                'fecha' => $f['invoice_date'] ?: '',
            ], $odoo->delCliente($cliente->odoo_id));
        } catch (OdooException $e) {
            Log::warning('No se pudieron leer las facturas para el reclamo: ' . $e->getMessage());
            $this->sinOdoo = true;
        }
    }

    /** Al elegir una factura de la lista se completan la fecha y los artículos. */
    public function updatedFacturaNumero(ClienteActivo $clienteActivo, OdooFacturas $odoo): void
    {
        $factura = $this->facturaElegida();
        $this->lineasFactura = [];

        if (! $factura) {
            return;
        }

        $this->facturaFecha = $factura['fecha'];

        try {
            $lineas = $odoo->lineas($factura['id'], $clienteActivo->actual()->odoo_id);
        } catch (OdooException $e) {
            return;
        }

        $codigos = Product::whereIn('odoo_id', array_column($lineas, 'product_id'))->pluck('code', 'odoo_id');

        foreach ($lineas as $linea) {
            $codigo = $codigos[$linea['product_id']] ?? null;

            if ($codigo) {
                $this->lineasFactura[] = [
                    'codigo' => $codigo,
                    'nombre' => $linea['nombre'],
                    'cantidad' => (int) $linea['cantidad'],
                ];
            }
        }
    }

    public function agregarArticulo(): void
    {
        $this->items[] = $this->itemVacio();
    }

    public function quitarArticulo(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items) ?: [$this->itemVacio()];
    }

    public function updatedNuevasFotos(): void
    {
        $this->validate([
            'nuevasFotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'nuevasFotos.*.image' => 'Sólo se pueden adjuntar imágenes.',
            'nuevasFotos.*.mimes' => 'Las fotos tienen que ser JPG, PNG o WebP.',
            'nuevasFotos.*.max' => 'Cada foto puede pesar hasta 8 MB.',
        ]);

        $lugar = self::MAX_FOTOS - count($this->fotos);
        $this->fotos = array_merge($this->fotos, array_slice($this->nuevasFotos, 0, max(0, $lugar)));
        $this->nuevasFotos = [];
    }

    public function quitarFoto(int $i): void
    {
        unset($this->fotos[$i]);
        $this->fotos = array_values($this->fotos);
    }

    protected function rules(): array
    {
        return [
            'facturaNumero' => ['required', 'string', 'max:60'],
            'facturaFecha' => ['nullable', 'date', 'before_or_equal:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.codigo' => ['required', 'string', 'max:120'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.observacion' => ['nullable', 'string', 'max:1000'],
            'fotos' => ['array', 'max:' . self::MAX_FOTOS],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    protected function messages(): array
    {
        return [
            'facturaNumero.required' => 'Indicá la factura.',
            'facturaFecha.before_or_equal' => 'La fecha de la factura no puede ser futura.',
            'items.*.codigo.required' => 'Indicá el código del artículo.',
            'items.*.cantidad.required' => 'Indicá la cantidad.',
            'items.*.cantidad.min' => 'La cantidad tiene que ser al menos 1.',
            'fotos.max' => 'Podés adjuntar hasta ' . self::MAX_FOTOS . ' fotos.',
            'fotos.*.image' => 'Sólo se pueden adjuntar imágenes.',
            'fotos.*.mimes' => 'Las fotos tienen que ser JPG, PNG o WebP.',
            'fotos.*.max' => 'Cada foto puede pesar hasta 8 MB.',
        ];
    }

    public function enviar(ClienteActivo $clienteActivo)
    {
        $cliente = $clienteActivo->actual();

        if (! $cliente) {
            $this->dispatch('show-toast', message: $clienteActivo->motivo(), type: 'error');

            return null;
        }

        $this->validate();

        if (! $this->validarCantidades()) {
            return null;
        }

        $factura = $this->facturaElegida();
        $nombres = Product::whereIn('code', array_column($this->items, 'codigo'))->pluck('name', 'code');

        $reclamo = DB::transaction(function () use ($cliente, $factura, $nombres) {
            $reclamo = Claim::create([
                'customer_id' => $cliente->id,
                'user_id' => auth('sitio')->id(),
                'fecha' => now()->toDateString(),
                'factura_numero' => trim($this->facturaNumero),
                'factura_fecha' => $this->facturaFecha ?: null,
                'factura_odoo_id' => $factura['id'] ?? null,
                'estado' => 'enviado',
            ]);

            foreach ($this->items as $item) {
                $codigo = trim($item['codigo']);

                $reclamo->items()->create([
                    'codigo' => $codigo,
                    'nombre' => $nombres[$codigo] ?? null,
                    'cantidad' => (int) $item['cantidad'],
                    'observacion' => trim($item['observacion'] ?? '') ?: null,
                ]);
            }

            foreach ($this->fotos as $foto) {
                $reclamo->fotos()->create([
                    // Disco privado: son fotos de un cliente, no se publican.
                    'archivo' => $foto->storeAs(
                        'reclamos/' . $reclamo->id,
                        Str::random(40) . '.' . strtolower($foto->getClientOriginalExtension()),
                        'local',
                    ),
                    'archivo_original' => $foto->getClientOriginalName(),
                ]);
            }

            return $reclamo;
        });

        $this->avisar($reclamo);

        session()->flash('toast', "Recibimos tu reclamo {$reclamo->numero}. Te avisamos por mail cuando cambie de estado.");

        return $this->redirectRoute('reclamos.ver', $reclamo, navigate: true);
    }

    public function render()
    {
        return view('livewire.vistas.reclamos.nuevo-reclamo-page', [
            'hoy' => now()->format('d/m/Y'),
            'facturaElegida' => $this->facturaElegida(),
        ]);
    }

    /** Si la factura vino de la lista, no se puede reclamar más de lo facturado. */
    private function validarCantidades(): bool
    {
        if (! $this->lineasFactura) {
            return true;
        }

        $facturado = collect($this->lineasFactura)->pluck('cantidad', 'codigo');
        $ok = true;

        foreach ($this->items as $i => $item) {
            $codigo = trim($item['codigo']);

            if (! $facturado->has($codigo)) {
                $this->addError("items.{$i}.codigo", "El artículo {$codigo} no está en la factura {$this->facturaNumero}.");
                $ok = false;
            } elseif ((int) $item['cantidad'] > $facturado[$codigo]) {
                $this->addError("items.{$i}.cantidad", "En la factura hay {$facturado[$codigo]}.");
                $ok = false;
            }
        }

        return $ok;
    }

    private function facturaElegida(): ?array
    {
        $numero = trim($this->facturaNumero);

        foreach ($this->facturas as $factura) {
            if ($factura['numero'] === $numero) {
                return $factura;
            }
        }

        return null;
    }

    private function itemVacio(): array
    {
        return ['codigo' => '', 'cantidad' => 1, 'observacion' => ''];
    }

    /** Al cliente, a su vendedor y a Rejovot. Si el mail falla, el reclamo ya quedó. */
    private function avisar(Claim $reclamo): void
    {
        $reclamo->load(['customer.salesperson', 'items', 'fotos', 'user']);
        $destinos = Destinatarios::delCliente($reclamo->customer);

        if (! $destinos) {
            return;
        }

        try {
            Mail::to($destinos)->send(new ReclamoCreadoMail($reclamo));
        } catch (\Throwable $e) {
            Log::warning("No se pudo avisar del reclamo {$reclamo->numero}: " . $e->getMessage());
        }
    }
}
