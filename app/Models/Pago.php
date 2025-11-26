<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pago extends Model
{
    protected $table = 'pago';
    public $timestamps = false;

    protected $fillable = [
        'venta_id',
        'monto',
        'metodo',
        'fecha_pago',
        'transaccion_id'
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public static function obtenerTodos()
    {
        return DB::select(
            "SELECT 
                p.id,
                p.monto,
                p.metodo as metodo_pago,
                p.fecha_pago,
                p.venta_id,
                u.nombre as cliente_nombre
            FROM pago p
            LEFT JOIN venta v ON p.venta_id = v.id
            LEFT JOIN usuario u ON v.cliente_id = u.id
            ORDER BY p.fecha_pago DESC"
        );
    }

    public static function obtenerPorId($id)
    {
        return DB::selectOne(
            "SELECT 
                p.id,
                p.monto,
                p.metodo as metodo_pago,
                p.fecha_pago,
                p.venta_id,
                u.nombre as cliente_nombre
            FROM pago p
            LEFT JOIN venta v ON p.venta_id = v.id
            LEFT JOIN usuario u ON v.cliente_id = u.id
            WHERE p.id = ?",
            [$id]
        );
    }

    public static function crearVentaConDetalles($clienteId, $productos, $total, $cotizacionId = null)
    {
        DB::beginTransaction();

        try {
            $ventaId = DB::selectOne(
                "INSERT INTO venta (fecha_venta, tipo, total, estado, cliente_id, vendedor_id, cotizacion_id)
                VALUES (NOW(), 'contado', ?, 'pendiente', ?, 3, ?)
                RETURNING id",
                [$total, $clienteId, $cotizacionId]
            )->id;

            foreach ($productos as $producto) {
                DB::insert(
                    "INSERT INTO detalle_venta (cantidad, precio_unitario, subtotal, venta_id, producto_id)
                    VALUES (?, ?, ?, ?, ?)",
                    [
                        $producto['cantidad'],
                        $producto['costo_unitario'],
                        $producto['cantidad'] * $producto['costo_unitario'],
                        $ventaId,
                        $producto['producto_id']
                    ]
                );
            }

            $pagoId = DB::selectOne(
                "INSERT INTO pago (monto, metodo, fecha_pago, venta_id)
                VALUES (?, 'qr', NOW(), ?)
                RETURNING id",
                [$total, $ventaId]
            )->id;

            DB::commit();
            return ['venta_id' => $ventaId, 'pago_id' => $pagoId];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function actualizarEstadoVenta($ventaId, $estado)
    {
        DB::update(
            "UPDATE venta SET estado = ? WHERE id = ?",
            [$estado, $ventaId]
        );
    }

    public static function consultarEstadoPago($pagoId)
    {
        return DB::selectOne(
            "SELECT 
                p.id,
                p.monto,
                p.fecha_pago,
                v.estado as venta_estado
            FROM pago p
            INNER JOIN venta v ON p.venta_id = v.id
            WHERE p.id = ?",
            [$pagoId]
        );
    }
}
