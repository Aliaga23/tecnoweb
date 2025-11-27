<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Transaccion extends Model
{
    public static function obtenerTodasLasTransacciones()
    {
        $ventas = DB::select(
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
                COUNT(dv.id) as cantidad_productos,
                SUM(dv.cantidad) as total_items
            FROM venta v
            LEFT JOIN usuario u ON v.cliente_id = u.id
            LEFT JOIN detalle_venta dv ON v.id = dv.venta_id
            GROUP BY v.id, v.fecha_venta, v.tipo, v.total, v.estado, v.cliente_id, v.vendedor_id, v.cotizacion_id, u.nombre
            ORDER BY v.fecha_venta DESC"
        );

        $pagos = DB::select(
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

        return [
            'ventas' => $ventas,
            'pagos' => $pagos
        ];
    }

    public static function obtenerDetalleVenta($id)
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
            [$id]
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
                dv.producto_id,
                p.nombre as producto_nombre
            FROM detalle_venta dv
            INNER JOIN producto p ON dv.producto_id = p.id
            WHERE dv.venta_id = ?",
            [$id]
        );

        $venta->items = $items;
        return $venta;
    }

    public static function obtenerResumen()
    {
        $resultado = DB::selectOne(
            "SELECT 
                (SELECT COUNT(*) FROM venta) as total_ventas,
                (SELECT COUNT(*) FROM pago) as total_pagos,
                (SELECT COALESCE(SUM(total), 0) FROM venta) as monto_total_ventas,
                (SELECT COALESCE(SUM(monto), 0) FROM pago) as monto_total_pagos"
        );

        return $resultado;
    }

    public static function crearVentaCredito($cliente_id, $productos, $monto_adelanto = 0, $metodo_adelanto = null, $vendedor_id = null)
    {
        return DB::transaction(function() use ($cliente_id, $productos, $monto_adelanto, $metodo_adelanto, $vendedor_id) {
            // Calcular total
            $total = array_reduce($productos, function($carry, $item) {
                return $carry + ($item['cantidad'] * $item['precio_unitario']);
            }, 0);

            // Crear venta
            $venta_id = DB::table('venta')->insertGetId([
                'fecha_venta' => now()->addDay(),
                'tipo' => 'credito',
                'total' => $total,
                'estado' => 'pendiente',
                'cliente_id' => $cliente_id,
                'vendedor_id' => $vendedor_id,
                'cotizacion_id' => null
            ]);

            // Insertar detalles y actualizar stock
            foreach ($productos as $producto) {
                DB::table('detalle_venta')->insert([
                    'venta_id' => $venta_id,
                    'producto_id' => $producto['producto_id'],
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['precio_unitario'],
                    'subtotal' => $producto['cantidad'] * $producto['precio_unitario']
                ]);

                // Actualizar stock
                DB::table('producto')
                    ->where('id', $producto['producto_id'])
                    ->decrement('stock_actual', $producto['cantidad']);
            }

            // Registrar adelanto si existe
            if ($monto_adelanto > 0 && $metodo_adelanto) {
                DB::table('pago')->insert([
                    'venta_id' => $venta_id,
                    'monto' => $monto_adelanto,
                    'metodo' => $metodo_adelanto,
                    'fecha_pago' => now()
                ]);
            }

            return self::obtenerDetalleVenta($venta_id);
        });
    }

    public static function registrarPagoCredito($venta_id, $monto, $metodo)
    {
        return DB::transaction(function() use ($venta_id, $monto, $metodo) {
            // Obtener venta
            $venta = DB::table('venta')->where('id', $venta_id)->first();
            
            if (!$venta) {
                throw new \Exception('Venta no encontrada');
            }

            if ($venta->tipo !== 'credito') {
                throw new \Exception('Esta venta no es al crédito');
            }

            // Calcular total pagado
            $total_pagado = DB::table('pago')
                ->where('venta_id', $venta_id)
                ->sum('monto');

            $nuevo_total = $total_pagado + $monto;

            if ($nuevo_total > $venta->total) {
                throw new \Exception('El monto excede la deuda pendiente');
            }

            // Registrar pago
            $pago_id = DB::table('pago')->insertGetId([
                'venta_id' => $venta_id,
                'monto' => $monto,
                'metodo' => $metodo,
                'fecha_pago' => now()
            ]);

            // Actualizar estado de venta si está totalmente pagada
            if ($nuevo_total >= $venta->total) {
                DB::table('venta')
                    ->where('id', $venta_id)
                    ->update(['estado' => 'pagada']);
            }

            $pago = DB::table('pago')->where('id', $pago_id)->first();
            
            return [
                'pago' => $pago,
                'mensaje' => $nuevo_total >= $venta->total 
                    ? 'Venta pagada completamente' 
                    : 'Pago registrado exitosamente'
            ];
        });
    }

    public static function obtenerVentasCredito()
    {
        $ventas = DB::select(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.total,
                v.estado,
                v.cliente_id,
                u.nombre || ' ' || u.apellido as cliente_nombre,
                COALESCE(SUM(p.monto), 0) as monto_pagado,
                v.total - COALESCE(SUM(p.monto), 0) as saldo_pendiente
            FROM venta v
            LEFT JOIN usuario u ON v.cliente_id = u.id
            LEFT JOIN pago p ON v.id = p.venta_id
            WHERE v.tipo = 'credito'
            GROUP BY v.id, v.fecha_venta, v.total, v.estado, v.cliente_id, u.nombre, u.apellido
            ORDER BY v.fecha_venta DESC"
        );

        // Obtener pagos para cada venta
        foreach ($ventas as $venta) {
            $venta->pagos = DB::select(
                "SELECT 
                    id,
                    monto,
                    metodo,
                    fecha_pago
                FROM pago
                WHERE venta_id = ?
                ORDER BY fecha_pago DESC",
                [$venta->id]
            );
        }

        return $ventas;
    }

    public static function obtenerVentasCreditoPorCliente($cliente_id)
    {
        $ventas = DB::select(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.total,
                v.estado,
                COALESCE(SUM(p.monto), 0) as monto_pagado,
                v.total - COALESCE(SUM(p.monto), 0) as saldo_pendiente
            FROM venta v
            LEFT JOIN pago p ON v.id = p.venta_id
            WHERE v.tipo = 'credito' AND v.cliente_id = ?
            GROUP BY v.id, v.fecha_venta, v.total, v.estado
            ORDER BY v.fecha_venta DESC",
            [$cliente_id]
        );

        // Obtener pagos para cada venta
        foreach ($ventas as $venta) {
            $venta->pagos = DB::select(
                "SELECT 
                    id,
                    monto,
                    metodo,
                    fecha_pago
                FROM pago
                WHERE venta_id = ?
                ORDER BY fecha_pago DESC",
                [$venta->id]
            );
        }

        return $ventas;
    }

    // Métodos para Ventas al Contado
    public static function crearVentaContado($cliente_id, $productos, $metodo_pago, $vendedor_id = null)
    {
        return DB::transaction(function() use ($cliente_id, $productos, $metodo_pago, $vendedor_id) {
            // Calcular total
            $total = array_reduce($productos, function($carry, $item) {
                return $carry + ($item['cantidad'] * $item['precio_unitario']);
            }, 0);

            // Crear venta
            $venta_id = DB::table('venta')->insertGetId([
                'fecha_venta' => now(),
                'tipo' => 'contado',
                'total' => $total,
                'estado' => 'pagada',  // Al contado se paga inmediatamente
                'cliente_id' => $cliente_id,
                'vendedor_id' => $vendedor_id,
                'cotizacion_id' => null
            ]);

            // Insertar detalles y actualizar stock
            foreach ($productos as $producto) {
                DB::table('detalle_venta')->insert([
                    'venta_id' => $venta_id,
                    'producto_id' => $producto['producto_id'],
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['precio_unitario'],
                    'subtotal' => $producto['cantidad'] * $producto['precio_unitario']
                ]);

                // Actualizar stock
                DB::table('producto')
                    ->where('id', $producto['producto_id'])
                    ->decrement('stock_actual', $producto['cantidad']);
            }

            // Registrar pago completo
            DB::table('pago')->insert([
                'venta_id' => $venta_id,
                'monto' => $total,
                'metodo' => $metodo_pago,
                'fecha_pago' => now()
            ]);

            return self::obtenerDetalleVenta($venta_id);
        });
    }

    public static function registrarPagoContado($venta_id, $monto, $metodo)
    {
        return DB::transaction(function() use ($venta_id, $monto, $metodo) {
            // Obtener venta
            $venta = DB::table('venta')->where('id', $venta_id)->first();
            
            if (!$venta) {
                throw new \Exception('Venta no encontrada');
            }

            if ($venta->tipo !== 'contado') {
                throw new \Exception('Esta venta no es al contado');
            }

            // Calcular total pagado
            $total_pagado = DB::table('pago')
                ->where('venta_id', $venta_id)
                ->sum('monto');

            $nuevo_total = $total_pagado + $monto;

            if ($nuevo_total > $venta->total) {
                throw new \Exception('El monto excede la deuda pendiente');
            }

            // Registrar pago
            $pago_id = DB::table('pago')->insertGetId([
                'venta_id' => $venta_id,
                'monto' => $monto,
                'metodo' => $metodo,
                'fecha_pago' => now()
            ]);

            // Actualizar estado de venta si está totalmente pagada
            if ($nuevo_total >= $venta->total) {
                DB::table('venta')
                    ->where('id', $venta_id)
                    ->update(['estado' => 'pagada']);
            }

            $pago = DB::table('pago')->where('id', $pago_id)->first();
            
            return [
                'pago' => $pago,
                'mensaje' => $nuevo_total >= $venta->total 
                    ? 'Venta pagada completamente' 
                    : 'Pago registrado exitosamente'
            ];
        });
    }

    public static function obtenerVentasContado()
    {
        $ventas = DB::select(
            "SELECT 
                v.id,
                v.fecha_venta,
                v.total,
                v.estado,
                v.cliente_id,
                u.nombre || ' ' || u.apellido as cliente_nombre,
                COALESCE(SUM(p.monto), 0) as monto_pagado,
                v.total - COALESCE(SUM(p.monto), 0) as saldo_pendiente
            FROM venta v
            LEFT JOIN usuario u ON v.cliente_id = u.id
            LEFT JOIN pago p ON v.id = p.venta_id
            WHERE v.tipo = 'contado'
            GROUP BY v.id, v.fecha_venta, v.total, v.estado, v.cliente_id, u.nombre, u.apellido
            ORDER BY v.fecha_venta DESC"
        );

        // Obtener pagos para cada venta
        foreach ($ventas as $venta) {
            $venta->pagos = DB::select(
                "SELECT 
                    id,
                    monto,
                    metodo,
                    fecha_pago
                FROM pago
                WHERE venta_id = ?
                ORDER BY fecha_pago DESC",
                [$venta->id]
            );
        }

        return $ventas;
    }
}