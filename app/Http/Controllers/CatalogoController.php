<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;

class CatalogoController extends Controller
{
    public function index()
    {
        try {
            $productos = Producto::obtenerCatalogoCompleto();

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

    public function show($id)
    {
        try {
            $producto = Producto::obtenerProductoCatalogo($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado o sin stock disponible'
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

    public function porCategoria($categoria_id)
    {
        try {
            $productos = Producto::obtenerPorCategoria($categoria_id);

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

    public function categorias()
    {
        try {
            $categorias = Producto::obtenerCategoriasConProductos();

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
