<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Rol;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    public function index()
    {
        try {
            $usuarios = Usuario::obtenerTodosConRol();

            return response()->json([
                'success' => true,
                'data' => $usuarios
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $usuario = Usuario::obtenerPorIdConRol($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $usuario
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        if (Usuario::existeCi($request->ci)) {
            return response()->json([
                'success' => false,
                'errors' => ['ci' => ['El CI ya está registrado']]
            ], 422);
        }

        if (Usuario::existeCorreo($request->correo)) {
            return response()->json([
                'success' => false,
                'errors' => ['correo' => ['El correo ya está registrado']]
            ], 422);
        }

        if (!Rol::existe($request->rol_id)) {
            return response()->json([
                'success' => false,
                'errors' => ['rol_id' => ['El rol seleccionado no existe']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => 'required|string|max:50',
            'telefono' => 'required|string|max:50',
            'correo' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'rol_id' => 'required|integer'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'ci.required' => 'El CI es obligatorio',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'rol_id.required' => 'El rol es obligatorio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $usuario = Usuario::crearNuevo($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $usuario
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::obtenerPorId($id);
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        if ($request->filled('ci') && Usuario::existeCi($request->ci, $id)) {
            return response()->json([
                'success' => false,
                'errors' => ['ci' => ['El CI ya está registrado']]
            ], 422);
        }

        if ($request->filled('correo') && Usuario::existeCorreo($request->correo, $id)) {
            return response()->json([
                'success' => false,
                'errors' => ['correo' => ['El correo ya está registrado']]
            ], 422);
        }

        if ($request->filled('rol_id') && !Rol::existe($request->rol_id)) {
            return response()->json([
                'success' => false,
                'errors' => ['rol_id' => ['El rol seleccionado no existe']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'apellido' => 'sometimes|required|string|max:255',
            'ci' => 'sometimes|required|string|max:50',
            'telefono' => 'sometimes|required|string|max:50',
            'correo' => 'sometimes|required|string|email|max:255',
            'password' => 'sometimes|nullable|string|min:6',
            'rol_id' => 'sometimes|required|integer'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'ci.required' => 'El CI es obligatorio',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'rol_id.required' => 'El rol es obligatorio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $usuarioActualizado = Usuario::actualizarPorId($id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $usuarioActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $usuario = Usuario::obtenerPorId($id);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            Usuario::eliminarPorId($id);

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
