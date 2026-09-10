@php
    $item = $item ?? null;
    $type = $item?->type ?? 'whatsapp';
    $value = $item?->value ?? '';
    $fixed = (bool) ($item?->is_fixed ?? false);
    $placeholder = match ($type) {
        'email' => 'ventas@rejovot.com.ar',
        'direccion' => 'Fischetti 3887, Santos Lugares',
        'telefono' => '(+5411) 7704-1512',
        'whatsapp', 'whatsapp_flotante' => '5491130165209',
        default => '',
    };
@endphp

<div class="info-item grid grid-cols-1 items-start gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-[220px_1fr_auto]"
     data-fixed="{{ $fixed ? '1' : '0' }}">
    <div>
        <label class="f-label">Tipo</label>
        @if($fixed)
            <input type="hidden" name="ci_type[]" value="{{ $type }}">
            <div class="f-input flex cursor-not-allowed items-center bg-slate-50 text-slate-500">
                {{ \App\Models\ContactInfoItem::TYPES[$type] ?? $type }}
            </div>
        @else
            <select name="ci_type[]" class="f-input">
                @foreach(\App\Models\ContactInfoItem::TYPES as $tvalue => $tlabel)
                    <option value="{{ $tvalue }}" @selected($type === $tvalue)>{{ $tlabel }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div>
        <label class="f-label">Valor</label>
        <input type="text" name="ci_value[]" value="{{ $value }}" class="f-input" placeholder="{{ $placeholder }}">
    </div>

    <div class="flex items-end">
        @if($fixed)
            <span class="py-2 text-xs italic text-slate-400">Fijo (no se puede eliminar)</span>
        @else
            <button type="button" class="btn btn-danger btn-sm remove-info-item">Eliminar</button>
        @endif
    </div>

    <input type="hidden" name="ci_fixed[]" value="{{ $fixed ? '1' : '0' }}">
</div>
