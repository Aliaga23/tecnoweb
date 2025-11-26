<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Categoria;

class CategoriaController extends Controller
{
    public function index()
    {
        try {
            $categorias = Categoria::obtenerTodas();

            return response()->json([
                'success' => true,
                'data' => $categorias
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $categoria = Categoria::obtenerPorId($id);

            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $categoria
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva categoría
     */
    public function store(Request $request)
    {
        // Validación personalizada usando el modelo
        if (Categoria::existeNombreExcepto($request->nombre)) {
            return response()->json([
                'success' => false,
                'errors' => ['nombre' => ['Ya existe una categoría con ese nombre']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255'
        ], [
            'nombre.required' => 'El nombre es obligatorio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categoria = Categoria::crearNueva($request->nombre);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, $id)
    {
        $categoria = Categoria::obtenerPorId($id);
        
        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        // Validación personalizada usando el modelo
        if (Categoria::existeNombreExcepto($request->nombre, $id)) {
            return response()->json([
                'success' => false,
                'errors' => ['nombre' => ['Ya existe una categoría con ese nombre']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255'
        ], [
            'nombre.required' => 'El nombre es obligatorio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categoriaActualizada = Categoria::actualizarPorId($id, $request->nombre);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => $categoriaActualizada
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar categoría
     */
    public function destroy($id)
    {
        try {
            $categoria = Categoria::obtenerPorId($id);
            
            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            // Verificar si hay productos asociados
            if (Categoria::tieneProductosAsociados($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene productos asociados'
                ], 400);
            }

            Categoria::eliminarPorId($id);

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
