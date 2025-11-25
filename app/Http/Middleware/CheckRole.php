<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            
            \Log::info('CheckRole - Usuario autenticado:', [
                'id' => $usuario->id ?? null,
                'rol_id' => $usuario->rol_id ?? null,
                'roles_permitidos' => $roles
            ]);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            if (!in_array($usuario->rol_id, $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a este recurso',
                    'debug' => [
                        'tu_rol' => $usuario->rol_id,
                        'roles_permitidos' => $roles
                    ]
                ], 403);
            }

            return $next($request);

        } catch (\Exception $e) {
            \Log::error('CheckRole - Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
