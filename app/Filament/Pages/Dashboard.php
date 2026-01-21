<?php
namespace App\Filament\Pages;

use App\Filament\Widgets\Alquileres;
use App\Filament\Widgets\AlquileresChart;
use App\Filament\Widgets\AlquilerWidgetStats;
use App\Filament\Widgets\UltimosAlquileres;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Inicio';

    public function getTitle(): string
    {
        return 'Bienvenido(a), ' . strtoupper(Auth::user()->name);
    }
    public function getWidgets(): array
    {
        return [
            AlquilerWidgetStats::class,
            Alquileres::class,
            UltimosAlquileres::class,
            // otros widgets...
        ];
    }
}

?>
