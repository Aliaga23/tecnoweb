<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Venta extends Model
{
    protected $table = 'venta';
    public $timestamps = false;

    protected $fillable = [
        'fecha_venta',
        'tipo',
        'total',
        'estado',
        'cliente_id',
        'vendedor_id',
        'cotizacion_id'
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'total' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Usuario::class, 'cliente_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pago()
    {
        return $this->hasOne(Pago::class, 'venta_id');
    }

    public function devoluciones()
    {
        return $this->hasMany(Devolucion::class, 'venta_id');
    }

    public static function obtenerComprasPorUsuario($usuarioId)
    {
        $ventas = DB::select(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.tipo,
                v.total,
                v.estado,
                v.cliente_id
            FROM venta v
            WHERE v.cliente_id = ?
            ORDER BY v.fecha_venta DESC",
            [$usuarioId]
        );

        // Para cada venta, obtener detalles y pago
        foreach ($ventas as $venta) {
            // Obtener detalles de productos
            $venta->detalles = DB::select(
                "SELECT 
                    dv.id,
                    dv.cantidad,
                    dv.precio_unitario,
                    dv.subtotal,
                    dv.producto_id,
                    p.nombre as producto_nombre
                FROM detalle_venta dv
                LEFT JOIN producto p ON dv.producto_id = p.id
                WHERE dv.venta_id = ?",
                [$venta->id]
            );

            // Convertir detalles a formato objeto con producto
            foreach ($venta->detalles as $detalle) {
                $detalle->producto = (object)[
                    'id' => $detalle->producto_id,
                    'nombre' => $detalle->producto_nombre
                ];
            }

            // Obtener información de pago (solo campos que existen: monto, metodo, fecha_pago, venta_id)
            $venta->pago = DB::selectOne(
                "SELECT 
                    id,
                    monto,
                    metodo,
                    fecha_pago,
                    venta_id
                FROM pago
                WHERE venta_id = ?
                LIMIT 1",
                [$venta->id]
            );
        }

        return $ventas;
    }

    public static function obtenerCompraDetalle($ventaId, $usuarioId)
    {
        $venta = DB::selectOne(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.tipo,
                v.total,
                v.estado,
                p.metodo
            FROM venta v
            LEFT JOIN pago p ON v.id = p.venta_id
            WHERE v.id = ? AND v.cliente_id = ?",
            [$ventaId, $usuarioId]
        );

        if (!$venta) {
            return null;
        }

        $productos = DB::select(
            "SELECT 
                dv.cantidad,
                dv.precio_unitario,
                dv.subtotal,
                p.nombre as producto_nombre
            FROM detalle_venta dv
            INNER JOIN producto p ON dv.producto_id = p.id
            WHERE dv.venta_id = ?",
            [$ventaId]
        );

        $venta->productos = $productos;
        return $venta;
    }

    public static function obtenerTodasLasVentas()
    {
        return DB::select(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.tipo,
                v.total,
                v.estado,
                u.nombre as cliente_nombre,
                COUNT(dv.id) as cantidad_productos,
                SUM(dv.cantidad) as total_items
            FROM venta v
            LEFT JOIN usuario u ON v.cliente_id = u.id
            LEFT JOIN detalle_venta dv ON v.id = dv.venta_id
            GROUP BY v.id, v.fecha_venta, v.tipo, v.total, v.estado, v.cliente_id, v.vendedor_id, v.cotizacion_id, u.nombre
            ORDER BY v.fecha_venta DESC"
        );
    }

    public static function obtenerVentaDetalle($ventaId)
    {
        $venta = DB::selectOne(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.tipo,
                v.total,
                v.estado,
                v.cliente_id,
                v.vendedor_id,
                v.cotizacion_id,
                u.nombre as cliente_nombre,
                u.correo as cliente_correo
            FROM venta v
            LEFT JOIN usuario u ON v.cliente_id = u.id
            WHERE v.id = ?",
            [$ventaId]
        );

        if (!$venta) {
            return null;
        }

        $items = DB::select(
            "SELECT 
                dv.id,
                dv.cantidad,
                dv.precio_unitario,
                dv.subtotal,
                p.nombre as producto_nombre
            FROM detalle_venta dv
            INNER JOIN producto p ON dv.producto_id = p.id
            WHERE dv.venta_id = ?",
            [$ventaId]
        );

        $venta->items = $items;
        return $venta;
    }
}
