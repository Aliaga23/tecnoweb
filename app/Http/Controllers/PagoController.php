<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Pago;

class PagoController extends Controller
{
    private $tokenService;
    private $tokenSecret;
    private $apiUrl;
    private $loginUrl;

    public function __construct()
    {
        $this->tokenService = env('PAGOFACIL_TOKEN_SERVICE');
        $this->tokenSecret = env('PAGOFACIL_TOKEN_SECRET');
        $this->apiUrl = 'https://masterqr.pagofacil.com.bo/api/services/v2';
        $this->loginUrl = $this->apiUrl . '/login';
    }

    public function index()
    {
        try {
            $pagos = Pago::obtenerTodos();

            return response()->json([
                'success' => true,
                'data' => $pagos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $pago = Pago::obtenerPorId($id);

            if (!$pago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $pago
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getAccessToken()
    {
        $accessToken = Cache::get('pagofacil_access_token');
        
        if ($accessToken) {
            return $accessToken;
        }

        try {
            $response = Http::withHeaders([
                'tcTokenService' => $this->tokenService,
                'tcTokenSecret' => $this->tokenSecret
            ])->post($this->loginUrl);

            \Log::info('PagoFácil Login Request:', [
                'url' => $this->loginUrl,
                'token_service_length' => strlen($this->tokenService),
                'token_secret_length' => strlen($this->tokenSecret)
            ]);

            \Log::info('PagoFácil Login Response:', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['error']) && $data['error'] == 0 && isset($data['values']['accessToken'])) {
                    $accessToken = $data['values']['accessToken'];
                    $expiresInMinutes = $data['values']['expiresInMinutes'] ?? 720;
                    
                    Cache::put('pagofacil_access_token', $accessToken, now()->addMinutes($expiresInMinutes - 5));
                    
                    return $accessToken;
                }
            }

            throw new \Exception('Error al obtener access token: ' . $response->body());

        } catch (\Exception $e) {
            \Log::error('Error en login PagoFácil: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getPaymentMethodId($accessToken)
    {
        $paymentMethodId = Cache::get('pagofacil_payment_method_id');
        
        if ($paymentMethodId) {
            return $paymentMethodId;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->post($this->apiUrl . '/list-enabled-services');

            \Log::info('PagoFácil List Services Response:', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['values']) && is_array($data['values']) && count($data['values']) > 0) {
                    $paymentMethodId = $data['values'][0]['paymentMethodId'];
                    
                    Cache::put('pagofacil_payment_method_id', $paymentMethodId, now()->addHours(24));
                    
                    return $paymentMethodId;
                }
            }

            throw new \Exception('No se encontraron métodos de pago habilitados');

        } catch (\Exception $e) {
            \Log::error('Error al listar servicios PagoFácil: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generarQR(Request $request)
    {
        try {
            $request->validate([
                'cliente_id' => 'required|integer',
                'productos' => 'required|array',
                'total' => 'required|numeric',
                'cliente_nombre' => 'required|string',
                'cliente_ci' => 'required|string',
                'cliente_telefono' => 'required|string',
                'cliente_email' => 'required|email'
            ]);

            $accessToken = $this->getAccessToken();
            $paymentMethodId = $this->getPaymentMethodId($accessToken);

            $clienteId = $request->cliente_id;
            $productos = $request->productos;
            $total = $request->total;
            $paymentNumber = 'MP' . time() . $clienteId;

            $orderDetail = [];
            
            foreach ($productos as $producto) {
                $orderDetail[] = [
                    'serial' => count($orderDetail) + 1,
                    'product' => $producto['nombre'],
                    'quantity' => (int)$producto['cantidad'],
                    'price' => (float)$producto['costo_unitario'],
                    'discount' => 0,
                    'total' => (float)($producto['cantidad'] * $producto['costo_unitario'])
                ];
            }

            $payload = [
                'paymentMethod' => $paymentMethodId,
                'clientName' => $request->cliente_nombre,
                'documentType' => 1,
                'documentId' => preg_replace('/[^0-9]/', '', $request->cliente_ci),
                'phoneNumber' => preg_replace('/[^0-9]/', '', $request->cliente_telefono),
                'email' => $request->cliente_email,
                'paymentNumber' => $paymentNumber,
                'amount' => 0.1,
                'currency' => 2,
                'clientCode' => $this->tokenSecret,
                'callbackUrl' => url('/api/pago-callback'),
                'orderDetail' => $orderDetail
            ];

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($this->apiUrl . '/generate-qr', $payload);

            \Log::info('PagoFácil Generate QR Request:', [
                'url' => $this->apiUrl . '/generate-qr',
                'payload' => $payload,
                'payment_method_id' => $paymentMethodId
            ]);

            \Log::info('PagoFácil Generate QR Response:', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $pagoFacilResponse = $response->json();
                
                if (isset($pagoFacilResponse['error']) && $pagoFacilResponse['error'] != 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error de PagoFácil: ' . ($pagoFacilResponse['message'] ?? 'Error desconocido'),
                        'details' => $pagoFacilResponse
                    ], 400);
                }
                
                // Obtener cotizacion_id si existe
                $cotizacionId = $request->input('cotizacion_id');
                
                $resultado = Pago::crearVentaConDetalles($clienteId, $productos, $total, $cotizacionId);
                $ventaId = $resultado['venta_id'];
                $pagoId = $resultado['pago_id'];

                $qrImage = $pagoFacilResponse['values']['qrBase64'] ?? null;
                $transactionId = $pagoFacilResponse['values']['transactionId'] ?? null;

                if ($qrImage && !str_starts_with($qrImage, 'data:image')) {
                    $qrImage = 'data:image/png;base64,' . $qrImage;
                }
                
                Cache::put('qr_' . $pagoId, [
                    'payment_number' => $paymentNumber,
                    'transaction_id' => $transactionId,
                    'qr_image' => $qrImage,
                    'venta_id' => $ventaId,
                    'productos' => $productos
                ], now()->addHours(2));
                
                Cache::put('payment_' . $paymentNumber, [
                    'pago_id' => $pagoId,
                    'venta_id' => $ventaId
                ], now()->addHours(2));

                return response()->json([
                    'success' => true,
                    'pago_id' => $pagoId,
                    'qr_image' => $qrImage,
                    'transaction_id' => $transactionId,
                    'payment_number' => $paymentNumber,
                    'total' => $total
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al generar QR: ' . $response->body(),
                    'status' => $response->status()
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error en generarQR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $data = $request->all();
            \Log::info('PagoFácil Callback:', $data);

            $pedidoId = $data['PedidoID'] ?? null;
            $estado = $data['Estado'] ?? null;

            if ($pedidoId) {
                $paymentData = Cache::get('payment_' . $pedidoId);
                
                \Log::info('Buscando pago:', ['pedido_id' => $pedidoId, 'encontrado' => $paymentData ? 'si' : 'no']);

                if ($paymentData) {
                    if ($estado == 2) {
                        Pago::actualizarEstadoVenta($paymentData['venta_id'], 'pagada');
                        \Log::info('Venta completada:', ['venta_id' => $paymentData['venta_id'], 'pedido_id' => $pedidoId]);
                    } else {
                        Pago::actualizarEstadoVenta($paymentData['venta_id'], 'pendiente');
                        \Log::warning('Pago fallido:', ['venta_id' => $paymentData['venta_id'], 'estado' => $estado]);
                    }
                } else {
                    \Log::warning('No se encontró información del pago:', ['pedido_id' => $pedidoId]);
                }
            }

            return response()->json([
                'error' => 0,
                'status' => 1,
                'message' => 'Notificación recibida correctamente',
                'values' => true
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en callback de PagoFácil: ' . $e->getMessage());
            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Error procesando callback',
                'values' => false
            ], 500);
        }
    }

    public function consultarEstado($pagoId)
    {
        try {
            $pago = Pago::consultarEstadoPago($pagoId);

            if (!$pago) {
                return response()->json(['error' => 'Pago no encontrado'], 404);
            }

            $qrData = Cache::get('qr_' . $pagoId);

            return response()->json([
                'pago_id' => $pago->id,
                'estado' => $pago->venta_estado,
                'monto' => $pago->monto,
                'payment_number' => $qrData['payment_number'] ?? 'N/A',
                'transaction_id' => $qrData['transaction_id'] ?? 'N/A',
                'fecha_pago' => $pago->fecha_pago
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}