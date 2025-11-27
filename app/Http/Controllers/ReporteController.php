<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    // Reporte 1: Ventas por mes (últimos 6 meses)
    public function ventasPorMes()
    {
        try {
            $ventas = DB::select("
                SELECT 
                    TO_CHAR(fecha_venta, 'Mon') as mes,
                    COUNT(*) as cantidad,
                    SUM(total) as total
                FROM venta
                WHERE fecha_venta >= NOW() - INTERVAL '6 months'
                GROUP BY TO_CHAR(fecha_venta, 'Mon'), DATE_TRUNC('month', fecha_venta)
                ORDER BY DATE_TRUNC('month', fecha_venta) DESC
                LIMIT 6
            ");

            return response()->json([
                'success' => true,
                'data' => $ventas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ventas por mes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Reporte 2: Productos más vendidos (Top 5)
    public function productosTopVentas()
    {
        try {
            $productos = DB::select("
                SELECT 
                    p.nombre,
                    SUM(dv.cantidad) as cantidad_vendida,
                    SUM(dv.subtotal) as total_vendido
                FROM detalle_venta dv
                INNER JOIN producto p ON dv.producto_id = p.id
                GROUP BY p.id, p.nombre
                ORDER BY cantidad_vendida DESC
                LIMIT 5
            ");

            return response()->json([
                'success' => true,
                'data' => $productos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos más vendidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Reporte 3: Estado de ventas (pagadas vs pendientes)
    public function estadoVentas()
    {
        try {
            $estados = DB::select("
                SELECT 
                    estado,
                    COUNT(*) as cantidad,
                    SUM(total) as total
                FROM venta
                GROUP BY estado
            ");

            return response()->json([
                'success' => true,
                'data' => $estados
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado de ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Reporte 4: Productos con stock bajo (menos de 10 unidades)
    public function productosStockBajo()
    {
        try {
            $productos = DB::select("
                SELECT 
                    p.nombre,
                    p.stock_actual,
                    c.nombre as categoria
                FROM producto p
                LEFT JOIN categoria c ON p.categoria_id = c.id
                WHERE p.stock_actual < 10
                ORDER BY p.stock_actual ASC
            ");

            return response()->json([
                'success' => true,
                'data' => $productos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos con stock bajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
