<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'usuario';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'ci',
        'telefono',
        'correo',
        'password',
        'rol_id'
    ];

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'rol_id' => $this->rol_id
        ];
    }

    public function rol()
    {
        return DB::table('rol')->where('id', $this->rol_id)->first();
    }

    public static function buscarPorCi($ci)
    {
        return DB::selectOne(
            "SELECT id, nombre, apellido, ci, telefono, correo, rol_id
            FROM usuario
            WHERE ci = ?",
            [$ci]
        );
    }

    public function devolucionesClientes()
    {
        return $this->hasMany(Devolucion::class, 'usuario_id');
    }

    public function devolucionesProveedores()
    {
        return $this->hasMany(DevolucionProveedor::class, 'usuario_id');
    }

    public static function obtenerTodosConRol()
    {
        $usuarios = DB::select(
            'SELECT u.*, r.nombre as rol_nombre, r.id as rol_id
             FROM usuario u 
             LEFT JOIN rol r ON u.rol_id = r.id 
             ORDER BY u.id'
        );

        return array_map(function($u) {
            return [
                'id' => $u->id,
                'nombre' => $u->nombre,
                'apellido' => $u->apellido,
                'ci' => $u->ci,
                'telefono' => $u->telefono,
                'correo' => $u->correo,
                'rol_id' => $u->rol_id,
                'rol' => [
                    'id' => $u->rol_id,
                    'nombre' => $u->rol_nombre
                ]
            ];
        }, $usuarios);
    }

    public static function obtenerPorIdConRol($id)
    {
        $usuarios = DB::select(
            'SELECT u.*, r.nombre as rol_nombre 
             FROM usuario u 
             LEFT JOIN rol r ON u.rol_id = r.id 
             WHERE u.id = ? 
             LIMIT 1',
            [$id]
        );

        return !empty($usuarios) ? $usuarios[0] : null;
    }

    public static function obtenerPorId($id)
    {
        $usuarios = DB::select('SELECT * FROM usuario WHERE id = ? LIMIT 1', [$id]);
        return !empty($usuarios) ? $usuarios[0] : null;
    }

    public static function existeCi($ci, $idExcluir = null)
    {
        if ($idExcluir) {
            $resultado = DB::select('SELECT COUNT(*) as total FROM usuario WHERE ci = ? AND id != ?', [$ci, $idExcluir]);
        } else {
            $resultado = DB::select('SELECT COUNT(*) as total FROM usuario WHERE ci = ?', [$ci]);
        }
        return $resultado[0]->total > 0;
    }

    public static function existeCorreo($correo, $idExcluir = null)
    {
        if ($idExcluir) {
            $resultado = DB::select('SELECT COUNT(*) as total FROM usuario WHERE correo = ? AND id != ?', [$correo, $idExcluir]);
        } else {
            $resultado = DB::select('SELECT COUNT(*) as total FROM usuario WHERE correo = ?', [$correo]);
        }
        return $resultado[0]->total > 0;
    }

    public static function crearNuevo($data)
    {
        $passwordHash = Hash::make($data['password']);
        
        DB::insert(
            'INSERT INTO usuario (nombre, apellido, ci, telefono, correo, password, rol_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['nombre'],
                $data['apellido'],
                $data['ci'],
                $data['telefono'],
                $data['correo'],
                $passwordHash,
                $data['rol_id']
            ]
        );

        $usuario = DB::select(
            'SELECT u.*, r.nombre as rol_nombre 
             FROM usuario u 
             LEFT JOIN rol r ON u.rol_id = r.id 
             WHERE u.correo = ? 
             LIMIT 1',
            [$data['correo']]
        )[0];

        return $usuario;
    }

    public static function actualizarPorId($id, $data)
    {
        $usuarioActual = self::obtenerPorId($id);
        
        $nombre = $data['nombre'] ?? $usuarioActual->nombre;
        $apellido = $data['apellido'] ?? $usuarioActual->apellido;
        $ci = $data['ci'] ?? $usuarioActual->ci;
        $telefono = $data['telefono'] ?? $usuarioActual->telefono;
        $correo = $data['correo'] ?? $usuarioActual->correo;
        $rol_id = $data['rol_id'] ?? $usuarioActual->rol_id;
        
        $password = isset($data['password']) && !empty($data['password'])
            ? Hash::make($data['password']) 
            : $usuarioActual->password;

        DB::update(
            'UPDATE usuario 
             SET nombre = ?, apellido = ?, ci = ?, telefono = ?, correo = ?, password = ?, rol_id = ? 
             WHERE id = ?',
            [$nombre, $apellido, $ci, $telefono, $correo, $password, $rol_id, $id]
        );

        $usuarioActualizado = DB::select(
            'SELECT u.*, r.nombre as rol_nombre 
             FROM usuario u 
             LEFT JOIN rol r ON u.rol_id = r.id 
             WHERE u.id = ? 
             LIMIT 1',
            [$id]
        )[0];

        return $usuarioActualizado;
    }

    public static function eliminarPorId($id)
    {
        return DB::delete('DELETE FROM usuario WHERE id = ?', [$id]);
    }

    public static function crearUsuarioRegistro($datos)
    {
        $passwordHash = Hash::make($datos['password']);
        
        DB::insert(
            'INSERT INTO usuario (nombre, apellido, ci, telefono, correo, password, rol_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $datos['nombre'],
                $datos['apellido'], 
                $datos['ci'],
                $datos['telefono'],
                $datos['correo'],
                $passwordHash,
                3
            ]
        );
        
        $resultado = DB::select(
            'SELECT * FROM usuario WHERE correo = ? LIMIT 1',
            [$datos['correo']]
        );
        return $resultado[0];
    }

    public static function obtenerPorCorreo($correo)
    {
        $resultado = DB::select(
            'SELECT * FROM usuario WHERE correo = ? LIMIT 1',
            [$correo]
        );
        return !empty($resultado) ? $resultado[0] : null;
    }

    public static function verificarPassword($passwordPlano, $passwordHash)
    {
        return Hash::check($passwordPlano, $passwordHash);
    }
}
