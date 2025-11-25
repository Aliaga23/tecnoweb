<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Registro de nuevo usuario
     */
    public function register(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => 'required|string|max:50|unique:usuario,ci',
            'telefono' => 'required|string|max:50',
            'correo' => 'required|string|email|max:255|unique:usuario,correo',
            'password' => 'required|string|min:6'
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
            'password.min' => 'La contraseña debe tener al menos 6 caracteres'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $passwordHash = Hash::make($request->password);
            
            // Insertar usuario con SQL crudo
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
                    3
                ]
            );

            // Obtener el usuario recién creado
            $usuario = DB::select(
                'SELECT * FROM usuario WHERE correo = ? LIMIT 1',
                [$request->correo]
            )[0];
            
            // Obtener el rol
            $rol = DB::select(
                'SELECT * FROM rol WHERE id = ? LIMIT 1',
                [$usuario->rol_id]
            )[0] ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'ci' => $usuario->ci,
                    'telefono' => $usuario->telefono,
                    'correo' => $usuario->correo,
                    'rol' => $rol->nombre ?? null
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'password' => 'required|string'
        ], [
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'password.required' => 'La contraseña es obligatoria'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar usuario por correo con SQL crudo
            $usuarios = DB::select(
                'SELECT * FROM usuario WHERE correo = ? LIMIT 1',
                [$request->correo]
            );

            if (empty($usuarios)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            $usuario = $usuarios[0];

            // Verificar contraseña
            if (!Hash::check($request->password, $usuario->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Crear instancia del modelo Usuario para JWT
            $usuarioModel = \App\Models\Usuario::find($usuario->id);
            
            // Generar token JWT
            $token = JWTAuth::fromUser($usuarioModel);

            // Obtener el rol con SQL crudo
            $roles = DB::select(
                'SELECT * FROM rol WHERE id = ? LIMIT 1',
                [$usuario->rol_id]
            );
            $rol = $roles[0] ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'apellido' => $usuario->apellido,
                        'ci' => $usuario->ci,
                        'telefono' => $usuario->telefono,
                        'correo' => $usuario->correo,
                        'rol' => $rol->nombre ?? null
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout de usuario
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function me()
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener datos completos con SQL crudo
            $usuarios = DB::select(
                'SELECT * FROM usuario WHERE id = ? LIMIT 1',
                [$usuario->id]
            );
            $usuarioData = $usuarios[0];

            $roles = DB::select(
                'SELECT * FROM rol WHERE id = ? LIMIT 1',
                [$usuarioData->rol_id]
            );
            $rol = $roles[0] ?? null;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $usuarioData->id,
                    'nombre' => $usuarioData->nombre,
                    'apellido' => $usuarioData->apellido,
                    'ci' => $usuarioData->ci,
                    'telefono' => $usuarioData->telefono,
                    'correo' => $usuarioData->correo,
                    'rol' => $rol->nombre ?? null
                ]
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
     * Refrescar token
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
