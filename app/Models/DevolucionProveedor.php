<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DevolucionProveedor extends Model
{
    protected $table = 'devolucion_proveedor';
    
    protected $fillable = [
        'proveedor_id',
        'usuario_id',
        'fecha_devolucion',
        'observacion'
    ];

    protected $casts = [
        'fecha_devolucion' => 'date'
    ];

    public $timestamps = false;

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleDevolucionProveedor::class, 'id_devolucion_proveedor');
    }

    public static function obtenerTodasLasDevoluciones()
    {
        return DB::select(
            "SELECT 
                dp.id,
                dp.fecha_devolucion,
                dp.observacion,
                p.nombre as proveedor_nombre,
                u.nombre as usuario_nombre,
                COUNT(ddp.id) as total_productos
            FROM devolucion_proveedor dp
            LEFT JOIN proveedor p ON dp.proveedor_id = p.id
            LEFT JOIN usuario u ON dp.usuario_id = u.id
            LEFT JOIN detalle_devolucion_proveedor ddp ON dp.id = ddp.id_devolucion_proveedor
            GROUP BY dp.id, dp.fecha_devolucion, dp.observacion, p.nombre, u.nombre
            ORDER BY dp.fecha_devolucion DESC"
        );
    }

    public static function obtenerDetallePorId($id)
    {
        $resultado = DB::select(
            "SELECT 
                dp.id,
                dp.fecha_devolucion,
                dp.observacion,
                p.nombre as proveedor_nombre,
                u.nombre as usuario_nombre,
                ddp.cantidad,
                prod.nombre as producto_nombre,
                prod.precio_unitario
            FROM devolucion_proveedor dp
            LEFT JOIN proveedor p ON dp.proveedor_id = p.id
            LEFT JOIN usuario u ON dp.usuario_id = u.id
            INNER JOIN detalle_devolucion_proveedor ddp ON dp.id = ddp.id_devolucion_proveedor
            INNER JOIN producto prod ON ddp.producto_id = prod.id
            WHERE dp.id = ?",
            [$id]
        );

        if (empty($resultado)) {
            return null;
        }

        $devolucion = (object) [
            'id' => $resultado[0]->id,
            'fecha_devolucion' => $resultado[0]->fecha_devolucion,
            'observacion' => $resultado[0]->observacion,
            'proveedor_nombre' => $resultado[0]->proveedor_nombre,
            'usuario_nombre' => $resultado[0]->usuario_nombre,
            'total_productos' => count($resultado),
            'detalles' => []
        ];

        foreach ($resultado as $row) {
            $devolucion->detalles[] = (object) [
                'cantidad' => $row->cantidad,
                'producto_nombre' => $row->producto_nombre,
                'precio_unitario' => $row->precio_unitario
            ];
        }

        return $devolucion;
    }

    public static function crearNuevaDevolucion($datos, $productos, $usuarioId)
    {
        DB::beginTransaction();

        try {
            $devolucionId = DB::selectOne(
                "INSERT INTO devolucion_proveedor (proveedor_id, usuario_id, fecha_devolucion, observacion)
                VALUES (?, ?, NOW(), ?)
                RETURNING id",
                [
                    $datos['proveedor_id'],
                    $usuarioId,
                    $datos['observacion']
                ]
            )->id;

            foreach ($productos as $producto) {
                DB::insert(
                    "INSERT INTO detalle_devolucion_proveedor (id_devolucion_proveedor, producto_id, cantidad)
                    VALUES (?, ?, ?)",
                    [
                        $devolucionId,
                        $producto['producto_id'],
                        $producto['cantidad']
                    ]
                );

                DB::update(
                    "UPDATE producto 
                    SET stock_actual = stock_actual - ? 
                    WHERE id = ?",
                    [
                        $producto['cantidad'],
                        $producto['producto_id']
                    ]
                );
            }

            DB::commit();
            return $devolucionId;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}