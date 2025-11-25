<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProveedorController extends Controller
{
    /**
     * Listar todos los proveedores
     */
    public function index()
    {
        try {
            $proveedores = DB::select('SELECT * FROM proveedor ORDER BY id');

            return response()->json([
                'success' => true,
                'data' => $proveedores
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un proveedor por ID
     */
    public function show($id)
    {
        try {
            $proveedores = DB::select('SELECT * FROM proveedor WHERE id = ? LIMIT 1', [$id]);

            if (empty($proveedores)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $proveedores[0]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo proveedor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:50',
            'correo' => 'required|string|email|max:255|unique:proveedor,correo',
            'direccion' => 'required|string|max:255'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'correo.unique' => 'El correo ya está registrado',
            'direccion.required' => 'La dirección es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::insert(
                'INSERT INTO proveedor (nombre, telefono, correo, direccion) VALUES (?, ?, ?, ?)',
                [$request->nombre, $request->telefono, $request->correo, $request->direccion]
            );

            $proveedor = DB::select(
                'SELECT * FROM proveedor WHERE correo = ? ORDER BY id DESC LIMIT 1',
                [$request->correo]
            )[0];

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => $proveedor
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar proveedor
     */
    public function update(Request $request, $id)
    {
        $proveedores = DB::select('SELECT * FROM proveedor WHERE id = ? LIMIT 1', [$id]);
        
        if (empty($proveedores)) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'telefono' => 'sometimes|required|string|max:50',
            'correo' => 'sometimes|required|string|email|max:255|unique:proveedor,correo,' . $id,
            'direccion' => 'sometimes|required|string|max:255'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'correo.unique' => 'El correo ya está registrado',
            'direccion.required' => 'La dirección es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proveedorActual = $proveedores[0];
            
            $nombre = $request->input('nombre', $proveedorActual->nombre);
            $telefono = $request->input('telefono', $proveedorActual->telefono);
            $correo = $request->input('correo', $proveedorActual->correo);
            $direccion = $request->input('direccion', $proveedorActual->direccion);

            DB::update(
                'UPDATE proveedor SET nombre = ?, telefono = ?, correo = ?, direccion = ? WHERE id = ?',
                [$nombre, $telefono, $correo, $direccion, $id]
            );

            $proveedorActualizado = DB::select('SELECT * FROM proveedor WHERE id = ? LIMIT 1', [$id])[0];

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $proveedorActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar proveedor
     */
    public function destroy($id)
    {
        try {
            $proveedores = DB::select('SELECT * FROM proveedor WHERE id = ? LIMIT 1', [$id]);
            
            if (empty($proveedores)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            DB::delete('DELETE FROM proveedor WHERE id = ?', [$id]);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
