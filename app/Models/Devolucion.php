<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Devolucion extends Model
{
    protected $table = 'devolucion';
    public $timestamps = false;

    protected $fillable = [
        'venta_id',
        'fecha_devolucion',
        'motivo'
    ];

    protected $casts = [
        'fecha_devolucion' => 'datetime'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleDevolucionCliente::class, 'devolucion_id');
    }

    public static function obtenerPorUsuario($usuarioId)
    {
        $devoluciones = DB::select(
            "SELECT 
                d.id,
                d.fecha_devolucion,
                d.motivo,
                d.venta_id,
                v.total as venta_total
            FROM devolucion d
            INNER JOIN venta v ON d.venta_id = v.id
            WHERE v.cliente_id = ?
            ORDER BY d.fecha_devolucion DESC",
            [$usuarioId]
        );

        // Para cada devolución, obtener los productos devueltos
        foreach ($devoluciones as $devolucion) {
            $devolucion->detalles = DB::select(
                "SELECT 
                    ddc.id,
                    ddc.cantidad,
                    ddc.producto_id,
                    ddc.devolucion_id,
                    p.nombre as producto_nombre,
                    p.precio_unitario
                FROM detalle_devolucion_cliente ddc
                INNER JOIN producto p ON ddc.producto_id = p.id
                WHERE ddc.devolucion_id = ?",
                [$devolucion->id]
            );

            // Convertir detalles a formato con producto
            foreach ($devolucion->detalles as $detalle) {
                $detalle->producto = (object)[
                    'id' => $detalle->producto_id,
                    'nombre' => $detalle->producto_nombre
                ];
            }
        }

        return $devoluciones;
    }

    public static function obtenerDetallePorIdYUsuario($id, $usuarioId)
    {
        $devolucion = DB::selectOne(
            "SELECT 
                d.id,
                d.fecha_devolucion,
                d.motivo,
                d.venta_id,
                v.total as venta_total
            FROM devolucion d
            INNER JOIN venta v ON d.venta_id = v.id
            WHERE d.id = ? AND v.cliente_id = ?",
            [$id, $usuarioId]
        );

        if (!$devolucion) {
            return null;
        }

        $productos = DB::select(
            "SELECT 
                ddc.cantidad,
                p.nombre as producto_nombre,
                p.precio_unitario
            FROM detalle_devolucion_cliente ddc
            INNER JOIN producto p ON ddc.producto_id = p.id
            WHERE ddc.devolucion_id = ?",
            [$id]
        );

        $devolucion->productos = $productos;
        return $devolucion;
    }

    public static function obtenerTodas()
    {
        $devoluciones = DB::select(
            "SELECT 
                d.id,
                d.fecha_devolucion,
                d.motivo,
                d.venta_id,
                u.nombre as cliente_nombre
            FROM devolucion d
            LEFT JOIN venta v ON d.venta_id = v.id
            LEFT JOIN usuario u ON v.cliente_id = u.id
            ORDER BY d.fecha_devolucion DESC"
        );

        foreach ($devoluciones as $devolucion) {
            $conteo = DB::selectOne(
                "SELECT COUNT(*) as total
                FROM detalle_devolucion_cliente 
                WHERE devolucion_id = ?",
                [$devolucion->id]
            );
            
            $devolucion->total_productos = $conteo ? (int)$conteo->total : 0;
        }

        return $devoluciones;
    }

    public static function obtenerDetalleProductos($id)
    {
        return DB::select(
            "SELECT 
                ddc.cantidad,
                p.nombre as producto_nombre,
                p.precio_unitario
            FROM detalle_devolucion_cliente ddc
            INNER JOIN producto p ON ddc.producto_id = p.id
            WHERE ddc.devolucion_id = ?",
            [$id]
        );
    }

    public static function obtenerDetalleCompleto($id)
    {
        $devolucion = DB::selectOne(
            "SELECT 
                d.id,
                d.fecha_devolucion,
                d.motivo,
                d.venta_id,
                u.nombre as cliente_nombre
            FROM devolucion d
            LEFT JOIN venta v ON d.venta_id = v.id
            LEFT JOIN usuario u ON v.cliente_id = u.id
            WHERE d.id = ?",
            [$id]
        );

        if (!$devolucion) {
            return null;
        }

        $productos = DB::select(
            "SELECT 
                ddc.cantidad,
                p.nombre as producto_nombre,
                p.precio_unitario
            FROM detalle_devolucion_cliente ddc
            INNER JOIN producto p ON ddc.producto_id = p.id
            WHERE ddc.devolucion_id = ?",
            [$id]
        );

        $devolucion->detalles = $productos;
        $devolucion->total_productos = count($productos);
        
        return $devolucion;
    }

    public static function buscarVentasPorCarnet($ci)
    {
        return DB::select(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.total,
                v.estado,
                u.nombre,
                u.ci,
                COUNT(dv.id) as total_productos
            FROM venta v
            INNER JOIN usuario u ON v.cliente_id = u.id
            INNER JOIN detalle_venta dv ON v.id = dv.venta_id
            WHERE u.ci = ? AND v.estado = 'pagada'
            GROUP BY v.id, v.fecha_venta, v.total, v.estado, u.nombre, u.ci
            ORDER BY v.fecha_venta DESC",
            [$ci]
        );
    }

    public static function obtenerDetalleVenta($ventaId)
    {
        return DB::select(
            "SELECT 
                dv.id,
                dv.cantidad,
                dv.precio_unitario,
                dv.subtotal,
                p.id as producto_id,
                p.nombre as producto_nombre
            FROM detalle_venta dv
            INNER JOIN producto p ON dv.producto_id = p.id
            WHERE dv.venta_id = ?",
            [$ventaId]
        );
    }

    public static function crearNuevaDevolucion($datos)
    {
        DB::beginTransaction();

        try {
            $devolucionId = DB::selectOne(
                "INSERT INTO devolucion (fecha_devolucion, motivo, venta_id)
                VALUES (NOW(), ?, ?)
                RETURNING id",
                [
                    $datos['motivo'],
                    $datos['venta_id']
                ]
            )->id;

            $totalDevuelto = 0;

            foreach ($datos['productos'] as $producto) {
                DB::insert(
                    "INSERT INTO detalle_devolucion_cliente (cantidad, devolucion_id, producto_id)
                    VALUES (?, ?, ?)",
                    [
                        $producto['cantidad'],
                        $devolucionId,
                        $producto['producto_id']
                    ]
                );

                DB::update(
                    "UPDATE producto 
                    SET stock_actual = stock_actual + ? 
                    WHERE id = ?",
                    [
                        $producto['cantidad'],
                        $producto['producto_id']
                    ]
                );

                $detalleVenta = DB::selectOne(
                    "SELECT precio_unitario, cantidad 
                    FROM detalle_venta 
                    WHERE venta_id = ? AND producto_id = ?",
                    [
                        $datos['venta_id'],
                        $producto['producto_id']
                    ]
                );

                $totalDevuelto += ($detalleVenta->precio_unitario * $producto['cantidad']);
                $nuevaCantidad = $detalleVenta->cantidad - $producto['cantidad'];
                
                if ($nuevaCantidad <= 0) {
                    DB::delete(
                        "DELETE FROM detalle_venta 
                        WHERE venta_id = ? AND producto_id = ?",
                        [
                            $datos['venta_id'],
                            $producto['producto_id']
                        ]
                    );
                } else {
                    $nuevoSubtotal = $nuevaCantidad * $detalleVenta->precio_unitario;
                    DB::update(
                        "UPDATE detalle_venta 
                        SET cantidad = ?, subtotal = ?
                        WHERE venta_id = ? AND producto_id = ?",
                        [
                            $nuevaCantidad,
                            $nuevoSubtotal,
                            $datos['venta_id'],
                            $producto['producto_id']
                        ]
                    );
                }
            }

            DB::update(
                "UPDATE venta 
                SET total = total - ? 
                WHERE id = ?",
                [
                    $totalDevuelto,
                    $datos['venta_id']
                ]
            );

            DB::commit();
            return $devolucionId;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
