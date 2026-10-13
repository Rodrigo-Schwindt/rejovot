<div>
    {{-- Cuenta corriente del cliente activo, leída de Odoo. --}}

    {{-- Franja celeste pegada al header, de borde a borde: migas de pan y objetivos del mes --}}
    <section class="w-full bg-[#EDF3F9]">
        {{-- max-lg:px-4: en pantallas angostas el contenido no queda pegado al borde. --}}
        <div class="mx-auto w-full max-w-[1300px]  pb-5 pt-6 max-lg:px-4">
            <nav class="anim-entrada text-[13px] text-slate-500" aria-label="Migas de pan">
                <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#002B56]">Inicio</a>
                <span class="mx-1.5 text-slate-400">&gt;</span>
                <span>Estado de la cuenta</span>
            </nav>

            @if($objetivos)
                @include('livewire.vistas.cuenta.partials.barra-objetivos')
            @endif
      
        
            @if($objetivos)
                @include('livewire.vistas.cuenta.partials.progreso-objetivo')
            @endif
        </div>
    </section>

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">


        @include('livewire.vistas.cuenta.partials.tarjetas-saldo')

        {{-- INFO: los comprobantes de la otra empresa (PRUEBA en Odoo). Los saldos
             de arriba ya suman las dos; esto sólo cambia lo que muestra la tabla. --}}
        @if($tieneInfo)
            <div class="anim-entrada mb-4 flex flex-wrap items-center gap-3" style="--retraso: 230">
                <button type="button" wire:click="alternarInfo" wire:loading.attr="disabled"
                        aria-pressed="{{ $cuenta === 'info' ? 'true' : 'false' }}"
                        class="h-[42px] cursor-pointer rounded-[4px] border border-[#002B56] px-6 text-[15px] font-bold uppercase tracking-wide transition disabled:opacity-60 {{ $cuenta === 'info' ? 'bg-[#002B56] text-white hover:bg-[#0A2249]' : 'bg-white text-[#002B56] hover:bg-[#002B56] hover:text-white' }}">
                    Info
                </button>
                @if($cuenta === 'info')
                    <span class="text-[14px] text-slate-600">
                        Estás viendo los comprobantes de INFO.
                        <button type="button" wire:click="alternarInfo" class="cursor-pointer font-semibold text-[#002B56] underline underline-offset-2">Volver</button>
                    </span>
                @endif
            </div>
        @endif

        {{-- En pantallas angostas la tabla conserva sus columnas y se desplaza en horizontal. --}}
        <p class="anim-entrada mb-2 flex items-center gap-1.5 text-[12px] text-slate-400 lg:hidden" style="--retraso: 260">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7l-4 5 4 5M16 7l4 5-4 5"/></svg>
            Deslizá para ver todas las columnas
        </p>
        {{-- relative: el sr-only (absolute) del encabezado queda dentro del recorte y no ensancha la página en mobile. --}}
        <div class="anim-entrada relative overflow-x-auto rounded-t-[4px] bg-white max-lg:overscroll-x-contain max-lg:[-webkit-overflow-scrolling:touch]" style="--retraso: 300">
            <table class="w-full min-w-[1180px] border-collapse">
                <thead>
                    <tr class="border-b border-[#D9D9D9] bg-[#F8F8F8] text-[18px] text-black">
                        <th class="px-3 py-5 text-left font-semibold">Emisión</th>
                        <th class="px-3 py-5 text-left font-semibold">Vencimiento</th>
                        <th class="px-3 py-5 text-left font-semibold">Tipo</th>
                        <th class="px-3 py-5 text-left font-semibold">Número</th>
                        <th class="px-3 py-5 text-right font-semibold">Importe factura</th>
                        <th class="px-3 py-5 text-right font-semibold">Pago a cuenta</th>
                        <th class="px-3 py-5 text-right font-semibold">Saldo</th>
                        <th class="px-3 py-5 text-center font-semibold">Status</th>
                        <th class="w-[70px] px-3 py-5 text-center"><span class="sr-only">Comprobante</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $movimiento)
                        @include('livewire.vistas.cuenta.partials.fila-movimiento', ['movimiento' => $movimiento])
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-14 text-center text-[14px] text-slate-500">
                                {{ $motivo ?: 'No tenés comprobantes.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hayMas)
            <div class="mt-6 flex justify-center">
                <button type="button" wire:click="verMas" wire:loading.attr="disabled" wire:target="verMas"
                        class="h-[44px] cursor-pointer rounded-[4px] border border-[#002B56] px-8 text-[14px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-[#002B56] hover:text-white disabled:opacity-60">
                    <span wire:loading.remove wire:target="verMas">Ver más</span>
                    <span wire:loading wire:target="verMas">Cargando…</span>
                </button>
            </div>
        @endif
    </div>
</div>
