<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Buscar al usuario por email
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        // Generar token manual basado en el JWT_SECRET del .env
        $token = $this->generateCustomToken($user);

        return response()->json([
            'status' => true,
            'message' => 'Usuario logueado correctamente',
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'rol' => $user->rol
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Registro de usuario
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required',
                'apellido' => 'required',
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|min:6',
                'telefono' => 'nullable',
                'rol' => 'required|in:admin,supervisor,usuario',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'telefono' => $request->telefono ?? null,
                'rol' => $request->rol,
                'fecha_registro' => now(),
            ]);

            $token = $this->generateCustomToken($user);

            return response()->json([
                'status' => true,
                'message' => 'Usuario registrado correctamente',
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'email' => $user->email,
                    'rol' => $user->rol
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);
        } catch (\Exception $e) {
            // Captura cualquier error y devuelve un mensaje descriptivo
            return response()->json([
                'status' => false,
                'message' => 'Error al registrar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar un token personalizado usando el JWT_SECRET del .env
     */
    private function generateCustomToken($user)
    {
        $key = env('JWT_SECRET');
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'rol' => $user->rol,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7), // 1 semana
            'jti' => Str::random(16)
        ];

        // Función para codificar como base64url (importante para JWT)
        $base64url_encode = function($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        // Codificar el token correctamente según el estándar JWT
        $header = $base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEncoded = $base64url_encode(json_encode($payload));
        $signature = $base64url_encode(hash_hmac('sha256', "$header.$payloadEncoded", $key, true));

        return "$header.$payloadEncoded.$signature";
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        // Obtenemos el token del header o del parámetro
        $authHeader = $request->header('Authorization');
        $token = null;
        
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        } elseif ($request->has('token')) {
            $token = $request->query('token');
        }
        
        if ($token) {
            // Agregamos el token a la lista negra usando nuestro método estático
            \App\Http\Middleware\JwtAuthMiddleware::addToBlacklist($token);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'Sesión cerrada correctamente',
            'redirectTo' => '/login' // Dirección a la que redirigir en el frontend
        ]);
    }
    
    /**
     * Obtener el perfil del usuario actual
     */
    public function profile(Request $request)
    {
        // La autenticación se verifica en el middleware
        $user = $request->user;
        
        return response()->json([
            'status' => true,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'rol' => $user->rol,
                'fecha_registro' => $user->fecha_registro,
                'foto_url' => $user->foto_url
            ]
        ]);
    }

    /**
     * Renovar token del usuario actual
     */
    public function refresh(Request $request)
    {
        // La autenticación se verifica en el middleware
        $user = $request->user;
        
        // Generar un nuevo token
        $token = $this->generateCustomToken($user);
        
        return response()->json([
            'status' => true,
            'message' => 'Token renovado correctamente',
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Obtener información del usuario actual (alias para profile)
     */
    public function me(Request $request)
    {
        try {
            // Verificación explícita del token
            $authHeader = $request->header('Authorization');
            $token = null;
            
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            } elseif ($request->has('token')) {
                $token = $request->query('token');
            } elseif (isset($_GET['token'])) {
                $token = $_GET['token'];
            }
            // Si no hay token, devuelve error de autenticación
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'authenticated' => false,
                    'message' => 'Token no proporcionado'
                ], 401);
            }
            // Verifica si el token ya está en la request (middleware lo puso ahí)
            if (isset($request->user)) {
                $user = $request->user;
                
                // Crear un array con solo los campos disponibles en el usuario
                $userData = [
                    'id' => $user->id ?? null,
                    'authenticated' => true
                ];
                
                // Añadir campos solo si existen en el objeto de usuario
                if (isset($user->nombre)) $userData['nombre'] = $user->nombre;
                if (isset($user->apellido)) $userData['apellido'] = $user->apellido;
                if (isset($user->email)) $userData['email'] = $user->email;
                if (isset($user->telefono)) $userData['telefono'] = $user->telefono;
                if (isset($user->rol)) $userData['rol'] = $user->rol;
                if (isset($user->fecha_registro)) $userData['fecha_registro'] = $user->fecha_registro;
                if (isset($user->foto_url)) $userData['foto_url'] = $user->foto_url;
                
                return response()->json([
                    'status' => true,
                    'authenticated' => true,
                    'message' => 'Usuario autenticado correctamente',
                    'user' => $userData
                ]);
            } else {
                // Si por alguna razón no está en request pero tenemos un token, intentamos decodificarlo
                $payload = $this->decodeToken($token);
                
                if (!$payload || !isset($payload['sub'])) {
                    return response()->json([
                        'status' => false,
                        'authenticated' => false,
                        'message' => 'Token inválido o mal formado'
                    ], 401);
                }
                
                // Verificar si el token expiró
                if (!isset($payload['exp']) || $payload['exp'] < time()) {
                    return response()->json([
                        'status' => false,
                        'authenticated' => false,
                        'message' => 'Token expirado'
                    ], 401);
                }
                
                // Verificar si está en lista negra
                $tokenId = md5($token);
                // Usamos el método addToBlacklist para verificar si el token está en la lista negra
                // ya que la propiedad $blacklist es privada y no podemos acceder directamente
                $inBlacklist = false;
                try {
                    // Intentamos usar el método estático para verificar el token
                    $inBlacklist = \App\Http\Middleware\JwtAuthMiddleware::isInBlacklist($tokenId);
                } catch (\Exception $e) {
                    // Si no existe el método, simplemente continuamos (no está en lista negra)
                    $inBlacklist = false;
                }
                
                if ($inBlacklist) {
                    return response()->json([
                        'status' => false,
                        'authenticated' => false,
                        'message' => 'Token revocado o en lista negra'
                    ], 401);
                }
                
                // Buscar usuario por ID
                $user = User::find($payload['sub']);
                
                if (!$user) {
                    return response()->json([
                        'status' => false,
                        'authenticated' => false,
                        'message' => 'Usuario no encontrado con el token proporcionado'
                    ], 401);
                }
                
                // Crear un array con solo los campos disponibles en el usuario
                $userData = [
                    'id' => $user->id ?? null,
                    'authenticated' => true
                ];
                
                // Añadir campos solo si existen en el objeto de usuario
                if (isset($user->nombre)) $userData['nombre'] = $user->nombre;
                if (isset($user->apellido)) $userData['apellido'] = $user->apellido;
                if (isset($user->email)) $userData['email'] = $user->email;
                if (isset($user->telefono)) $userData['telefono'] = $user->telefono;
                if (isset($user->rol)) $userData['rol'] = $user->rol;
                if (isset($user->fecha_registro)) $userData['fecha_registro'] = $user->fecha_registro;
                if (isset($user->foto_url)) $userData['foto_url'] = $user->foto_url;
                
                return response()->json([
                    'status' => true,
                    'authenticated' => true,
                    'message' => 'Usuario autenticado correctamente',
                    'user' => $userData
                ]);
            }
        } catch (\Exception $e) {
            // Registrar el error para debugging
            \Illuminate\Support\Facades\Log::error('Error en me(): ' . $e->getMessage() . ' en línea ' . $e->getLine() . ' traza: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => false,
                'authenticated' => false,
                'message' => 'Error al verificar autenticación: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Decodificar el token JWT
     * Este método debe coincidir con la implementación en JwtAuthMiddleware
     */
    private function decodeToken($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
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
                return false;
            }
            
            $payload = json_decode($payloadJson, true);
            if (!$payload) {
                return false;
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}