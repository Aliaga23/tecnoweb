<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Devolucion;
use Inertia\Inertia;

class DevolucionController extends Controller
{
    public function index()
    {
        return Inertia::render('MisDevoluciones');
    }

    public function obtenerDevoluciones(Request $request)
    {
        try {
            $usuario = auth()->user();

            if (!$usuario) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $devoluciones = Devolucion::obtenerPorUsuario($usuario->id);

            return response()->json($devoluciones);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las devoluciones: ' . $e->getMessage()
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

            $devolucion = Devolucion::obtenerDetallePorIdYUsuario($id, $usuario->id);

            if (!$devolucion) {
                return response()->json([
                    'error' => 'Devolución no encontrada'
                ], 404);
            }

            return response()->json($devolucion);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el detalle: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerTodasDevoluciones()
    {
        try {
            $devoluciones = Devolucion::obtenerTodas();

            return response()->json([
                'success' => true,
                'data' => $devoluciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener devoluciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerDetalleDevolucion($id)
    {
        try {
            $devolucion = Devolucion::obtenerDetalleCompleto($id);

            if (!$devolucion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Devolución no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $devolucion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle: ' . $e->getMessage()
            ], 500);
        }
    }

    public function buscarVentasPorCarnet($ci)
    {
        try {
            $ventas = Devolucion::buscarVentasPorCarnet($ci);

            return response()->json([
                'success' => true,
                'data' => $ventas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar ventas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerDetalleVenta($ventaId)
    {
        try {
            $detalles = Devolucion::obtenerDetalleVenta($ventaId);

            return response()->json([
                'success' => true,
                'data' => $detalles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle: ' . $e->getMessage()
            ], 500);
        }
    }

    public function crearDevolucion(Request $request)
    {
        try {
            $validated = $request->validate([
                'venta_id' => 'required|integer',
                'productos' => 'required|array',
                'productos.*.producto_id' => 'required|integer',
                'productos.*.cantidad' => 'required|integer|min:1',
                'motivo' => 'required|string'
            ]);

            $devolucionId = Devolucion::crearNuevaDevolucion($validated);

            return response()->json([
                'success' => true,
                'message' => 'Devolución registrada exitosamente. Stock restaurado y venta actualizada.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar devolución: ' . $e->getMessage()
            ], 500);
        }
    }
}