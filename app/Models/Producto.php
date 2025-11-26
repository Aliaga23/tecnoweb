<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'producto';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'stock_actual',
        'precio_unitario',
        'creado_en',
        'imagen_url',
        'categoria_id'
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'stock_actual' => 'integer',
        'creado_en' => 'datetime'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function detallesDevolucionCliente()
    {
        return $this->hasMany(DetalleDevolucionCliente::class, 'producto_id');
    }

    public function detallesDevolucionProveedor()
    {
        return $this->hasMany(DetalleDevolucionProveedor::class, 'producto_id');
    }

    public static function obtenerDisponibles()
    {
        return DB::select("
            SELECT 
                id,
                nombre,
                precio_unitario as precio,
                stock_actual
            FROM producto
            WHERE stock_actual > 0
            ORDER BY nombre
        ");
    }

    public static function obtenerTodosConCategoria()
    {
        $productos = DB::select(
            'SELECT p.*, c.nombre as categoria_nombre 
             FROM producto p 
             LEFT JOIN categoria c ON p.categoria_id = c.id 
             ORDER BY p.id'
        );

        return array_map(function($p) {
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'descripcion' => $p->descripcion,
                'precio' => $p->precio_unitario,
                'stock' => $p->stock_actual,
                'imagen' => $p->imagen_url,
                'categoria_id' => $p->categoria_id,
                'categoria' => $p->categoria_nombre ? [
                    'nombre' => $p->categoria_nombre
                ] : null
            ];
        }, $productos);
    }

    public static function obtenerPorIdConCategoria($id)
    {
        $productos = DB::select(
            'SELECT p.*, c.nombre as categoria_nombre 
             FROM producto p 
             LEFT JOIN categoria c ON p.categoria_id = c.id 
             WHERE p.id = ? 
             LIMIT 1',
            [$id]
        );

        return !empty($productos) ? $productos[0] : null;
    }

    public static function obtenerPorId($id)
    {
        $productos = DB::select('SELECT * FROM producto WHERE id = ? LIMIT 1', [$id]);
        return !empty($productos) ? $productos[0] : null;
    }

    public static function crearNuevo($data)
    {
        $creado_en = date('Y-m-d H:i:s');
        
        DB::insert(
            'INSERT INTO producto (nombre, descripcion, stock_actual, precio_unitario, creado_en, imagen_url, categoria_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['nombre'],
                $data['descripcion'],
                $data['stock_actual'],
                $data['precio_unitario'],
                $creado_en,
                $data['imagen_url'],
                $data['categoria_id']
            ]
        );

        $producto = DB::select(
            'SELECT p.*, c.nombre as categoria_nombre 
             FROM producto p 
             LEFT JOIN categoria c ON p.categoria_id = c.id 
             WHERE p.nombre = ? AND p.creado_en = ?
             ORDER BY p.id DESC
             LIMIT 1',
            [$data['nombre'], $creado_en]
        )[0];

        return $producto;
    }

    public static function actualizarPorId($id, $data)
    {
        $productoActual = self::obtenerPorId($id);
        
        $nombre = $data['nombre'] ?? $productoActual->nombre;
        $descripcion = $data['descripcion'] ?? $productoActual->descripcion;
        $stock_actual = $data['stock_actual'] ?? $productoActual->stock_actual;
        $precio_unitario = $data['precio_unitario'] ?? $productoActual->precio_unitario;
        $imagen_url = $data['imagen_url'] ?? $productoActual->imagen_url;
        $categoria_id = $data['categoria_id'] ?? $productoActual->categoria_id;

        DB::update(
            'UPDATE producto 
             SET nombre = ?, descripcion = ?, stock_actual = ?, precio_unitario = ?, imagen_url = ?, categoria_id = ? 
             WHERE id = ?',
            [$nombre, $descripcion, $stock_actual, $precio_unitario, $imagen_url, $categoria_id, $id]
        );

        $productoActualizado = DB::select(
            'SELECT p.*, c.nombre as categoria_nombre 
             FROM producto p 
             LEFT JOIN categoria c ON p.categoria_id = c.id 
             WHERE p.id = ? 
             LIMIT 1',
            [$id]
        )[0];

        return $productoActualizado;
    }

    public static function eliminarPorId($id)
    {
        return DB::delete('DELETE FROM producto WHERE id = ?', [$id]);
    }

    public static function obtenerCatalogoCompleto()
    {
        return DB::select(
            'SELECT p.id, p.nombre, p.descripcion, p.stock_actual, p.precio_unitario, p.imagen_url, 
                    c.nombre as categoria_nombre, c.id as categoria_id
             FROM producto p 
             LEFT JOIN categoria c ON p.categoria_id = c.id 
             WHERE p.stock_actual > 0
             ORDER BY p.id'
        );
    }

    public static function obtenerProductoCatalogo($id)
    {
        $resultado = DB::select(
            'SELECT p.id, p.nombre, p.descripcion, p.stock_actual, p.precio_unitario, p.imagen_url, 
                    c.nombre as categoria_nombre, c.id as categoria_id
             FROM producto p 
             LEFT JOIN categoria c ON p.categoria_id = c.id 
             WHERE p.id = ? AND p.stock_actual > 0
             LIMIT 1',
            [$id]
        );
        return !empty($resultado) ? $resultado[0] : null;
    }

    public static function obtenerPorCategoria($categoria_id)
    {
        return DB::select(
            'SELECT p.id, p.nombre, p.descripcion, p.stock_actual, p.precio_unitario, p.imagen_url, 
                    c.nombre as categoria_nombre, c.id as categoria_id
             FROM producto p 
             LEFT JOIN categoria c ON p.categoria_id = c.id 
             WHERE p.categoria_id = ? AND p.stock_actual > 0
             ORDER BY p.nombre',
            [$categoria_id]
        );
    }

    public static function obtenerCategoriasConProductos()
    {
        return DB::select(
            'SELECT DISTINCT c.id, c.nombre, 
                    (SELECT COUNT(*) FROM producto p WHERE p.categoria_id = c.id AND p.stock_actual > 0) as total_productos
             FROM categoria c
             WHERE EXISTS (SELECT 1 FROM producto p WHERE p.categoria_id = c.id AND p.stock_actual > 0)
             ORDER BY c.nombre'
        );
    }
}
