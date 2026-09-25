<h2 style="font-family: Arial, sans-serif; color: #002B56;">Reclamo {{ $reclamo->numero }}</h2>

<p style="font-family: Arial, sans-serif; color: #334155; font-size: 15px;">
    <strong>{{ $reclamo->customer->name }}</strong> cargó un reclamo desde el sitio.
</p>

<table cellpadding="6" cellspacing="0" style="font-family: Arial, sans-serif; font-size: 14px; color: #334155;">
    <tr><td style="color:#64748b;">Fecha</td><td>{{ $reclamo->fecha->format('d/m/Y') }}</td></tr>
    <tr><td style="color:#64748b;">Factura</td><td>{{ $reclamo->factura_numero }}{{ $reclamo->factura_fecha ? ' · ' . $reclamo->factura_fecha->format('d/m/Y') : '' }}</td></tr>
    @if($reclamo->user?->esVendedor())
        <tr><td style="color:#64748b;">Lo cargó</td><td>{{ $reclamo->user->name }}</td></tr>
    @endif
</table>

<table cellpadding="8" cellspacing="0" width="100%"
       style="font-family: Arial, sans-serif; font-size: 14px; color: #334155; border-collapse: collapse; margin-top: 12px;">
    <tr style="background:#F1F5F9; text-align:left;">
        <th>Artículo</th><th align="center">Cantidad</th><th>Observación</th>
    </tr>
    @foreach($reclamo->items as $item)
        <tr style="border-bottom:1px solid #E2E8F0;">
            <td><strong>{{ $item->codigo }}</strong>@if($item->nombre)<br><span style="color:#64748b;">{{ $item->nombre }}</span>@endif</td>
            <td align="center">{{ $item->cantidad }}</td>
            <td>{{ $item->observacion ?: '—' }}</td>
        </tr>
    @endforeach
</table>

@if($reclamo->fotos->isNotEmpty())
    <p style="font-family: Arial, sans-serif; font-size: 14px; color: #334155;">
        Van adjuntas {{ $reclamo->fotos->count() }} {{ $reclamo->fotos->count() === 1 ? 'foto' : 'fotos' }}.
    </p>
@endif

<p style="font-family: Arial, sans-serif; font-size: 13px; color: #64748b;">
    El estado del reclamo se sigue desde el sitio, en «Reclamos».
</p>
