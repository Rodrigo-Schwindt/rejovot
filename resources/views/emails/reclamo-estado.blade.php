<h2 style="font-family: Arial, sans-serif; color: #002B56;">Garantía {{ $reclamo->numero }}</h2>

<p style="font-family: Arial, sans-serif; color: #334155; font-size: 15px;">
    Tu garantía{!! $reclamo->factura_numero ? ' sobre la factura <strong>' . e($reclamo->factura_numero) . '</strong>' : ' ' . e($reclamo->numero) !!} pasó a
    <strong>{{ $reclamo->estado_nombre }}</strong>.
</p>

@if($reclamo->respuesta)
    <div style="font-family: Arial, sans-serif; font-size: 14px; color: #334155; background:#F1F5F9; border-radius:6px; padding:12px 14px;">
        <strong>Respuesta de Rejovot:</strong><br>
        {!! nl2br(e($reclamo->respuesta)) !!}
    </div>
@endif

<p style="font-family: Arial, sans-serif; font-size: 13px; color: #64748b;">
    Podés ver el detalle en el sitio, en «Garantías».
</p>
