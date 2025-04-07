<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sala extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'salas';

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
        'empresa_id',
        'nombre',
        'tipo',
        'capacidad',
        'precio_hora',
        'equipamiento',
        'disponible',
        'imagen_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacidad' => 'integer',
        'precio_hora' => 'decimal:2',
        'disponible' => 'boolean',
    ];

    /**
     * Get the empresa that owns the sala.
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Get the reservas for the sala.
     */
    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'sala_id');
    }
}