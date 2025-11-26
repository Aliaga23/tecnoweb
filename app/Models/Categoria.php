<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categoria';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

    public static function obtenerTodas()
    {
        return DB::select('SELECT * FROM categoria ORDER BY id');
    }

    public static function obtenerPorId($id)
    {
        $categorias = DB::select('SELECT * FROM categoria WHERE id = ? LIMIT 1', [$id]);
        return !empty($categorias) ? $categorias[0] : null;
    }

    public static function crearNueva($nombre)
    {
        DB::insert('INSERT INTO categoria (nombre) VALUES (?)', [$nombre]);
        
        $categoria = DB::select(
            'SELECT * FROM categoria WHERE nombre = ? ORDER BY id DESC LIMIT 1',
            [$nombre]
        )[0];
        
        return $categoria;
    }

    public static function existeNombreExcepto($nombre, $id = null)
    {
        if ($id) {
            $resultado = DB::select('SELECT COUNT(*) as total FROM categoria WHERE nombre = ? AND id != ?', [$nombre, $id]);
        } else {
            $resultado = DB::select('SELECT COUNT(*) as total FROM categoria WHERE nombre = ?', [$nombre]);
        }
        
        return $resultado[0]->total > 0;
    }

    public static function actualizarPorId($id, $nombre)
    {
        DB::update('UPDATE categoria SET nombre = ? WHERE id = ?', [$nombre, $id]);
        
        $categoriaActualizada = DB::select('SELECT * FROM categoria WHERE id = ? LIMIT 1', [$id])[0];
        
        return $categoriaActualizada;
    }

    public static function tieneProductosAsociados($id)
    {
        $productos = DB::select('SELECT COUNT(*) as total FROM producto WHERE categoria_id = ?', [$id]);
        return $productos[0]->total > 0;
    }

    public static function eliminarPorId($id)
    {
        return DB::delete('DELETE FROM categoria WHERE id = ?', [$id]);
    }
}
