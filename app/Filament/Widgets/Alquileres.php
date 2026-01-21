<?php

namespace App\Filament\Widgets;

use App\Models\Alquiler;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class Alquileres extends ChartWidget
{
    protected static ?string $heading = 'Alquileres Realizados';

    protected function getData(): array
    {
        $hoy = now();
        $inicio = $hoy->copy()->subMonths(3)->startOfMonth();
        $fin = $hoy->copy()->endOfMonth();

        $alquileres = Alquiler::whereBetween('fecha_alquiler', [$inicio, $fin])->get();

        // Agrupar en PHP por mes-año
        $alquileresPorMes = $alquileres
            ->groupBy(function ($alquiler) {
                return \Carbon\Carbon::parse($alquiler->fecha_alquiler)->format('m-Y');
            })
            ->map(fn($grupo) => $grupo->count());

        // Generar los últimos 4 meses para el eje X
        $meses = collect(range(0, 3))->map(function ($i) use ($hoy) {
            $fecha = $hoy->copy()->subMonths(3 - $i);
            return [
                'label' => ucfirst($fecha->locale('es')->monthName),
                'clave' => $fecha->format('m-Y'),
            ];
        });

        // Preparar el retorno para el gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Alquileres',
                    'data' => $meses->map(fn($mes) => $alquileresPorMes->get($mes['clave'], 0)),
                    'borderColor' => '#facc15',
                    'backgroundColor' => '#facc15',
                    'tension' => 0.3,
                    'fill' => false,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => $meses->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): ?array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0, // Evita negativos
                    'ticks' => [
                        'stepSize' => 1, // Ajuste opcional según tus datos
                    ],
                ],
            ],
        ];
    }
}
