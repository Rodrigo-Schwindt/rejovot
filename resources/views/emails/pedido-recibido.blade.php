@php use App\Support\Precio; @endphp

<h2 style="font-family: Arial, sans-serif; color: #002B56;">Pedido {{ $pedido['numero'] }}</h2>

<p style="font-family: Arial, sans-serif; color: #334155; font-size: 15px;">
    Recibimos el pedido de <strong>{{ $pedido['cliente'] }}</strong>. Te avisamos cuando lo confirmemos.
</p>

<table cellpadding="8" cellspacing="0" width="100%"
       style="font-family: Arial, sans-serif; font-size: 14px; color: #334155; border-collapse: collapse;">
    <thead>
        <tr style="background: #F1F5F9; text-align: left;">
            <th>Producto</th>
            <th align="center">Cantidad</th>
            <th align="right">Precio</th>
            <th align="right">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pedido['lineas'] as $linea)
            <tr style="border-bottom: 1px solid #E2E8F0;">
                <td>
                    <strong>{{ $linea['codigo'] }}</strong><br>
                    <span style="color: #64748b;">{{ $linea['nombre'] }}</span>
                </td>
                <td align="center">{{ $linea['cantidad'] }}</td>
                <td align="right">{{ Precio::ar($linea['precio']) }}</td>
                <td align="right">{{ Precio::ar($linea['subtotal']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table cellpadding="6" cellspacing="0" align="right"
       style="font-family: Arial, sans-serif; font-size: 14px; color: #334155; margin-top: 8px;">
    @if(($pedido['totales']['descuento'] ?? 0) > 0)
        <tr>
            <td>Subtotal a precio de lista</td>
            <td align="right">{{ Precio::ar($pedido['totales']['lista']) }}</td>
        </tr>
        <tr style="color: #1E9E3E;">
            <td>Descuento</td>
            <td align="right">− {{ Precio::ar($pedido['totales']['descuento']) }}</td>
        </tr>
    @endif
    <tr>
        <td>Subtotal</td>
        <td align="right">{{ Precio::ar($pedido['totales']['subtotal']) }}</td>
    </tr>
    <tr>
        <td>{{ $pedido['entrega'] }}</td>
        <td align="right">{{ Precio::ar($pedido['totales']['envio']) }}</td>
    </tr>
    <tr>
        <td>IVA</td>
        <td align="right">{{ Precio::ar($pedido['totales']['iva']) }}</td>
    </tr>
    <tr style="font-weight: bold; font-size: 16px; color: #0f172a;">
        <td>Total</td>
        <td align="right">{{ Precio::ar($pedido['totales']['total']) }}</td>
    </tr>
</table>

<div style="clear: both;"></div>

@if($pedido['observaciones'])
    <p style="font-family: Arial, sans-serif; font-size: 14px; color: #334155;">
        <strong>Mensaje del cliente:</strong> {{ $pedido['observaciones'] }}
    </p>
@endif

@if($pedido['sin_stock'])
    <p style="font-family: Arial, sans-serif; font-size: 14px; color: #B8141B;">
        Sin stock, quedaron en el carrito para cuando ingresen:
        {{ implode(', ', $pedido['sin_stock']) }}
    </p>
@endif
