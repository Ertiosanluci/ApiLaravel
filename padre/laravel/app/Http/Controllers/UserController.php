<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Obtener lista de todos los usuarios
     */
    public function index()
    {
        $usuarios = User::all();
        
        return response()->json([
            'status' => true,
            'message' => 'Lista de usuarios obtenida correctamente',
            'usuarios' => $usuarios
        ]);
    }
    
    /**
     * Mostrar un usuario específico
     */
    public function show($id)
    {
        $usuario = User::find($id);
        
        if (!$usuario) {
            return response()->json([
                'status' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'usuario' => $usuario
        ]);
    }
    
    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $id)
    {
        $usuario = User::find($id);
        
        if (!$usuario) {
            return response()->json([
                'status' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required',
            'apellido' => 'sometimes|required',
            'email' => 'sometimes|required|email|unique:usuarios,email,'.$id,
            'password' => 'sometimes|min:6',
            'telefono' => 'nullable',
            'rol' => 'sometimes|in:admin,supervisor,usuario',
            'foto_url' => 'nullable|url'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Actualizar datos
        if ($request->has('nombre')) $usuario->nombre = $request->nombre;
        if ($request->has('apellido')) $usuario->apellido = $request->apellido;
        if ($request->has('email')) $usuario->email = $request->email;
        if ($request->has('password')) $usuario->password = Hash::make($request->password);
        if ($request->has('telefono')) $usuario->telefono = $request->telefono;
        if ($request->has('rol')) $usuario->rol = $request->rol;
        if ($request->has('foto_url')) $usuario->foto_url = $request->foto_url;
        
        $usuario->save();
        
        return response()->json([
            'status' => true,
            'message' => 'Usuario actualizado correctamente',
            'usuario' => $usuario
        ]);
    }
    
    /**
     * Eliminar un usuario
     */
    public function destroy($id)
    {
        $usuario = User::find($id);
        
        if (!$usuario) {
            return response()->json([
                'status' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        
        $usuario->delete();
        
        return response()->json([
            'status' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
    }
    
    /**
     * Buscar usuarios por criterios
     */
    public function search(Request $request)
    {
        $query = User::query();
        
        // Filtrar por nombre
        if ($request->has('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }
        
        // Filtrar por apellido
        if ($request->has('apellido')) {
            $query->where('apellido', 'like', '%' . $request->apellido . '%');
        }
        
        // Filtrar por email
        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        
        // Filtrar por rol
        if ($request->has('rol')) {
            $query->where('rol', $request->rol);
        }
        
        $usuarios = $query->get();
        
        return response()->json([
            'status' => true,
            'message' => 'Búsqueda realizada correctamente',
            'usuarios' => $usuarios
        ]);
    }
}