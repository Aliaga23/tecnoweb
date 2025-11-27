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
            $request->validate([
                'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $nombreArchivo = time() . '_' . Str::slug(pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $imagen->getClientOriginalExtension();
                
                // Guardar en public/images/productos
                $imagen->move(public_path('images/productos'), $nombreArchivo);
                
                // Retornar URL relativa
                $url = '/images/productos/' . $nombreArchivo;

                return response()->json([
                    'success' => true,
                    'url' => $url,
                    'message' => 'Imagen subida exitosamente'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se recibió ninguna imagen'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen: ' . $e->getMessage()
            ], 500);
        }
    }
}
