<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\SalaController;
use App\Http\Middleware\JwtAuthMiddleware;

// Rutas de autenticación agrupadas como solicitado
Route::group(['prefix' => 'auth'], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware(JwtAuthMiddleware::class);
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware(JwtAuthMiddleware::class);
    Route::get('me', [AuthController::class, 'me'])->middleware(JwtAuthMiddleware::class);
});

// Ruta principal para listar usuarios (implementación que funciona)
Route::get('/usuarios', function (Request $request) {
    try {
        // Obtener el token
        $authHeader = $request->header('Authorization');
        $token = null;
        
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }
        
        // Si no hay token en el header, intenta obtenerlo de los parámetros de la URL
        if (!$token && $request->has('token')) {
            $token = $request->query('token');
        }
        
        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Token no proporcionado'
            ], 401);
        }
        
        // Decodificar el token
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return response()->json([
                'error' => 'Formato de token inválido'
            ], 400);
        }
        
        // Función para decodificar base64url
        $padding = function($data) {
            $remainder = strlen($data) % 4;
            if ($remainder > 0) {
                return $data . str_repeat('=', 4 - $remainder);
            }
            return $data;
        };
        
        $payloadBase64 = $parts[1];
        $payloadJson = base64_decode(strtr($padding($payloadBase64), '-_', '+/'));
        $payload = json_decode($payloadJson, true);
        
        // Verificar que es un admin
        if (!isset($payload['rol']) || strtolower($payload['rol']) !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'No tiene permisos para acceder a este recurso. Se requiere rol: admin'
            ], 403);
        }
        
        // Verificar si el token ha expirado
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return response()->json([
                'status' => false,
                'message' => 'Token expirado'
            ], 401);
        }
        
        // Obtener todos los usuarios a través del controlador
        $controller = new UserController();
        return $controller->index();
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Ver detalle de usuario específico
Route::get('/usuarios/{id}', function (Request $request, $id) {
    try {
        // Obtener el token
        $authHeader = $request->header('Authorization');
        $token = null;
        
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }
        
        // Si no hay token en el header, intenta obtenerlo de los parámetros de la URL
        if (!$token && $request->has('token')) {
            $token = $request->query('token');
        }
        
        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Token no proporcionado'
            ], 401);
        }
        
        // Decodificar el token
        $parts = explode('.', $token);
        $padding = function($data) {
            $remainder = strlen($data) % 4;
            if ($remainder > 0) {
                return $data . str_repeat('=', 4 - $remainder);
            }
            return $data;
        };
        
        $payloadBase64 = $parts[1];
        $payloadJson = base64_decode(strtr($padding($payloadBase64), '-_', '+/'));
        $payload = json_decode($payloadJson, true);
        
        // Verificar que es un admin
        if (!isset($payload['rol']) || strtolower($payload['rol']) !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'No tiene permisos para acceder a este recurso'
            ], 403);
        }
        
        // Obtener el usuario
        $controller = new UserController();
        return $controller->show($id);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Mantener las rutas protegidas adicionales
Route::middleware(JwtAuthMiddleware::class)->group(function() {
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Rutas para empresas
    Route::get('empresas', [EmpresaController::class, 'index']);
    Route::post('empresas', [EmpresaController::class, 'store']);
    Route::get('empresas/{id}', [EmpresaController::class, 'show']);
    Route::put('empresas/{id}', [EmpresaController::class, 'update']);
    Route::delete('empresas/{id}', [EmpresaController::class, 'destroy']);
    Route::get('mis-empresas', [EmpresaController::class, 'misEmpresas']);
    
    // Rutas para salas
    Route::get('salas', [SalaController::class, 'index']);
    Route::post('salas', [SalaController::class, 'store']);
    Route::get('salas/{id}', [SalaController::class, 'show']);
    Route::put('salas/{id}', [SalaController::class, 'update']);
    Route::delete('salas/{id}', [SalaController::class, 'destroy']);
    Route::get('empresas/{id}/salas', [SalaController::class, 'salasPorEmpresa']);
    Route::post('salas/buscar', [SalaController::class, 'buscar']);
    
    // Rutas solo para administradores
    Route::group(['middleware' => ['role:admin']], function () {
        Route::put('empresas/{id}/estado', [EmpresaController::class, 'cambiarEstado']);
    });
});