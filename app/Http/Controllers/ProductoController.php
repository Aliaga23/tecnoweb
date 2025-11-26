<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Producto;
use App\Models\Categoria;

class ProductoController extends Controller
{
    public function index()
    {
        try {
            $productos = Producto::obtenerTodosConCategoria();

            return response()->json([
                'success' => true,
                'data' => $productos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $producto = Producto::obtenerPorIdConCategoria($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $producto
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        if (!Categoria::obtenerPorId($request->categoria_id)) {
            return response()->json([
                'success' => false,
                'errors' => ['categoria_id' => ['La categoría seleccionada no existe']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'stock_actual' => 'required|integer|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'imagen_url' => 'nullable|string|max:255',
            'categoria_id' => 'required|integer'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'stock_actual.required' => 'El stock es obligatorio',
            'stock_actual.integer' => 'El stock debe ser un número entero',
            'stock_actual.min' => 'El stock no puede ser negativo',
            'precio_unitario.required' => 'El precio es obligatorio',
            'precio_unitario.numeric' => 'El precio debe ser un número',
            'precio_unitario.min' => 'El precio no puede ser negativo',
            'categoria_id.required' => 'La categoría es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $producto = Producto::crearNuevo($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $producto
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::obtenerPorId($id);
        
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        if ($request->filled('categoria_id') && !Categoria::obtenerPorId($request->categoria_id)) {
            return response()->json([
                'success' => false,
                'errors' => ['categoria_id' => ['La categoría seleccionada no existe']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'stock_actual' => 'sometimes|required|integer|min:0',
            'precio_unitario' => 'sometimes|required|numeric|min:0',
            'imagen_url' => 'nullable|string|max:255',
            'categoria_id' => 'sometimes|required|integer'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'stock_actual.required' => 'El stock es obligatorio',
            'stock_actual.integer' => 'El stock debe ser un número entero',
            'stock_actual.min' => 'El stock no puede ser negativo',
            'precio_unitario.required' => 'El precio es obligatorio',
            'precio_unitario.numeric' => 'El precio debe ser un número',
            'precio_unitario.min' => 'El precio no puede ser negativo',
            'categoria_id.required' => 'La categoría es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $productoActualizado = Producto::actualizarPorId($id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => $productoActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $producto = Producto::obtenerPorId($id);
            
            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            Producto::eliminarPorId($id);

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
