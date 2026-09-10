{{-- Datos bancarios cargados desde el admin --}}
<div>
    <h1 class="text-[24px] font-bold leading-[130%] text-slate-900">
        Cuentas bancarias para efectuar el depósito:
    </h1>

    @forelse($cuentas as $cuenta)
        <div class="mt-4 space-y-1 text-[17px] leading-[160%] text-slate-800 {{ ! $loop->first ? 'border-t border-slate-200 pt-4' : '' }}">
            @foreach(\App\Models\BankAccount::CAMPOS as $campo => $etiqueta)
                @if($cuenta->$campo)
                    <p>{{ $etiqueta }}: {{ $cuenta->$campo }}</p>
                @endif
            @endforeach
        </div>
    @empty
        <p class="mt-4 text-[15px] text-slate-500">
            Todavía no se cargaron las cuentas bancarias.
        </p>
    @endforelse
</div>
