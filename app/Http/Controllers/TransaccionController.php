<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaccion;

class TransaccionController extends Controller
{
    public function index()
    {
        try {
            $transacciones = Transaccion::obtenerTodasLasTransacciones();

            return response()->json([
                'success' => true,
                'data' => $transacciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener transacciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function detalleVenta($id)
    {
        try {
            $venta = Transaccion::obtenerDetalleVenta($id);

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

    public function resumen()
    {
        try {
            $resumen = Transaccion::obtenerResumen();

            return response()->json([
                'success' => true,
                'data' => $resumen
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function buscarClientePorCi($ci)
    {
        try {
            $cliente = \App\Models\Usuario::buscarPorCi($ci);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $cliente
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function crearVentaCredito(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:usuario,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:producto,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
            'monto_adelanto' => 'nullable|numeric|min:0',
            'metodo_adelanto' => 'required_with:monto_adelanto|string|in:efectivo,qr,tarjeta'
        ]);

        try {
            $venta = Transaccion::crearVentaCredito(
                $request->cliente_id,
                $request->productos,
                $request->monto_adelanto ?? 0,
                $request->metodo_adelanto,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Venta al crédito creada exitosamente',
                'data' => $venta
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear venta al crédito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function registrarPagoCredito(Request $request, $id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0',
            'metodo' => 'required|string|in:efectivo,qr,tarjeta'
        ]);

        try {
            // Verificar que sea el cliente de la venta o un admin/vendedor
            $venta = DB::table('venta')->where('id', $id)->first();
            
            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }

            $usuario = auth()->user();
            $es_cliente = $venta->cliente_id == $usuario->id;
            $es_admin = in_array($usuario->rol_id, [1, 2]); // Propietario o Vendedor

            if (!$es_cliente && !$es_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para registrar pagos de esta venta'
                ], 403);
            }

            $resultado = Transaccion::registrarPagoCredito(
                $id,
                $request->monto,
                $request->metodo
            );

            return response()->json([
                'success' => true,
                'message' => $resultado['mensaje'],
                'data' => $resultado['pago']
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerVentasCredito()
    {
        try {
            $ventas = Transaccion::obtenerVentasCredito();

            return response()->json([
                'success' => true,
                'data' => $ventas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ventas al crédito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function misVentasCredito()
    {
        try {
            $cliente_id = auth()->id();
            $ventas = Transaccion::obtenerVentasCreditoPorCliente($cliente_id);

            return response()->json([
                'success' => true,
                'data' => $ventas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mis ventas al crédito: ' . $e->getMessage()
            ], 500);
        }
    }
}