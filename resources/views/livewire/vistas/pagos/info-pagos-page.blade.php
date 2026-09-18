<div>
    {{-- Cuentas bancarias y envío de comprobantes: datos propios, no vienen de Odoo. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-8 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#002B56]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span>Información de pagos</span>
        </nav>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)] lg:gap-12">
            @include('livewire.vistas.pagos.partials.cuentas-bancarias')
            @include('livewire.vistas.pagos.partials.formulario-comprobante')
        </div>
    </div>
</div>
