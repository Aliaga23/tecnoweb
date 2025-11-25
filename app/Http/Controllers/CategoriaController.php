<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    /**
     * Listar todas las categorías
     */
    public function index()
    {
        try {
            $categorias = DB::select('SELECT * FROM categoria ORDER BY id');

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

    /**
     * Obtener una categoría por ID
     */
    public function show($id)
    {
        try {
            $categorias = DB::select('SELECT * FROM categoria WHERE id = ? LIMIT 1', [$id]);

            if (empty($categorias)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $categorias[0]
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
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:categoria,nombre'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.unique' => 'Ya existe una categoría con ese nombre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::insert('INSERT INTO categoria (nombre) VALUES (?)', [$request->nombre]);

            $categoria = DB::select(
                'SELECT * FROM categoria WHERE nombre = ? ORDER BY id DESC LIMIT 1',
                [$request->nombre]
            )[0];

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
        $categorias = DB::select('SELECT * FROM categoria WHERE id = ? LIMIT 1', [$id]);
        
        if (empty($categorias)) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:categoria,nombre,' . $id
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.unique' => 'Ya existe una categoría con ese nombre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::update('UPDATE categoria SET nombre = ? WHERE id = ?', [$request->nombre, $id]);

            $categoriaActualizada = DB::select('SELECT * FROM categoria WHERE id = ? LIMIT 1', [$id])[0];

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
            $categorias = DB::select('SELECT * FROM categoria WHERE id = ? LIMIT 1', [$id]);
            
            if (empty($categorias)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            // Verificar si hay productos asociados
            $productos = DB::select('SELECT COUNT(*) as total FROM producto WHERE categoria_id = ?', [$id]);
            
            if ($productos[0]->total > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene productos asociados'
                ], 400);
            }

            DB::delete('DELETE FROM categoria WHERE id = ?', [$id]);

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
