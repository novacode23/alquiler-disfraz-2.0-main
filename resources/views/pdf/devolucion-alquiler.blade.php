<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Devolución</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 13px;
            line-height: 1.5;
            margin: 40px;
        }
        h2 {
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 12px;
        }
        .info {
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 40px;
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>
<body>

<h2>Recibo de Devolución</h2>
@php
    $piezasBuenas= $record->devolucionPiezas->filter(function ($item) {
        return $item->estado_pieza == \App\Enums\DevolucionPiezasEnum::BUENO->value;
    });
    $piezasDañadas = $record->devolucionPiezas->filter(function ($item) {
        return $item->estado_pieza !== \App\Enums\DevolucionPiezasEnum::BUENO->value;
    });

    $totalPiezasDañadas = $piezasDañadas->sum('cantidad');
    $totalMultaDañadas = $piezasDañadas->sum('multa_pieza');
@endphp

<div class="info">
    <p><strong>Nro. Devolución:</strong> #{{ $record->id }}</p>
    <p><strong>Cliente:</strong> {{ $record->alquiler->cliente->name }}</p>
    <p><strong>CI:</strong> {{ $record->alquiler->cliente->ci }}</p>
    <p><strong>Fecha de alquiler:</strong> {{ $record->alquiler->fecha_alquiler }}</p>
    <p><strong>Fecha pactada de devolución:</strong> {{ $record->alquiler->fecha_devolucion }}</p>
    <p><strong>Fecha real de devolución:</strong> {{ $record->fecha_devolucion_real }}</p>
</div>
@if ($piezasBuenas->isNotEmpty())
    <h3 style="margin-top: 40px;">Piezas en Buen estado</h3>

    <table>
        <thead>
            <tr>
                <th>Pieza</th>
                <th>Estado</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($piezasBuenas as $item)
                <tr>
                    <td>{{ $item->alquilerDisfrazPieza->pieza->name ?? '-' }}</td>
                    <td>{{ \App\Enums\DevolucionPiezasEnum::tryFrom($item->estado_pieza)?->getLabel() ?? '-' }}</td>
                    <td>{{ $item->cantidad }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
@if ($piezasDañadas->isNotEmpty())
    <h3 style="margin-top: 40px;">Piezas Dañadas o Perdidas</h3>

    <table>
        <thead>
            <tr>
                <th>Pieza</th>
                <th>Estado</th>
                <th>Cantidad</th>
                <th>Multa</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($piezasDañadas as $item)
                <tr>
                    <td>{{ $item->alquilerDisfrazPieza->pieza->name ?? '-' }}</td>
                    <td>{{ \App\Enums\DevolucionPiezasEnum::tryFrom($item->estado_pieza)?->getLabel() ?? '-' }}</td>
                    <td>{{ $item->cantidad }}</td>
                    <td>Bs {{ number_format($item->multa_pieza, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2" style="text-align: right;"><strong>Total</strong></td>
                <td><strong>{{ $totalPiezasDañadas }}</strong></td>
                <td><strong>Bs {{ number_format($totalMultaDañadas, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
@endif

@php
    $multaPiezas = $record->devolucionPiezas->sum('multa_pieza');
    $multaRetraso = $record->multa ?? 0;
    $multaTotal = $multaPiezas + $multaRetraso;
@endphp
<p style="margin-top: 20px;"><strong>Multa por piezas dañadas o perdidas:</strong> Bs {{ number_format($multaPiezas, 2) }}</p>
<p><strong>Multa por retraso:</strong> Bs {{ number_format($multaRetraso, 2) }}</p>
<p><strong>Multa total:</strong> Bs {{ number_format($multaTotal, 2) }}</p>
<hr style="margin: 30px 0;">

<div class="footer">
    Al firmar este recibo, el cliente reconoce la devolución parcial o total del material alquilado.<br>
    En caso de daño o pérdida, se aplicaron las penalidades correspondientes.<br>
    Gracias por confiar en nosotros.
</div>

</body>
</html>
