@php
    $cuenta = $cuenta ?? null;
    $i = $i ?? '__INDEX__';
    $placeholders = [
        'titular' => 'Rejovot',
        'banco' => 'Banco Nación',
        'tipo_cuenta' => 'Cuenta Corriente',
        'numero' => '011-345678/9',
        'cbu' => '01105995-55001234567890',
        'alias' => 'rejovot.alias',
        'cuit' => '30-12345678-9',
    ];
@endphp

<div class="cuenta-item space-y-4 rounded-lg border border-slate-200 p-4">
    <div class="flex items-center justify-between">
        <span class="sec-label">Cuenta bancaria</span>
        <button type="button" class="btn btn-danger btn-sm remove-cuenta">Eliminar</button>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach(\App\Models\BankAccount::CAMPOS as $campo => $etiqueta)
            <div>
                <label class="f-label">{{ $etiqueta }}</label>
                <input type="text" name="cuentas[{{ $i }}][{{ $campo }}]" class="f-input"
                       value="{{ $cuenta->$campo ?? '' }}" placeholder="{{ $placeholders[$campo] ?? '' }}">
            </div>
        @endforeach
    </div>
</div>
