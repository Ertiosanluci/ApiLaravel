<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\ValidacionEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Por defecto obtenemos todas las empresas
            $query = Empresa::with('creador');
            
            // Filtro por estado si se especifica
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }
            
            // Filtro por nombre si se especifica
            if ($request->has('nombre')) {
                $query->where('nombre', 'like', '%' . $request->nombre . '%');
            }
            
            // Ordenar
            $query->orderBy('nombre', 'asc');
            
            // Paginación
            $perPage = $request->has('per_page') ? (int) $request->per_page : 15;
            $empresas = $query->paginate($perPage);
            
            return response()->json([
                'status' => true,
                'message' => 'Empresas obtenidas correctamente',
                'empresas' => $empresas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener empresas: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener empresas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'direccion' => 'required|string|max:200',
                'ciudad' => 'required|string|max:100',
                'codigo_postal' => 'nullable|string|max:10',
                'telefono' => 'required|string|max:20',
                'email' => 'nullable|email|max:100',
                'hora_apertura' => 'required|date_format:H:i:s',
                'hora_cierre' => 'required|date_format:H:i:s|after:hora_apertura',
                'dias_operacion' => 'nullable|string|max:100',
                'logo_url' => 'nullable|image|max:2048', // 2MB max
                'banner_url' => 'nullable|image|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Procesar el logo si se proporciona
            $logoPath = null;
            if ($request->hasFile('logo_url') && $request->file('logo_url')->isValid()) {
                // Guardar la imagen y obtener la URL
                $logoPath = $request->file('logo_url')->store('public/logos');
                // Convertir a URL pública
                $logoPath = Storage::url($logoPath);
            }
            
            // Procesar el banner si se proporciona
            $bannerPath = null;
            if ($request->hasFile('banner_url') && $request->file('banner_url')->isValid()) {
                // Guardar la imagen y obtener la URL
                $bannerPath = $request->file('banner_url')->store('public/banners');
                // Convertir a URL pública
                $bannerPath = Storage::url($bannerPath);
            }
            
            // Crear la empresa
            $empresa = new Empresa($request->all());
            $empresa->logo_url = $logoPath;
            $empresa->banner_url = $bannerPath;
            $empresa->creador_id = $request->user->id; // Asumiendo que el middleware coloca el usuario en $request->user
            $empresa->estado = 'pendiente'; // Estado por defecto para nuevas empresas
            $empresa->fecha_registro = now();
            $empresa->save();
            
            // Crear registro de validación pendiente
            ValidacionEmpresa::create([
                'empresa_id' => $empresa->id,
                'estado' => 'pendiente',
                'fecha_solicitud' => now()
            ]);
            
            return response()->json([
                'status' => true,
                'message' => 'Empresa creada correctamente',
                'empresa' => $empresa
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear empresa: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al crear empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $empresa = Empresa::with('creador', 'salas', 'validacion')->find($id);
            
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Empresa obtenida correctamente',
                'empresa' => $empresa
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener empresa: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $empresa = Empresa::find($id);
            
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            // Verificar si el usuario tiene permiso (es propietario o admin)
            if ($request->user->id != $empresa->creador_id && $request->user->rol != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'No tiene permisos para actualizar esta empresa'
                ], 403);
            }
            
            // Validar los datos
            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|max:100',
                'direccion' => 'sometimes|required|string|max:200',
                'ciudad' => 'sometimes|required|string|max:100',
                'codigo_postal' => 'nullable|string|max:10',
                'telefono' => 'sometimes|required|string|max:20',
                'email' => 'nullable|email|max:100',
                'hora_apertura' => 'sometimes|required|date_format:H:i:s',
                'hora_cierre' => 'sometimes|required|date_format:H:i:s|after:hora_apertura',
                'dias_operacion' => 'nullable|string|max:100',
                'logo_url' => 'nullable|image|max:2048', // 2MB max
                'banner_url' => 'nullable|image|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Procesar el logo si se proporciona
            if ($request->hasFile('logo_url') && $request->file('logo_url')->isValid()) {
                // Eliminar el logo antiguo si existe
                if ($empresa->logo_url && Storage::exists(str_replace('/storage', 'public', $empresa->logo_url))) {
                    Storage::delete(str_replace('/storage', 'public', $empresa->logo_url));
                }
                
                // Guardar la nueva imagen y obtener la URL
                $logoPath = $request->file('logo_url')->store('public/logos');
                // Convertir a URL pública
                $empresa->logo_url = Storage::url($logoPath);
            }
            
            // Procesar el banner si se proporciona
            if ($request->hasFile('banner_url') && $request->file('banner_url')->isValid()) {
                // Eliminar el banner antiguo si existe
                if ($empresa->banner_url && Storage::exists(str_replace('/storage', 'public', $empresa->banner_url))) {
                    Storage::delete(str_replace('/storage', 'public', $empresa->banner_url));
                }
                
                // Guardar la nueva imagen y obtener la URL
                $bannerPath = $request->file('banner_url')->store('public/banners');
                // Convertir a URL pública
                $empresa->banner_url = Storage::url($bannerPath);
            }
            
            // Actualizar los campos de la empresa
            $fieldsToUpdate = [
                'nombre', 'direccion', 'ciudad', 'codigo_postal', 'telefono',
                'email', 'hora_apertura', 'hora_cierre', 'dias_operacion'
            ];
            
            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $empresa->$field = $request->$field;
                }
            }
            
            $empresa->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Empresa actualizada correctamente',
                'empresa' => $empresa
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar empresa: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $empresa = Empresa::find($id);
            
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            // Verificar si el usuario tiene permiso (es propietario o admin)
            if ($request->user->id != $empresa->creador_id && $request->user->rol != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'No tiene permisos para eliminar esta empresa'
                ], 403);
            }
            
            // Eliminar el logo si existe
            if ($empresa->logo_url && Storage::exists(str_replace('/storage', 'public', $empresa->logo_url))) {
                Storage::delete(str_replace('/storage', 'public', $empresa->logo_url));
            }
            
            // Eliminar el banner si existe
            if ($empresa->banner_url && Storage::exists(str_replace('/storage', 'public', $empresa->banner_url))) {
                Storage::delete(str_replace('/storage', 'public', $empresa->banner_url));
            }
            
            // Eliminar la empresa (las validaciones y salas se eliminarán por cascade delete en la BD)
            $empresa->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Empresa eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar empresa: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al eliminar empresa: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display a listing of the companies owned by the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function misEmpresas(Request $request)
    {
        try {
            // Obtenemos las empresas del usuario autenticado
            $query = Empresa::where('creador_id', $request->user->id);
            
            // Filtro por estado si se especifica
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }
            
            // Ordenar
            $query->orderBy('nombre', 'asc');
            
            // Paginación
            $perPage = $request->has('per_page') ? (int) $request->per_page : 15;
            $empresas = $query->paginate($perPage);
            
            return response()->json([
                'status' => true,
                'message' => 'Mis empresas obtenidas correctamente',
                'empresas' => $empresas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener mis empresas: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener mis empresas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Change the status of a company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cambiarEstado(Request $request, $id)
    {
        try {
            // Verificar que el usuario es admin (esto lo hace el middleware role:admin)
            $empresa = Empresa::find($id);
            
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            // Validar el estado
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:pendiente,aprobada,rechazada',
                'comentarios' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Cambiar el estado de la empresa
            $empresa->estado = $request->estado;
            $empresa->save();
            
            // Actualizar la validación
            $validacion = ValidacionEmpresa::where('empresa_id', $id)->first();
            if ($validacion) {
                $validacion->admin_id = $request->user->id;
                $validacion->estado = $request->estado;
                $validacion->comentarios = $request->comentarios ?? $validacion->comentarios;
                $validacion->fecha_resolucion = now();
                $validacion->save();
            } else {
                // Crear registro de validación si no existe
                ValidacionEmpresa::create([
                    'empresa_id' => $id,
                    'admin_id' => $request->user->id,
                    'estado' => $request->estado,
                    'comentarios' => $request->comentarios,
                    'fecha_solicitud' => now(),
                    'fecha_resolucion' => now()
                ]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Estado de empresa cambiado correctamente',
                'empresa' => $empresa,
                'validacion' => $validacion ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de empresa: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al cambiar estado de empresa: ' . $e->getMessage()
            ], 500);
        }
    }
}