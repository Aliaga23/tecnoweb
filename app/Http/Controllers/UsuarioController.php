<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        try {
            $usuarios = DB::select(
                'SELECT u.*, r.nombre as rol_nombre, r.id as rol_id
                 FROM usuario u 
                 LEFT JOIN rol r ON u.rol_id = r.id 
                 ORDER BY u.id'
            );

            // Formatear datos
            $usuariosFormatted = array_map(function($u) {
                return [
                    'id' => $u->id,
                    'nombre' => $u->nombre,
                    'apellido' => $u->apellido,
                    'ci' => $u->ci,
                    'telefono' => $u->telefono,
                    'correo' => $u->correo,
                    'rol_id' => $u->rol_id,
                    'rol' => [
                        'id' => $u->rol_id,
                        'nombre' => $u->rol_nombre
                    ]
                ];
            }, $usuarios);

            return response()->json([
                'success' => true,
                'data' => $usuariosFormatted
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario por ID
     */
    public function show($id)
    {
        try {
            $usuarios = DB::select(
                'SELECT u.*, r.nombre as rol_nombre 
                 FROM usuario u 
                 LEFT JOIN rol r ON u.rol_id = r.id 
                 WHERE u.id = ? 
                 LIMIT 1',
                [$id]
            );

            if (empty($usuarios)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $usuarios[0]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo usuario
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => 'required|string|max:50|unique:usuario,ci',
            'telefono' => 'required|string|max:50',
            'correo' => 'required|string|email|max:255|unique:usuario,correo',
            'password' => 'required|string|min:6',
            'rol_id' => 'required|integer|exists:rol,id'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'ci.required' => 'El CI es obligatorio',
            'ci.unique' => 'El CI ya está registrado',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'correo.unique' => 'El correo ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'rol_id.required' => 'El rol es obligatorio',
            'rol_id.exists' => 'El rol seleccionado no existe'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $passwordHash = Hash::make($request->password);
            
            DB::insert(
                'INSERT INTO usuario (nombre, apellido, ci, telefono, correo, password, rol_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $request->nombre,
                    $request->apellido,
                    $request->ci,
                    $request->telefono,
                    $request->correo,
                    $passwordHash,
                    $request->rol_id
                ]
            );

            $usuario = DB::select(
                'SELECT u.*, r.nombre as rol_nombre 
                 FROM usuario u 
                 LEFT JOIN rol r ON u.rol_id = r.id 
                 WHERE u.correo = ? 
                 LIMIT 1',
                [$request->correo]
            )[0];

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

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        // Verificar si el usuario existe
        $usuarios = DB::select('SELECT * FROM usuario WHERE id = ? LIMIT 1', [$id]);
        
        if (empty($usuarios)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'apellido' => 'sometimes|required|string|max:255',
            'ci' => 'sometimes|required|string|max:50|unique:usuario,ci,' . $id,
            'telefono' => 'sometimes|required|string|max:50',
            'correo' => 'sometimes|required|string|email|max:255|unique:usuario,correo,' . $id,
            'password' => 'sometimes|nullable|string|min:6',
            'rol_id' => 'sometimes|required|integer|exists:rol,id'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'ci.required' => 'El CI es obligatorio',
            'ci.unique' => 'El CI ya está registrado',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'correo.unique' => 'El correo ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'rol_id.required' => 'El rol es obligatorio',
            'rol_id.exists' => 'El rol seleccionado no existe'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $usuarioActual = $usuarios[0];
            
            $nombre = $request->input('nombre', $usuarioActual->nombre);
            $apellido = $request->input('apellido', $usuarioActual->apellido);
            $ci = $request->input('ci', $usuarioActual->ci);
            $telefono = $request->input('telefono', $usuarioActual->telefono);
            $correo = $request->input('correo', $usuarioActual->correo);
            $rol_id = $request->input('rol_id', $usuarioActual->rol_id);
            
            // Si hay password nuevo, lo hasheamos, sino mantenemos el actual
            $password = $request->filled('password') 
                ? Hash::make($request->password) 
                : $usuarioActual->password;

            DB::update(
                'UPDATE usuario 
                 SET nombre = ?, apellido = ?, ci = ?, telefono = ?, correo = ?, password = ?, rol_id = ? 
                 WHERE id = ?',
                [$nombre, $apellido, $ci, $telefono, $correo, $password, $rol_id, $id]
            );

            $usuarioActualizado = DB::select(
                'SELECT u.*, r.nombre as rol_nombre 
                 FROM usuario u 
                 LEFT JOIN rol r ON u.rol_id = r.id 
                 WHERE u.id = ? 
                 LIMIT 1',
                [$id]
            )[0];

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

    /**
     * Eliminar usuario
     */
    public function destroy($id)
    {
        try {
            $usuarios = DB::select('SELECT * FROM usuario WHERE id = ? LIMIT 1', [$id]);
            
            if (empty($usuarios)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            DB::delete('DELETE FROM usuario WHERE id = ?', [$id]);

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
