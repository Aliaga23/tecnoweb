<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            $token = JWTAuth::getToken();
            $payload = JWTAuth::getPayload($token);
            
            // Debug: Ver todo el contenido del JWT
            error_log("JWT Payload completo: " . json_encode($payload->toArray()));
            error_log("Roles permitidos: " . json_encode($roles));
            
            $rolUsuario = $payload->get('rol_id');
            error_log("Rol del usuario: " . $rolUsuario);
            
            if (!$rolUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token sin información de rol'
                ], 401);
            }

            if (!in_array($rolUsuario, $roles)) {
                error_log("ACCESO DENEGADO - Rol usuario: $rolUsuario, Roles permitidos: " . json_encode($roles));
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a este recurso'
                ], 403);
            }

            error_log("ACCESO PERMITIDO - Rol usuario: $rolUsuario");
            return $next($request);

        } catch (\Exception $e) {
            error_log("ERROR en CheckRole: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        }
    }
}
