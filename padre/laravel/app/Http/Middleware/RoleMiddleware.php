<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        try {
            // Verificar que el usuario está autenticado
            if (!isset($request->user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario tiene el rol necesario
            if (!isset($request->user->rol) || strtolower($request->user->rol) !== strtolower($role)) {
                Log::warning("Acceso denegado por rol. Usuario: {$request->user->email}, Rol actual: {$request->user->rol}, Rol requerido: {$role}");
                
                return response()->json([
                    'status' => false,
                    'message' => 'No tiene permisos para acceder a este recurso. Se requiere rol: ' . $role
                ], 403);
            }
            
            return $next($request);
        } catch (\Exception $e) {
            Log::error('Error en RoleMiddleware: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error de verificación de rol: ' . $e->getMessage()
            ], 500);
        }
    }
}