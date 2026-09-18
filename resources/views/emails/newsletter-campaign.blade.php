<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $asunto }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#002B56;padding:22px 28px;color:#ffffff;font-size:20px;font-weight:bold;">
                            REJOVOT AUTOPARTES
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;color:#1f2937;font-size:15px;line-height:1.7;">
                            <h1 style="margin:0 0 16px;font-size:19px;color:#002B56;">{{ $asunto }}</h1>
                            <div>{!! nl2br(e($cuerpo)) !!}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f4f6fa;padding:18px 28px;color:#6b7280;font-size:12px;">
                            Recibís este correo porque te suscribiste al newsletter de Rejovot Autopartes.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
