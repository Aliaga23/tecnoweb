<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    /**
     * Listar todos los productos del catálogo (público)
     */
    public function index()
    {
        try {
            $productos = DB::select(
                'SELECT p.id, p.nombre, p.descripcion, p.stock_actual, p.precio_unitario, p.imagen_url, 
                        c.nombre as categoria_nombre, c.id as categoria_id
                 FROM producto p 
                 LEFT JOIN categoria c ON p.categoria_id = c.id 
                 WHERE p.stock_actual > 0
                 ORDER BY p.id'
            );

            return response()->json([
                'success' => true,
                'data' => $productos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener catálogo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un producto específico del catálogo (público)
     */
    public function show($id)
    {
        try {
            $productos = DB::select(
                'SELECT p.id, p.nombre, p.descripcion, p.stock_actual, p.precio_unitario, p.imagen_url, 
                        c.nombre as categoria_nombre, c.id as categoria_id
                 FROM producto p 
                 LEFT JOIN categoria c ON p.categoria_id = c.id 
                 WHERE p.id = ? AND p.stock_actual > 0
                 LIMIT 1',
                [$id]
            );

            if (empty($productos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado o sin stock disponible'
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
     * Listar productos por categoría (público)
     */
    public function porCategoria($categoria_id)
    {
        try {
            $productos = DB::select(
                'SELECT p.id, p.nombre, p.descripcion, p.stock_actual, p.precio_unitario, p.imagen_url, 
                        c.nombre as categoria_nombre, c.id as categoria_id
                 FROM producto p 
                 LEFT JOIN categoria c ON p.categoria_id = c.id 
                 WHERE p.categoria_id = ? AND p.stock_actual > 0
                 ORDER BY p.nombre',
                [$categoria_id]
            );

            return response()->json([
                'success' => true,
                'data' => $productos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos de la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las categorías con productos disponibles (público)
     */
    public function categorias()
    {
        try {
            $categorias = DB::select(
                'SELECT DISTINCT c.id, c.nombre, 
                        (SELECT COUNT(*) FROM producto p WHERE p.categoria_id = c.id AND p.stock_actual > 0) as total_productos
                 FROM categoria c
                 WHERE EXISTS (SELECT 1 FROM producto p WHERE p.categoria_id = c.id AND p.stock_actual > 0)
                 ORDER BY c.nombre'
            );

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
}
