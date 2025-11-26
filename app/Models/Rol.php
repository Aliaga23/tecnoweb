<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'rol';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

    public static function obtenerTodos()
    {
        return DB::select('SELECT * FROM rol ORDER BY id');
    }

    public static function obtenerPorId($id)
    {
        $roles = DB::select('SELECT * FROM rol WHERE id = ? LIMIT 1', [$id]);
        return !empty($roles) ? $roles[0] : null;
    }

    public static function existe($id)
    {
        $resultado = DB::select('SELECT COUNT(*) as total FROM rol WHERE id = ?', [$id]);
        return $resultado[0]->total > 0;
    }
}
