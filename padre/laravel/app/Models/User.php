<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // Cambiado a false para evitar problemas con campos que pueden no existir

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = 'fecha_registro';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'telefono',
        'rol',
        'fecha_registro',
        'foto_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'password' => 'hashed',
        ];
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
    
    /**
     * Método boot para establecer valores por defecto al crear registros
     */
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->fecha_registro)) {
                $model->fecha_registro = Carbon::now();
            }
        });
    }
}
