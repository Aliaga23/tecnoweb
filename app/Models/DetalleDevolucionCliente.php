<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleDevolucionCliente extends Model
{
    protected $table = 'detalle_devolucion_cliente';
    public $timestamps = false;

    protected $fillable = [
        'devolucion_id',
        'producto_id',
        'cantidad'
    ];

    protected $casts = [
        'cantidad' => 'integer'
    ];

    public function devolucion()
    {
        return $this->belongsTo(Devolucion::class, 'devolucion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
