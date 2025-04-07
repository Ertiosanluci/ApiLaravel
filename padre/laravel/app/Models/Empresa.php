<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'empresas';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'direccion',
        'ciudad',
        'codigo_postal',
        'telefono',
        'email',
        'hora_apertura',
        'hora_cierre',
        'dias_operacion',
        'creador_id', // Referencia al usuario que crea
        'estado', // pendiente, aprobada, rechazada
        'fecha_registro',
        'logo_url',
        'banner_url'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_registro' => 'datetime',
        'hora_apertura' => 'datetime:H:i:s',
        'hora_cierre' => 'datetime:H:i:s',
    ];

    /**
     * Get the user that owns the empresa.
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creador_id');
    }
    
    /**
     * Get the salas for the empresa.
     */
    public function salas()
    {
        return $this->hasMany(Sala::class, 'empresa_id');
    }
    
    /**
     * Get the validacion for the empresa.
     */
    public function validacion()
    {
        return $this->hasOne(ValidacionEmpresa::class, 'empresa_id');
    }
    
    /**
     * Override attributesToArray to ensure no errors occur when accessing missing attributes
     */
    public function attributesToArray()
    {
        try {
            return parent::attributesToArray();
        } catch (\Exception $e) {
            // En caso de error, intentamos construir un array con los atributos disponibles
            $attributes = [];
            
            // Solo incluimos los atributos que sabemos que existen
            foreach ($this->attributes as $key => $value) {
                $attributes[$key] = $value;
            }
            
            return $attributes;
        }
    }
    
    /**
     * Sobrescribe el método para acceder a atributos para que no falle si algún campo no existe
     */
    public function getAttribute($key)
    {
        try {
            return parent::getAttribute($key);
        } catch (\Exception $e) {
            return null;
        }
    }
}