<?php
declare(strict_types=1);
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
enum DevolucionPiezasEnum: string implements HasLabel, HasColor
{
    case BUENO = 'bueno';
    case LEVE = 'dano_leve';
    case MODERADO = 'dano_moderado';
    case GRAVE = 'dano_grave';
    case PERDIDO = 'perdido';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BUENO => 'Bueno',
            self::LEVE => 'Daños Leves',
            self::MODERADO => 'Daños Moderados',
            self::GRAVE => 'Daños Graves',
            self::PERDIDO => 'Perdido',
        };
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BUENO => 'success',
            self::LEVE => 'warning',
            self::MODERADO => 'secondary',
            self::GRAVE => 'info',
            self::PERDIDO => 'primary',
        };
    }
}
