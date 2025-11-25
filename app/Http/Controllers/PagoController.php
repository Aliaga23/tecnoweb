<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PagoController extends Controller
{
    private $tokenService;
    private $tokenSecret;
    private $apiUrl;

    public function __construct()
    {
        $this->tokenService = env('PAGOFACIL_TOKEN_SERVICE');
        $this->tokenSecret = env('PAGOFACIL_TOKEN_SECRET');
        $this->apiUrl = env('PAGOFACIL_API_URL');
    }

    public function generarQR(Request $request)
    {
        try {
            // Validar datos del request
            $request->validate([
                'cliente_id' => 'required|integer',
                'productos' => 'required|array',
                'total' => 'required|numeric',
                'cliente_nombre' => 'required|string',
                'cliente_ci' => 'required|string',
                'cliente_telefono' => 'required|string',
                'cliente_email' => 'required|email'
            ]);

            // Obtener datos del cliente y productos
            $clienteId = $request->cliente_id;
            $productos = $request->productos;
            $total = $request->total;

            // Generar número de pago único
            $paymentNumber = 'MP' . time() . $clienteId;

            // Preparar detalles de orden para PagoFácil
            $orderDetail = [];
            $serial = 1;
            
            foreach ($productos as $producto) {
                $orderDetail[] = [
                    'serial' => $serial++,
                    'product' => $producto['nombre'],
                    'quantity' => $producto['cantidad'],
                    'price' => (float)$producto['costo_unitario'],
                    'discount' => 0,
                    'total' => (float)($producto['cantidad'] * $producto['costo_unitario'])
                ];
            }

            // Preparar payload para PagoFácil
            $payload = [
                'paymentMethod' => 4, // QR
                'clientName' => $request->cliente_nombre,
                'documentType' => 1, // CI
                'documentId' => $request->cliente_ci,
                'phoneNumber' => $request->cliente_telefono,
                'email' => $request->cliente_email,
                'paymentNumber' => $paymentNumber,
                'amount' => (float)$total,
                'currency' => 2, // BOB (Bolivianos)
                'clientCode' => '11001', // Código de cliente (puedes cambiarlo)
                'callbackUrl' => url('/api/pago-callback'),
                'orderDetail' => $orderDetail
            ];

            // Headers para la petición
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->tokenService
            ];

            // Hacer petición a PagoFácil
            $response = Http::withHeaders($headers)
                ->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $pagoFacilResponse = $response->json();
                
                // Primero crear la venta pendiente
                $ventaId = DB::table('venta')->insertGetId([
                    'fecha_venta' => now(),
                    'tipo' => 'online',
                    'total' => $total,
                    'estado' => 'pendiente',
                    'cliente_id' => $clienteId,
                    'vendedor_id' => null, // Para ventas online
                    'cotizacion_id' => null
                ]);

                // Crear detalles de venta
                foreach ($productos as $producto) {
                    DB::table('detalle_venta')->insert([
                        'cantidad' => $producto['cantidad'],
                        'precio_unitario' => $producto['costo_unitario'],
                        'subtotal' => $producto['cantidad'] * $producto['costo_unitario'],
                        'venta_id' => $ventaId,
                        'producto_id' => $producto['producto_id']
                    ]);
                }

                // Crear registro de pago
                $pagoId = DB::table('pago')->insertGetId([
                    'monto' => $total,
                    'metodo' => 'QR_PagoFacil',
                    'fecha_pago' => now(),
                    'venta_id' => $ventaId
                ]);

                // Guardar datos adicionales del QR en cache para poder consultarlos
                cache([
                    'qr_' . $pagoId => [
                        'payment_number' => $paymentNumber,
                        'transaction_id' => $pagoFacilResponse['transactionId'] ?? null,
                        'qr_url' => $pagoFacilResponse['qrUrl'] ?? null,
                        'venta_id' => $ventaId,
                        'productos' => $productos
                    ]
                ], now()->addHours(2)); // Cache por 2 horas

                return response()->json([
                    'success' => true,
                    'pago_id' => $pagoId,
                    'qr_url' => $pagoFacilResponse['qrUrl'] ?? null,
                    'qr_image' => $pagoFacilResponse['qrImage'] ?? null,
                    'transaction_id' => $pagoFacilResponse['transactionId'] ?? null,
                    'payment_number' => $paymentNumber,
                    'total' => $total
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al generar QR: ' . $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            // Manejar callback de PagoFácil
            $data = $request->all();
            \Log::info('PagoFácil Callback:', $data);

            // Buscar el pago usando cache y payment_number
            $transactionId = $data['transactionId'] ?? null;
            $paymentNumber = $data['paymentNumber'] ?? null;
            $status = $data['status'] ?? null;

            if ($paymentNumber) {
                // Buscar en cache usando payment_number
                $cacheKeys = cache()->getStore()->getMemcached()->getAllKeys();
                $pagoData = null;
                $pagoId = null;
                
                foreach ($cacheKeys as $key) {
                    if (str_contains($key, 'qr_')) {
                        $data_cache = cache($key);
                        if ($data_cache && $data_cache['payment_number'] === $paymentNumber) {
                            $pagoData = $data_cache;
                            $pagoId = str_replace('qr_', '', $key);
                            break;
                        }
                    }
                }

                if ($pagoData && $pagoId) {
                    // Si el pago es exitoso, actualizar la venta a completado
                    if ($status === 'completed') {
                        DB::table('venta')
                            ->where('id', $pagoData['venta_id'])
                            ->update(['estado' => 'completado']);
                    } else {
                        // Si falla, marcar como fallido
                        DB::table('venta')
                            ->where('id', $pagoData['venta_id'])
                            ->update(['estado' => 'fallido']);
                    }
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Error en callback de PagoFácil: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarEstado($pagoId)
    {
        try {
            // Buscar el pago y su venta asociada
            $pago = DB::table('pago')
                ->join('venta', 'pago.venta_id', '=', 'venta.id')
                ->where('pago.id', $pagoId)
                ->select('pago.*', 'venta.estado as venta_estado')
                ->first();

            if (!$pago) {
                return response()->json(['error' => 'Pago no encontrado'], 404);
            }

            // Buscar datos adicionales en cache
            $qrData = cache('qr_' . $pagoId);

            return response()->json([
                'pago_id' => $pago->id,
                'estado' => $pago->venta_estado, // Estado de la venta
                'monto' => $pago->monto,
                'payment_number' => $qrData['payment_number'] ?? 'N/A',
                'fecha_pago' => $pago->fecha_pago
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}