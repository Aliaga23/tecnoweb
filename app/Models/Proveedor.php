<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedor';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'telefono',
        'correo',
        'direccion'
    ];

    public function devoluciones()
    {
        return $this->hasMany(DevolucionProveedor::class, 'proveedor_id');
    }

    public static function obtenerTodos()
    {
        return DB::select('SELECT * FROM proveedor ORDER BY id');
    }

    public static function obtenerPorId($id)
    {
        $resultado = DB::select('SELECT * FROM proveedor WHERE id = ? LIMIT 1', [$id]);
        return !empty($resultado) ? $resultado[0] : null;
    }

    public static function existeCorreo($correo, $excludeId = null)
    {
        if ($excludeId) {
            $resultado = DB::select('SELECT COUNT(*) as count FROM proveedor WHERE correo = ? AND id != ?', [$correo, $excludeId]);
        } else {
            $resultado = DB::select('SELECT COUNT(*) as count FROM proveedor WHERE correo = ?', [$correo]);
        }
        return $resultado[0]->count > 0;
    }

    public static function crearNuevo($datos)
    {
        DB::insert(
            'INSERT INTO proveedor (nombre, telefono, correo, direccion) VALUES (?, ?, ?, ?)',
            [$datos['nombre'], $datos['telefono'], $datos['correo'], $datos['direccion']]
        );
        
        $resultado = DB::select(
            'SELECT * FROM proveedor WHERE correo = ? ORDER BY id DESC LIMIT 1',
            [$datos['correo']]
        );
        return $resultado[0];
    }

    public static function actualizarPorId($id, $datos)
    {
        DB::update(
            'UPDATE proveedor SET nombre = ?, telefono = ?, correo = ?, direccion = ? WHERE id = ?',
            [$datos['nombre'], $datos['telefono'], $datos['correo'], $datos['direccion'], $id]
        );
        
        $resultado = DB::select('SELECT * FROM proveedor WHERE id = ? LIMIT 1', [$id]);
        return $resultado[0];
    }

    public static function eliminarPorId($id)
    {
        return DB::delete('DELETE FROM proveedor WHERE id = ?', [$id]);
    }
}
