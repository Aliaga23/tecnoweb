<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\DevolucionProveedor;
use App\Models\Proveedor;
use App\Models\Producto;

class DevolucionProveedorController extends Controller
{
    public function index()
    {
        return Inertia::render('DashboardDevolucionesProveedor');
    }

    public function obtenerDevoluciones()
    {
        try {
            $devoluciones = DevolucionProveedor::obtenerTodasLasDevoluciones();

            return response()->json([
                'success' => true,
                'data' => $devoluciones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener devoluciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerDetalle($id)
    {
        try {
            $devolucion = DevolucionProveedor::obtenerDetallePorId($id);

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

    public function obtenerProveedores()
    {
        try {
            $proveedores = Proveedor::obtenerTodos();

            return response()->json([
                'success' => true,
                'data' => $proveedores
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerProductos()
    {
        try {
            $productos = Producto::obtenerDisponibles();

            return response()->json([
                'success' => true,
                'data' => $productos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function crearDevolucion(Request $request)
    {
        try {
            $validated = $request->validate([
                'proveedor_id' => 'required|integer',
                'productos' => 'required|array',
                'productos.*.producto_id' => 'required|integer',
                'productos.*.cantidad' => 'required|integer|min:1',
                'observacion' => 'required|string'
            ]);

            $usuario = auth()->user();

            DevolucionProveedor::crearNuevaDevolucion(
                $validated,
                $validated['productos'],
                $usuario->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Devolución al proveedor registrada exitosamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar devolución: ' . $e->getMessage()
            ], 500);
        }
    }
}