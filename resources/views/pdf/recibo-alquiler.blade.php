<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Alquiler</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
        }
        .piezas-column {
            font-size: 12px;
        }
        .total-column {
            width: 90px;
            text-align: center;
        }
        .center-column {
            text-align: center;
        }
        .footer-text {
            font-size: 11px;
            text-align: center;
            margin-top: auto;
            padding-top: 30px;
        }
    </style>
</head>
<body>
    <img src="{{ public_path('storage/logo/logo.png') }}" alt="Logo" style="height: 80px; float: right; margin-left: 20px; margin-bottom: 10px;">

    <h2>Recibo de Alquiler</h2>
    <p><strong>Nro. Recibo:</strong> #{{ $record->id }}</p>
    <p><strong>Cliente:</strong> {{ $record->cliente->name }}</p>
    <p><strong>CI:</strong> {{ $record->cliente->ci }}</p>
    <p><strong>Fecha de alquiler:</strong> {{ $record->fecha_alquiler }}</p>
    <p><strong>Fecha de devolución:</strong> {{ $record->fecha_devolucion }}</p>

    <table>
        <thead>
            <tr>
                <th>Disfraz</th>
                <th>Piezas</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th class="total-column">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $valoresReposicion = collect(); @endphp

            @foreach ($record->alquilerDisfrazs as $item)
                @php
                    $piezas = \App\Models\Pieza::whereIn('id', $item->piezas_seleccionadas ?? [])
                        ->with('tipo')
                        ->get();
                    $valoresReposicion = $valoresReposicion->merge($piezas);
                    $piezasTexto = $piezas->map(fn($pieza) => $pieza->tipo->name ?? 'Sin tipo')->toArray();
                @endphp
                <tr>
                    <td>{{ $item->disfraz->nombre }} - {{ ucfirst($item->disfraz->genero) }}</td>
                    <td class="piezas-column">{{ implode(', ', $piezasTexto) }}</td>
                    <td class="center-column">{{ $item->cantidad }}</td>
                    <td class="center-column">Bs {{ number_format($item->precio_alquiler, 2) }}</td>
                    <td class="total-column">Bs {{ number_format($item->cantidad * $item->precio_alquiler, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $total = $record->alquilerDisfrazs->sum(fn($item) => $item->cantidad * $item->precio_alquiler);
        $reposiciones = $valoresReposicion
            ->groupBy(fn($pieza) => $pieza->tipo->name ?? 'Sin tipo')
            ->map(fn($grupo, $nombre) =>
                $nombre . ' Bs ' . number_format($grupo->first()->valor_reposicion ?? 0, 2)
            )->values()->toArray();
    @endphp

    <p><strong>SubTotal:</strong> Bs {{ number_format($total, 2) }}</p>
    <p><strong>Tipo de Garantía:</strong> {{ ucfirst($record->tipo_garantia) }}</p>
    <p><strong>Valor de Garantía:</strong> Bs {{ number_format($record->valor_garantia, 2) }}</p>
    <p><strong>Total:</strong> Bs {{ number_format($total + $record->valor_garantia, 2) }}</p>

    @php
    $valoresPorDisfraz = [];

    foreach ($record->alquilerDisfrazs as $item) {
        $piezas = \App\Models\Pieza::whereIn('id', $item->piezas_seleccionadas ?? [])->with('tipo')->get();
        $valores = $piezas->map(fn($pieza) =>
            ($pieza->tipo->name ?? 'Sin tipo') . ' Bs ' . number_format($pieza->valor_reposicion, 2)
        )->toArray();

        $valoresPorDisfraz[$item->disfraz->nombre . ' - ' . ucfirst($item->disfraz->genero)] = $valores;
    }
@endphp
<p><strong>Valores de reposición:</strong></p>
@foreach ($valoresPorDisfraz as $disfraz => $valores)
    <p style="margin: 0 0 5px 15px;">
        • <strong>{{ $disfraz }}:</strong> {{ implode(', ', $valores) }}
    </p>
@endforeach

    <hr style="margin: 20px 0;">

    <div class="footer-text">
        Al firmar este recibo, el cliente se compromete a devolver el disfraz alquilado y sus piezas en buen estado.
        En caso de pérdida o daño, se aplicará el valor de reposición correspondiente. La garantía será devuelta
        únicamente si todo el material es entregado según lo acordado. Cualquier retraso en la devolución generará
        un cargo adicional por día.
    </div>
</body>
</html>
