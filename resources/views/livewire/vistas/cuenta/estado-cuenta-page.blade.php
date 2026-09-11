<div>
    {{-- Cuenta corriente del cliente activo, leída de Odoo. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#0D2B5E]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span>Estado de la cuenta</span>
        </nav>

        @if($cliente)
            <p class="mb-4 text-[14px] text-slate-500">
                Cuenta de <span class="font-semibold text-slate-700">{{ $cliente->name }}</span>
            </p>
        @endif

        @if($objetivos)
            @include('livewire.vistas.cuenta.partials.barra-objetivos')
        @endif

        @include('livewire.vistas.cuenta.partials.tarjetas-saldo')

        <div class="overflow-x-auto rounded-[4px] border border-slate-200 bg-white">
            <table class="w-full min-w-[1050px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[15px] font-medium text-slate-700">
                        <th class="px-4 py-5 text-left">Emisión</th>
                        <th class="px-4 py-5 text-left">Vencimiento</th>
                        <th class="px-4 py-5 text-left">Tipo</th>
                        <th class="px-4 py-5 text-left">Número</th>
                        <th class="px-4 py-5 text-left">Haber PS</th>
                        <th class="px-4 py-5 text-left">Saldo PS</th>
                        <th class="px-4 py-5 text-left">Importe origen</th>
                        <th class="px-4 py-5 text-left leading-[130%]">Importe bruto<br>moneda origen</th>
                        <th class="px-4 py-5 text-center"><span class="sr-only">Comprobante</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $movimiento)
                        @include('livewire.vistas.cuenta.partials.fila-movimiento', ['movimiento' => $movimiento])
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-14 text-center text-[14px] text-slate-500">
                                {{ $motivo ?: 'No tenés comprobantes pendientes.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
