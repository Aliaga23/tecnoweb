<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public static function obtenerTodas()
    {
        return DB::select("
            SELECT 
                c.id,
                c.fecha_cotizacion,
                c.total,
                c.cliente_id,
                c.vendedor_id,
                u.nombre || ' ' || u.apellido as cliente_nombre,
                u.correo as cliente_correo,
                u.telefono as cliente_telefono,
                v.nombre || ' ' || v.apellido as vendedor_nombre,
                COUNT(dc.id) as total_productos,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM venta vn WHERE vn.cotizacion_id = c.id
                    ) THEN 'Convertida en Venta'
                    WHEN c.fecha_cotizacion < NOW() - INTERVAL '30 days' THEN 'Expirada'
                    ELSE 'Pendiente'
                END as estado
            FROM cotizacion c
            LEFT JOIN usuario u ON c.cliente_id = u.id
            LEFT JOIN usuario v ON c.vendedor_id = v.id
            LEFT JOIN detalle_cotizacion dc ON c.id = dc.cotizacion_id
            GROUP BY c.id, c.fecha_cotizacion, c.total, c.cliente_id, c.vendedor_id,
                     u.nombre, u.apellido, u.correo, u.telefono,
                     v.nombre, v.apellido
            ORDER BY c.fecha_cotizacion DESC
        ");
    }

    public static function obtenerPorId($id)
    {
        $cotizacion = DB::selectOne("
            SELECT 
                c.id,
                c.fecha_cotizacion,
                c.total,
                c.cliente_id,
                u.nombre || ' ' || u.apellido as cliente,
                u.correo,
                u.telefono
            FROM cotizacion c
            LEFT JOIN usuario u ON c.cliente_id = u.id
            WHERE c.id = ?
        ", [$id]);

        if ($cotizacion) {
            $detalles = DB::select("
                SELECT 
                    dc.id,
                    dc.cantidad,
                    dc.costo_unitario,
                    dc.subtotal,
                    dc.producto_id,
                    p.nombre as producto,
                    p.descripcion
                FROM detalle_cotizacion dc
                LEFT JOIN producto p ON dc.producto_id = p.id
                WHERE dc.cotizacion_id = ?
            ", [$id]);

            $cotizacion->detalles = $detalles;
        }

        return $cotizacion;
    }

    public static function obtenerPorIdParaPDF($id)
    {
        $cotizacion = DB::selectOne("
            SELECT 
                c.id,
                c.fecha_cotizacion,
                c.total,
                c.cliente_id,
                u.nombre || ' ' || u.apellido as cliente,
                u.correo,
                u.telefono,
                u.ci
            FROM cotizacion c
            LEFT JOIN usuario u ON c.cliente_id = u.id
            WHERE c.id = ?
        ", [$id]);

        if ($cotizacion) {
            $detalles = DB::select("
                SELECT 
                    dc.id,
                    dc.cantidad,
                    dc.costo_unitario,
                    dc.subtotal,
                    p.nombre,
                    p.descripcion,
                    p.imagen_url
                FROM detalle_cotizacion dc
                LEFT JOIN producto p ON dc.producto_id = p.id
                WHERE dc.cotizacion_id = ?
                ORDER BY dc.id
            ", [$id]);

            return ['cotizacion' => $cotizacion, 'detalles' => $detalles];
        }

        return null;
    }

    public static function obtenerPorUsuario($usuario_id)
    {
        return DB::select("
            SELECT 
                c.id,
                c.fecha_cotizacion,
                c.total,
                c.cliente_id
            FROM cotizacion c
            WHERE c.cliente_id = ?
            ORDER BY c.fecha_cotizacion DESC
        ", [$usuario_id]);
    }

    public static function existe($id)
    {
        $resultado = DB::selectOne("SELECT id FROM cotizacion WHERE id = ?", [$id]);
        return $resultado !== null;
    }

    public static function crearNueva($cliente_id, $detalles)
    {
        DB::beginTransaction();
        
        try {
            $total = 0;
            foreach ($detalles as $detalle) {
                $total += $detalle['cantidad'] * $detalle['costo_unitario'];
            }

            $cotizacionId = DB::selectOne("
                INSERT INTO cotizacion (fecha_cotizacion, total, cliente_id, vendedor_id)
                VALUES (NOW(), ?, ?, 3)
                RETURNING id
            ", [$total, $cliente_id])->id;

            foreach ($detalles as $detalle) {
                $subtotal = $detalle['cantidad'] * $detalle['costo_unitario'];
                
                DB::insert("
                    INSERT INTO detalle_cotizacion (cantidad, costo_unitario, subtotal, cotizacion_id, producto_id)
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $detalle['cantidad'],
                    $detalle['costo_unitario'],
                    $subtotal,
                    $cotizacionId,
                    $detalle['producto_id']
                ]);
            }

            DB::commit();
            return $cotizacionId;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function actualizarPorId($id, $detalles)
    {
        DB::beginTransaction();
        
        try {
            $total = 0;
            foreach ($detalles as $detalle) {
                $total += $detalle['cantidad'] * $detalle['costo_unitario'];
            }

            DB::update("
                UPDATE cotizacion 
                SET total = ?, fecha_cotizacion = NOW()
                WHERE id = ?
            ", [$total, $id]);

            DB::delete("DELETE FROM detalle_cotizacion WHERE cotizacion_id = ?", [$id]);

            foreach ($detalles as $detalle) {
                $subtotal = $detalle['cantidad'] * $detalle['costo_unitario'];
                
                DB::insert("
                    INSERT INTO detalle_cotizacion (cantidad, costo_unitario, subtotal, cotizacion_id, producto_id)
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $detalle['cantidad'],
                    $detalle['costo_unitario'],
                    $subtotal,
                    $id,
                    $detalle['producto_id']
                ]);
            }

            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function eliminarPorId($id)
    {
        DB::beginTransaction();
        
        try {
            DB::delete("DELETE FROM detalle_cotizacion WHERE cotizacion_id = ?", [$id]);
            DB::delete("DELETE FROM cotizacion WHERE id = ?", [$id]);
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function obtenerDetalleCompleto($id)
    {
        $cotizacion = DB::selectOne("
            SELECT 
                c.id,
                c.fecha_cotizacion,
                c.total,
                c.cliente_id,
                c.vendedor_id,
                u.nombre || ' ' || u.apellido as cliente_nombre,
                u.correo as cliente_correo,
                u.telefono as cliente_telefono,
                u.ci as cliente_ci,
                v.nombre || ' ' || v.apellido as vendedor_nombre,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM venta vn WHERE vn.cotizacion_id = c.id
                    ) THEN 'Convertida en Venta'
                    WHEN c.fecha_cotizacion < NOW() - INTERVAL '30 days' THEN 'Expirada'
                    ELSE 'Pendiente'
                END as estado
            FROM cotizacion c
            LEFT JOIN usuario u ON c.cliente_id = u.id
            LEFT JOIN usuario v ON c.vendedor_id = v.id
            WHERE c.id = ?
        ", [$id]);

        if ($cotizacion) {
            $productos = DB::select("
                SELECT 
                    dc.id,
                    dc.cantidad,
                    dc.costo_unitario,
                    dc.subtotal,
                    p.id as producto_id,
                    p.nombre as producto_nombre,
                    p.descripcion as producto_descripcion,
                    p.precio_unitario as precio_producto,
                    cat.nombre as categoria_nombre
                FROM detalle_cotizacion dc
                INNER JOIN producto p ON dc.producto_id = p.id
                LEFT JOIN categoria cat ON p.categoria_id = cat.id
                WHERE dc.cotizacion_id = ?
                ORDER BY dc.id
            ", [$id]);
            
            $cotizacion->productos = $productos;
        }

        return $cotizacion;
    }
}
