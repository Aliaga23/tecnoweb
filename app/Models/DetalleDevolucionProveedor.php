<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleDevolucionProveedor extends Model
{
    protected $table = 'detalle_devolucion_proveedor';
    
    protected $fillable = [
        'id_devolucion_proveedor',
        'producto_id',
        'cantidad'
    ];

    public $timestamps = false;

    public function devolucionProveedor()
    {
        return $this->belongsTo(DevolucionProveedor::class, 'id_devolucion_proveedor');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}