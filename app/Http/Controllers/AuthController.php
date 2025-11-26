<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Rol;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => 'required|string|max:50',
            'telefono' => 'required|string|max:50',
            'correo' => 'required|string|email|max:255',
            'password' => 'required|string|min:6'
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'ci.required' => 'El CI es obligatorio',
            'telefono.required' => 'El teléfono es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

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

        try {
            $usuario = Usuario::crearUsuarioRegistro($request->all());
            $rol = Rol::obtenerPorId($usuario->rol_id);

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

    public function login(Request $request)
    {
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
            $usuario = Usuario::obtenerPorCorreo($request->correo);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            if (!Usuario::verificarPassword($request->password, $usuario->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            $usuarioModel = Usuario::find($usuario->id);
            $token = JWTAuth::fromUser($usuarioModel);
            $rol = Rol::obtenerPorId($usuario->rol_id);

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

            $usuarioData = Usuario::obtenerPorId($usuario->id);
            $rol = Rol::obtenerPorId($usuarioData->rol_id);

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
