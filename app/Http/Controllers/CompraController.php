<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $usuarioId = session('usuario_id');

        if (!$usuarioId) {
            return Inertia::render('MisCompras', [
                'compras' => []
            ]);
        }

        $compras = Venta::obtenerComprasPorUsuario($usuarioId);

        return Inertia::render('MisCompras', [
            'compras' => $compras
        ]);
    }

    public function obtenerCompras(Request $request)
    {
        try {
            $usuario = auth()->user();

            if (!$usuario) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $compras = Venta::obtenerComprasPorUsuario($usuario->id);

            return response()->json($compras);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las compras: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerDetalle(Request $request, $id)
    {
        try {
            $usuario = auth()->user();

            if (!$usuario) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $compra = Venta::obtenerCompraDetalle($id, $usuario->id);

            if (!$compra) {
                return response()->json([
                    'error' => 'Compra no encontrada'
                ], 404);
            }

            return response()->json($compra);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el detalle de la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function indexVentas()
    {
        try {
            $ventas = Venta::obtenerTodasLasVentas();

            return response()->json([
                'success' => true,
                'data' => $ventas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ventas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showVenta($id)
    {
        try {
            $venta = Venta::obtenerVentaDetalle($id);

            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $venta
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle de venta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Crear la venta
            $venta = Venta::create([
                'fecha_venta' => now(),
                'tipo' => $request->tipo ?? 'contado',
                'total' => $request->total,
                'estado' => $request->estado ?? 'completada',
                'cliente_id' => $request->usuario_id,
                'vendedor_id' => null,
                'cotizacion_id' => $request->cotizacion_id ?? null
            ]);

            // Crear los detalles de la venta
            if ($request->has('detalles') && is_array($request->detalles)) {
                foreach ($request->detalles as $detalle) {
                    DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad' => $detalle['cantidad'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario']
                    ]);

                    // Actualizar el stock del producto
                    $producto = Producto::find($detalle['producto_id']);
                    if ($producto) {
                        $producto->stock -= $detalle['cantidad'];
                        $producto->save();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta creada exitosamente',
                'data' => $venta
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la venta: ' . $e->getMessage()
            ], 500);
        }
    }
}
