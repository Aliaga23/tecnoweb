<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizacion';
    public $timestamps = false;

    protected $fillable = [
        'fecha_cotizacion',
        'total',
        'cliente_id',
        'vendedor_id'
    ];

    protected $casts = [
        'fecha_cotizacion' => 'datetime',
        'total' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Usuario::class, 'cliente_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'vendedor_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCotizacion::class, 'cotizacion_id');
    }
}
