@php use App\Support\Precio; @endphp

<h2 style="font-family: Arial, sans-serif; color: #002B56;">Nuevo comprobante de pago</h2>

<p style="font-family: Arial, sans-serif; color: #334155; font-size: 15px;">
    {{ $comprobante->customer?->name ?? 'Un cliente sin identificar' }} cargó un comprobante desde el sitio.
</p>

<table cellpadding="6" cellspacing="0" style="font-family: Arial, sans-serif; font-size: 14px; color: #334155; border-collapse: collapse;">
    <tr>
        <td style="color: #64748b;">Cliente</td>
        <td><strong>{{ $comprobante->customer?->name ?? '—' }}</strong></td>
    </tr>
    @if($comprobante->user?->esVendedor())
        <tr>
            <td style="color: #64748b;">Lo envió</td>
            <td>{{ $comprobante->user->name }} ({{ $comprobante->user->email }})</td>
        </tr>
    @endif
    <tr>
        <td style="color: #64748b;">Fecha del pago</td>
        <td>{{ $comprobante->fecha?->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td style="color: #64748b;">Importe</td>
        <td><strong>{{ Precio::ar($comprobante->importe) }}</strong></td>
    </tr>
    <tr>
        <td style="color: #64748b;">Banco / Sucursal</td>
        <td>{{ $comprobante->banco }} · {{ $comprobante->sucursal }}</td>
    </tr>
    <tr>
        <td style="color: #64748b;">Facturas que cancela</td>
        <td>{{ $comprobante->facturas_canceladas ?: '—' }}</td>
    </tr>
    @if($comprobante->observaciones)
        <tr>
            <td style="color: #64748b; vertical-align: top;">Observaciones</td>
            <td>{{ $comprobante->observaciones }}</td>
        </tr>
    @endif
</table>

<p style="font-family: Arial, sans-serif; font-size: 14px;">
    El comprobante va adjunto y queda cargado en
    <a href="{{ route('admin.pagos.comprobantes.index') }}" style="color: #002B56;">Cuenta corriente</a>.
</p>
