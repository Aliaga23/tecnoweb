<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use Tymon\JWTAuth\Facades\JWTAuth;
use Dompdf\Dompdf;
use Dompdf\Options;

class CotizacionController extends Controller
{
    public function index()
    {
        try {
            $cotizaciones = Cotizacion::obtenerTodas();
            return response()->json($cotizaciones, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener cotizaciones: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $cotizacion = Cotizacion::obtenerPorId($id);

            if (!$cotizacion) {
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            return response()->json($cotizacion, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener cotización: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:usuario,id',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:producto,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.costo_unitario' => 'required|numeric|min:0'
        ]);

        try {
            $cotizacionId = Cotizacion::crearNueva($request->cliente_id, $request->detalles);

            return response()->json([
                'message' => 'Cotización creada exitosamente',
                'id' => $cotizacionId
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear cotización: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:producto,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.costo_unitario' => 'required|numeric|min:0'
        ]);

        try {
            if (!Cotizacion::existe($id)) {
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            Cotizacion::actualizarPorId($id, $request->detalles);

            return response()->json(['message' => 'Cotización actualizada exitosamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar cotización: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Cotizacion::existe($id)) {
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            Cotizacion::eliminarPorId($id);

            return response()->json(['message' => 'Cotización eliminada exitosamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar cotización: ' . $e->getMessage()], 500);
        }
    }

    public function obtenerDetalle($id)
    {
        try {
            $cotizacion = Cotizacion::obtenerDetalleCompleto($id);
            
            if (!$cotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $cotizacion
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle de cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function porUsuario($usuario_id)
    {
        try {
            $cotizaciones = Cotizacion::obtenerPorUsuario($usuario_id);
            return response()->json($cotizaciones, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener cotizaciones: ' . $e->getMessage()], 500);
        }
    }

    public function generarPDF($id)
    {
        try {
            $datos = Cotizacion::obtenerPorIdParaPDF($id);

            if (!$datos) {
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            $cotizacion = $datos['cotizacion'];
            $detalles = $datos['detalles'];

            // Crear el HTML para el PDF
            $html = $this->generarHtmlPDF($cotizacion, $detalles);

            // Configurar opciones de DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            // Crear instancia de DomPDF
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Retornar el PDF
            $filename = 'cotizacion_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF: ' . $e->getMessage()], 500);
        }
    }

    // Generar el HTML para el PDF
    private function generarHtmlPDF($cotizacion, $detalles)
    {
        $fecha_formateada = date('d/m/Y H:i', strtotime($cotizacion->fecha_cotizacion));
        $total_formateado = number_format((float)$cotizacion->total, 2, ',', '.');

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Cotización #' . $cotizacion->id . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    color: #333;
                    line-height: 1.4;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #0066cc;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .company-name {
                    font-size: 28px;
                    font-weight: bold;
                    color: #0066cc;
                    margin: 0;
                }
                .company-subtitle {
                    font-size: 14px;
                    color: #666;
                    margin: 5px 0;
                }
                .cotizacion-info {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                }
                .info-label {
                    font-weight: bold;
                    color: #555;
                }
                .info-value {
                    color: #333;
                }
                .table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .table th {
                    background-color: #0066cc;
                    color: white;
                    padding: 12px 8px;
                    text-align: left;
                    font-weight: bold;
                }
                .table td {
                    padding: 10px 8px;
                    border-bottom: 1px solid #ddd;
                }
                .table tbody tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                .total-row {
                    background-color: #e9ecef !important;
                    font-weight: bold;
                    font-size: 14px;
                }
                .total-amount {
                    color: #0066cc;
                    font-size: 16px;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    color: #666;
                    font-size: 10px;
                    border-top: 1px solid #ddd;
                    padding-top: 15px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="company-name">ELYTA</h1>
                <p class="company-subtitle">Tu mejor opción en repuestos para motos</p>
                <h2 style="color: #0066cc; margin: 10px 0;">COTIZACIÓN #' . $cotizacion->id . '</h2>
            </div>

            <div class="cotizacion-info">
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value">' . htmlspecialchars($cotizacion->cliente) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CI:</span>
                    <span class="info-value">' . htmlspecialchars($cotizacion->ci) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Correo:</span>
                    <span class="info-value">' . htmlspecialchars($cotizacion->correo) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value">' . htmlspecialchars($cotizacion->telefono) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    <span class="info-value">' . $fecha_formateada . '</span>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Producto</th>
                        <th style="width: 15%;" class="text-center">Cantidad</th>
                        <th style="width: 17.5%;" class="text-right">Precio Unit. (Bs.)</th>
                        <th style="width: 17.5%;" class="text-right">Subtotal (Bs.)</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($detalles as $detalle) {
            $costo_formateado = number_format((float)$detalle->costo_unitario, 2, ',', '.');
            $subtotal_formateado = number_format((float)$detalle->subtotal, 2, ',', '.');
            
            $html .= '
                    <tr>
                        <td>
                            <strong>' . htmlspecialchars($detalle->nombre) . '</strong><br>
                            <span style="color: #666; font-size: 11px;">' . htmlspecialchars($detalle->descripcion) . '</span>
                        </td>
                        <td class="text-center">' . $detalle->cantidad . '</td>
                        <td class="text-right">' . $costo_formateado . '</td>
                        <td class="text-right">' . $subtotal_formateado . '</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right total-amount"><strong>Bs. ' . $total_formateado . '</strong></td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 30px; padding: 15px; background-color: #f0f8ff; border-left: 4px solid #0066cc;">
                <p style="margin: 0; font-weight: bold; color: #0066cc;">Información importante:</p>
                <p style="margin: 5px 0 0 0; font-size: 11px;">
                    • Esta cotización tiene una validez de 15 días.<br>
                    • Los precios incluyen IVA.<br>
                    • Para realizar el pedido, contacte con nosotros.
                </p>
            </div>

            <div class="footer">
                <p>Generado automáticamente el ' . date('d/m/Y H:i') . '</p>
                <p>ELYTA - Tu mejor opción en repuestos para motos</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}
