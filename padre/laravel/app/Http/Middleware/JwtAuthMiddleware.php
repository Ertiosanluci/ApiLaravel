<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JwtAuthMiddleware
{
    // Lista negra temporal en memoria (se pierde al reiniciar servidor)
    private static $blacklist = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role = null)
    {
        try {
            Log::debug('JwtAuthMiddleware: Iniciando verificación de token');
            
            // Primero intenta obtener el token del header
            $authHeader = $request->header('Authorization');
            $token = null;
            
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
                Log::debug('Token obtenido del header: ' . substr($token, 0, 10) . '...');
            }
            
            // Si no hay token en el header, intenta obtenerlo de los parámetros de la URL
            if (!$token) {
                // Verifica primero directamente el parámetro token para asegurar la captura
                $token = $request->query('token');
                if ($token) {
                    Log::debug('Token obtenido de query param directo: ' . substr($token, 0, 10) . '...');
                }
                // Si aún no hay token, intenta con el método has + query
                elseif ($request->has('token')) {
                    $token = $request->query('token');
                    Log::debug('Token obtenido con has+query: ' . substr($token, 0, 10) . '...');
                }
                // Último intento: revisar directamente los parámetros GET
                elseif (isset($_GET['token'])) {
                    $token = $_GET['token'];
                    Log::debug('Token obtenido de $_GET: ' . substr($token, 0, 10) . '...');
                }
            }
            
            if (!$token) {
                Log::warning('Token no proporcionado');
                return response()->json([
                    'status' => false,
                    'message' => 'Token no proporcionado'
                ], 401);
            }
            
            // Verificar si el token está en la lista negra (usando el array estático en lugar de caché)
            $tokenId = md5($token);
            if (self::isInBlacklist($tokenId)) {
                Log::warning('Token en lista negra');
                return response()->json([
                    'status' => false,
                    'message' => 'Token inválido o revocado'
                ], 401);
            }
            
            // Decodificar el token
            $payload = $this->decodeToken($token);
            
            if (!$payload) {
                Log::warning('No se pudo decodificar el token: ' . $token);
                return response()->json([
                    'status' => false,
                    'message' => 'No se pudo decodificar el token'
                ], 401);
            }
            
            Log::debug('Payload decodificado: ' . json_encode($payload));
            
            // Verificar si el token ha expirado
            if (!isset($payload['exp']) || $payload['exp'] < time()) {
                Log::warning('Token expirado');
                return response()->json([
                    'status' => false,
                    'message' => 'Token expirado'
                ], 401);
            }
            
            // Buscar el usuario
            if (!isset($payload['sub'])) {
                Log::warning('Token no contiene ID de usuario');
                return response()->json([
                    'status' => false,
                    'message' => 'Token no contiene ID de usuario'
                ], 401);
            }
            
            try {
                $user = User::find($payload['sub']);
            } catch (\Exception $e) {
                Log::error('Error al buscar usuario en la base de datos: ' . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'Error en la estructura de la base de datos: ' . $e->getMessage()
                ], 500);
            }
            
            if (!$user) {
                Log::warning('Usuario no encontrado: ' . $payload['sub']);
                return response()->json([
                    'status' => false,
                    'message' => 'Usuario no encontrado: ID ' . $payload['sub']
                ], 401);
            }
            
            // Para depuración
            Log::info('Usuario encontrado con ID: ' . $user->id);
            
            // Verificar rol si es necesario
            if ($role && $user->rol && strtolower($user->rol) !== strtolower($role)) {
                Log::warning("Verificación de rol fallida. Rol esperado: $role, Rol actual: " . ($user->rol ?? 'no definido'));
                
                return response()->json([
                    'status' => false,
                    'message' => 'No tiene permisos para acceder a este recurso. Se requiere rol: ' . $role,
                    'debug' => [
                        'usuario_rol' => $user->rol ?? 'no definido',
                        'rol_requerido' => $role
                    ]
                ], 403);
            }
            
            // Almacenar el usuario en la request para usarlo en el controlador
            $request->user = $user;
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Error en JwtAuthMiddleware: ' . $e->getMessage() . ' - Línea: ' . $e->getLine() . ' - Traza: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => false,
                'message' => 'Error de autenticación: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * Añadir un token a la lista negra
     */
    public static function addToBlacklist($token)
    {
        $tokenId = md5($token);
        self::$blacklist[$tokenId] = true;
        return true;
    }
    
    /**
     * Verificar si un token está en la lista negra
     */
    public static function isInBlacklist($tokenId)
    {
        return isset(self::$blacklist[$tokenId]) && self::$blacklist[$tokenId];
    }
    
    /**
     * Decodificar el token JWT
     */
    private function decodeToken($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                Log::warning('Token inválido: formato incorrecto');
                return false;
            }
            
            [$headerBase64, $payloadBase64, $signatureBase64] = $parts;
            
            // Función para decodificar base64url correctamente
            $base64url_decode = function($data) {
                // Reemplazar caracteres de base64url por caracteres de base64 estándar
                $data = str_replace(['-', '_'], ['+', '/'], $data);
                
                // Añadir padding si es necesario
                $pad = strlen($data) % 4;
                if ($pad) {
                    $data .= str_repeat('=', 4 - $pad);
                }
                
                return base64_decode($data);
            };
            
            // Decodificar el payload
            $payloadJson = $base64url_decode($payloadBase64);
            if (!$payloadJson) {
                Log::warning('Error decodificando payload del token. Payload base64: ' . $payloadBase64);
                return false;
            }
            
            $payload = json_decode($payloadJson, true);
            if (!$payload) {
                Log::warning('Error parseando JSON del payload. JSON: ' . $payloadJson);
                return false;
            }
            
            // Validar la firma (opcional, pero recomendado en producción)
            if (env('APP_ENV') === 'production') {
                if (!$this->validateSignature($headerBase64, $payloadBase64, $signatureBase64)) {
                    Log::warning('Validación de firma del token fallida');
                    return false;
                }
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            Log::error('Error decodificando token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validar la firma del token JWT
     */
    private function validateSignature($headerBase64, $payloadBase64, $signatureBase64)
    {
        $key = env('JWT_SECRET');
        if (!$key) {
            Log::warning('JWT_SECRET no está definido en .env');
            return false;
        }
        
        // Función para decodificar base64url
        $base64url_decode = function($data) {
            return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
        };
        
        // Función para codificar base64url
        $base64url_encode = function($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };
        
        $data = $headerBase64 . '.' . $payloadBase64;
        $signature = hash_hmac('sha256', $data, $key, true);
        $expectedSignature = $base64url_encode($signature);
        
        return hash_equals($signatureBase64, $expectedSignature);
    }
}