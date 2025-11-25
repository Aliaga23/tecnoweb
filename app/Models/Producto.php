<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'producto';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'stock_actual',
        'precio_unitario',
        'creado_en',
        'imagen_url',
        'categoria_id'
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'stock_actual' => 'integer',
        'creado_en' => 'datetime'
    ];
}
