<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Proveedor;

class ProveedorController extends Controller
{
    public function index()
    {
        try {
            $proveedores = Proveedor::obtenerTodos();

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

    public function show($id)
    {
        try {
            $proveedor = Proveedor::obtenerPorId($id);

            if (!$proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $proveedor
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:50',
            'correo' => 'required|string|email|max:255',
            'direccion' => 'required|string|max:255'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'direccion.required' => 'La dirección es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (Proveedor::existeCorreo($request->correo)) {
            return response()->json([
                'success' => false,
                'errors' => ['correo' => ['El correo ya está registrado']]
            ], 422);
        }

        try {
            $proveedor = Proveedor::crearNuevo($request->all());

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
    public function update(Request $request, $id)
    {
        $proveedorActual = Proveedor::obtenerPorId($id);
        
        if (!$proveedorActual) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'telefono' => 'sometimes|required|string|max:50',
            'correo' => 'sometimes|required|string|email|max:255',
            'direccion' => 'sometimes|required|string|max:255'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'direccion.required' => 'La dirección es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('correo') && Proveedor::existeCorreo($request->correo, $id)) {
            return response()->json([
                'success' => false,
                'errors' => ['correo' => ['El correo ya está registrado']]
            ], 422);
        }

        try {
            $datos = [
                'nombre' => $request->input('nombre', $proveedorActual->nombre),
                'telefono' => $request->input('telefono', $proveedorActual->telefono),
                'correo' => $request->input('correo', $proveedorActual->correo),
                'direccion' => $request->input('direccion', $proveedorActual->direccion)
            ];

            $proveedorActualizado = Proveedor::actualizarPorId($id, $datos);

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

    public function destroy($id)
    {
        try {
            $proveedor = Proveedor::obtenerPorId($id);
            
            if (!$proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            Proveedor::eliminarPorId($id);

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
