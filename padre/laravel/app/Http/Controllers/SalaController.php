<?php

namespace App\Http\Controllers;

use App\Models\Sala;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SalaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Por defecto obtenemos todas las salas
            $query = Sala::with('empresa');
            
            // Filtro por disponibilidad si se especifica
            if ($request->has('disponible')) {
                $disponible = $request->disponible === 'true' || $request->disponible === '1' ? 1 : 0;
                $query->where('disponible', $disponible);
            }
            
            // Filtro por tipo si se especifica
            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }
            
            // Ordenar
            $query->orderBy('nombre', 'asc');
            
            // Paginación
            $perPage = $request->has('per_page') ? (int) $request->per_page : 15;
            $salas = $query->paginate($perPage);
            
            return response()->json([
                'status' => true,
                'message' => 'Salas obtenidas correctamente',
                'salas' => $salas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener salas: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener salas: ' . $e->getMessage()
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
                'empresa_id' => 'required|integer|exists:empresas,id',
                'nombre' => 'required|string|max:100',
                'tipo' => 'required|in:conferencia,reuniones,eventos,capacitacion',
                'capacidad' => 'required|integer|min:1',
                'precio_hora' => 'required|numeric|min:0',
                'equipamiento' => 'nullable|string',
                'disponible' => 'nullable|boolean',
                'imagen_url' => 'nullable|image|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verificar que el usuario tiene permisos para crear salas en esta empresa
            $empresa = Empresa::find($request->empresa_id);
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            // Solo el creador de la empresa o un admin puede crear salas
            if ($empresa->creador_id != $request->user->id && $request->user->rol != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'No tiene permisos para crear salas en esta empresa'
                ], 403);
            }
            
            // Procesar la imagen si se proporciona
            $imagenPath = null;
            if ($request->hasFile('imagen_url') && $request->file('imagen_url')->isValid()) {
                // Guardar la imagen y obtener la URL
                $imagenPath = $request->file('imagen_url')->store('public/salas');
                // Convertir a URL pública
                $imagenPath = Storage::url($imagenPath);
            }
            
            // Crear la sala
            $sala = new Sala($request->all());
            $sala->imagen_url = $imagenPath;
            
            // Si no se especifica disponible, se establece como disponible por defecto
            if (!$request->has('disponible')) {
                $sala->disponible = true;
            }
            
            $sala->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Sala creada correctamente',
                'sala' => $sala
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear sala: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al crear sala: ' . $e->getMessage()
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
            $sala = Sala::with('empresa')->find($id);
            
            if (!$sala) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sala no encontrada'
                ], 404);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Sala obtenida correctamente',
                'sala' => $sala
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener sala: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener sala: ' . $e->getMessage()
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
            $sala = Sala::find($id);
            
            if (!$sala) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sala no encontrada'
                ], 404);
            }
            
            // Verificar que el usuario tiene permiso para actualizar esta sala
            $empresa = Empresa::find($sala->empresa_id);
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            // Solo el creador de la empresa o un admin puede actualizar salas
            if ($empresa->creador_id != $request->user->id && $request->user->rol != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'No tiene permisos para actualizar esta sala'
                ], 403);
            }
            
            // Validar los datos
            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|max:100',
                'tipo' => 'sometimes|required|in:conferencia,reuniones,eventos,capacitacion',
                'capacidad' => 'sometimes|required|integer|min:1',
                'precio_hora' => 'sometimes|required|numeric|min:0',
                'equipamiento' => 'nullable|string',
                'disponible' => 'nullable|boolean',
                'imagen_url' => 'nullable|image|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Procesar la imagen si se proporciona
            if ($request->hasFile('imagen_url') && $request->file('imagen_url')->isValid()) {
                // Eliminar la imagen antigua si existe
                if ($sala->imagen_url && Storage::exists(str_replace('/storage', 'public', $sala->imagen_url))) {
                    Storage::delete(str_replace('/storage', 'public', $sala->imagen_url));
                }
                
                // Guardar la nueva imagen y obtener la URL
                $imagenPath = $request->file('imagen_url')->store('public/salas');
                // Convertir a URL pública
                $sala->imagen_url = Storage::url($imagenPath);
            }
            
            // Actualizar los campos de la sala
            $fieldsToUpdate = [
                'nombre', 'tipo', 'capacidad', 'precio_hora', 'equipamiento', 'disponible'
            ];
            
            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $sala->$field = $request->$field;
                }
            }
            
            $sala->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Sala actualizada correctamente',
                'sala' => $sala
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar sala: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar sala: ' . $e->getMessage()
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
            $sala = Sala::find($id);
            
            if (!$sala) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sala no encontrada'
                ], 404);
            }
            
            // Verificar que el usuario tiene permiso para eliminar esta sala
            $empresa = Empresa::find($sala->empresa_id);
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            // Solo el creador de la empresa o un admin puede eliminar salas
            if ($empresa->creador_id != $request->user->id && $request->user->rol != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'No tiene permisos para eliminar esta sala'
                ], 403);
            }
            
            // Eliminar la imagen si existe
            if ($sala->imagen_url && Storage::exists(str_replace('/storage', 'public', $sala->imagen_url))) {
                Storage::delete(str_replace('/storage', 'public', $sala->imagen_url));
            }
            
            // Eliminar la sala
            $sala->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Sala eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar sala: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al eliminar sala: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all salas for a specific empresa.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function salasPorEmpresa($id)
    {
        try {
            $empresa = Empresa::find($id);
            
            if (!$empresa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }
            
            $salas = Sala::where('empresa_id', $id)->get();
            
            return response()->json([
                'status' => true,
                'message' => 'Salas de la empresa obtenidas correctamente',
                'empresa' => $empresa->nombre,
                'salas' => $salas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener salas por empresa: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener salas por empresa: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Search for salas based on criteria.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function buscar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo' => 'nullable|in:conferencia,reuniones,eventos,capacitacion',
                'capacidad_min' => 'nullable|integer|min:1',
                'precio_max' => 'nullable|numeric|min:0',
                'ciudad' => 'nullable|string',
                'disponible' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Iniciamos la consulta con un join para poder filtrar por ciudad
            $query = Sala::join('empresas', 'salas.empresa_id', '=', 'empresas.id')
                          ->select('salas.*', 'empresas.nombre as empresa_nombre', 'empresas.ciudad');
            
            // Filtrar por tipo
            if ($request->has('tipo')) {
                $query->where('salas.tipo', $request->tipo);
            }
            
            // Filtrar por capacidad mínima
            if ($request->has('capacidad_min')) {
                $query->where('salas.capacidad', '>=', $request->capacidad_min);
            }
            
            // Filtrar por precio máximo
            if ($request->has('precio_max')) {
                $query->where('salas.precio_hora', '<=', $request->precio_max);
            }
            
            // Filtrar por ciudad
            if ($request->has('ciudad')) {
                $query->where('empresas.ciudad', 'like', '%' . $request->ciudad . '%');
            }
            
            // Filtrar por disponibilidad
            if ($request->has('disponible')) {
                $disponible = $request->disponible === 'true' || $request->disponible === '1' ? 1 : 0;
                $query->where('salas.disponible', $disponible);
            }
            
            // Solo mostrar salas de empresas aprobadas
            $query->where('empresas.estado', 'aprobada');
            
            // Ordenar por precio ascendente por defecto
            $query->orderBy('salas.precio_hora', 'asc');
            
            // Paginación
            $perPage = $request->has('per_page') ? (int) $request->per_page : 15;
            $salas = $query->paginate($perPage);
            
            return response()->json([
                'status' => true,
                'message' => 'Búsqueda completada correctamente',
                'salas' => $salas
            ]);
        } catch (\Exception $e) {
            Log::error('Error en la búsqueda de salas: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Error en la búsqueda de salas: ' . $e->getMessage()
            ], 500);
        }
    }
}