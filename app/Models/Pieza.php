<?php

namespace App\Models;
use App\Enums\PiezaStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pieza extends Model
{
    use HasFactory;
    protected $fillable = ['tipo_pieza_id', 'talla_id', 'name', 'valor_reposicion','alquiler_pieza', 'color', 'material', 'notas'];
    public function tipo()
    {
        return $this->belongsTo(TipoPieza::class, 'tipo_pieza_id');
    }
    public function talla()
    {
        return $this->belongsTo(Talla::class);
    }

    public function disfrazPiezas()
    {
        return $this->hasMany(DisfrazPieza::class);
    }
}
