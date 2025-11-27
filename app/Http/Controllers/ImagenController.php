<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagenController extends Controller
{
    public function upload(Request $request)
    {
        try {
            \Log::info('Upload imagen - Inicio', [
                'has_file' => $request->hasFile('imagen'),
                'all_files' => $request->allFiles()
            ]);

            $request->validate([
                'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            \Log::info('Upload imagen - Validación pasada');

            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                \Log::info('Upload imagen - Archivo recibido', [
                    'original_name' => $imagen->getClientOriginalName(),
                    'size' => $imagen->getSize()
                ]);

                $nombreArchivo = time() . '_' . Str::slug(pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $imagen->getClientOriginalExtension();
                
                \Log::info('Upload imagen - Nombre generado', ['nombre' => $nombreArchivo]);

                $path = public_path('images/productos');
                \Log::info('Upload imagen - Path destino', ['path' => $path]);

                // Guardar en public/images/productos
                $imagen->move($path, $nombreArchivo);
                
                \Log::info('Upload imagen - Archivo guardado exitosamente');

                // Retornar URL completa
                $url = 'https://www.tecnoweb.org.bo/inf513/grupo01sc/tecnoweb/public/images/productos/' . $nombreArchivo;

                return response()->json([
                    'success' => true,
                    'url' => $url,
                    'message' => 'Imagen subida exitosamente'
                ], 200);
            }

            \Log::warning('Upload imagen - No se recibió archivo');

            return response()->json([
                'success' => false,
                'message' => 'No se recibió ninguna imagen'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Upload imagen - Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen: ' . $e->getMessage()
            ], 500);
        }
    }
}
