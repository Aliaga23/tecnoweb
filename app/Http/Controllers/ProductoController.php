<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    /**
     * Listar todos los productos
     */
    public function index()
    {
        try {
            $productos = DB::select(
                'SELECT p.*, c.nombre as categoria_nombre 
                 FROM producto p 
                 LEFT JOIN categoria c ON p.categoria_id = c.id 
                 ORDER BY p.id'
            );

            // Formatear respuesta con categoria como objeto simple
            $productosFormateados = array_map(function($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'precio' => $p->precio_unitario,
                    'stock' => $p->stock_actual,
                    'imagen' => $p->imagen_url,
                    'categoria_id' => $p->categoria_id,
                    'categoria' => $p->categoria_nombre ? [
                        'nombre' => $p->categoria_nombre
                    ] : null
                ];
            }, $productos);

            return response()->json([
                'success' => true,
                'data' => $productosFormateados
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un producto por ID
     */
    public function show($id)
    {
        try {
            $productos = DB::select(
                'SELECT p.*, c.nombre as categoria_nombre 
                 FROM producto p 
                 LEFT JOIN categoria c ON p.categoria_id = c.id 
                 WHERE p.id = ? 
                 LIMIT 1',
                [$id]
            );

            if (empty($productos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $productos[0]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'stock_actual' => 'required|integer|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'imagen_url' => 'nullable|string|max:255',
            'categoria_id' => 'required|integer|exists:categoria,id'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'stock_actual.required' => 'El stock es obligatorio',
            'stock_actual.integer' => 'El stock debe ser un número entero',
            'stock_actual.min' => 'El stock no puede ser negativo',
            'precio_unitario.required' => 'El precio es obligatorio',
            'precio_unitario.numeric' => 'El precio debe ser un número',
            'precio_unitario.min' => 'El precio no puede ser negativo',
            'categoria_id.required' => 'La categoría es obligatoria',
            'categoria_id.exists' => 'La categoría seleccionada no existe'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $creado_en = date('Y-m-d H:i:s');
            
            DB::insert(
                'INSERT INTO producto (nombre, descripcion, stock_actual, precio_unitario, creado_en, imagen_url, categoria_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $request->nombre,
                    $request->descripcion,
                    $request->stock_actual,
                    $request->precio_unitario,
                    $creado_en,
                    $request->imagen_url,
                    $request->categoria_id
                ]
            );

            $producto = DB::select(
                'SELECT p.*, c.nombre as categoria_nombre 
                 FROM producto p 
                 LEFT JOIN categoria c ON p.categoria_id = c.id 
                 WHERE p.nombre = ? AND p.creado_en = ?
                 ORDER BY p.id DESC
                 LIMIT 1',
                [$request->nombre, $creado_en]
            )[0];

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

    /**
     * Actualizar producto
     */
    public function update(Request $request, $id)
    {
        $productos = DB::select('SELECT * FROM producto WHERE id = ? LIMIT 1', [$id]);
        
        if (empty($productos)) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'stock_actual' => 'sometimes|required|integer|min:0',
            'precio_unitario' => 'sometimes|required|numeric|min:0',
            'imagen_url' => 'nullable|string|max:255',
            'categoria_id' => 'sometimes|required|integer|exists:categoria,id'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'stock_actual.required' => 'El stock es obligatorio',
            'stock_actual.integer' => 'El stock debe ser un número entero',
            'stock_actual.min' => 'El stock no puede ser negativo',
            'precio_unitario.required' => 'El precio es obligatorio',
            'precio_unitario.numeric' => 'El precio debe ser un número',
            'precio_unitario.min' => 'El precio no puede ser negativo',
            'categoria_id.required' => 'La categoría es obligatoria',
            'categoria_id.exists' => 'La categoría seleccionada no existe'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $productoActual = $productos[0];
            
            $nombre = $request->input('nombre', $productoActual->nombre);
            $descripcion = $request->input('descripcion', $productoActual->descripcion);
            $stock_actual = $request->input('stock_actual', $productoActual->stock_actual);
            $precio_unitario = $request->input('precio_unitario', $productoActual->precio_unitario);
            $imagen_url = $request->input('imagen_url', $productoActual->imagen_url);
            $categoria_id = $request->input('categoria_id', $productoActual->categoria_id);

            DB::update(
                'UPDATE producto 
                 SET nombre = ?, descripcion = ?, stock_actual = ?, precio_unitario = ?, imagen_url = ?, categoria_id = ? 
                 WHERE id = ?',
                [$nombre, $descripcion, $stock_actual, $precio_unitario, $imagen_url, $categoria_id, $id]
            );

            $productoActualizado = DB::select(
                'SELECT p.*, c.nombre as categoria_nombre 
                 FROM producto p 
                 LEFT JOIN categoria c ON p.categoria_id = c.id 
                 WHERE p.id = ? 
                 LIMIT 1',
                [$id]
            )[0];

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

    /**
     * Eliminar producto
     */
    public function destroy($id)
    {
        try {
            $productos = DB::select('SELECT * FROM producto WHERE id = ? LIMIT 1', [$id]);
            
            if (empty($productos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            DB::delete('DELETE FROM producto WHERE id = ?', [$id]);

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
