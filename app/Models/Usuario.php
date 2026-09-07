<?php

require_once CORE_PATH . 'Controller.php';

class Usuario extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerPorUsuario($usuario)
    {
        $sql = 'SELECT * FROM usuarios WHERE usuario = :usuario AND estado = 1 LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function validarCredenciales($usuario, $password)
    {
        $usuarioEncontrado = $this->obtenerPorUsuario($usuario);
        if (!$usuarioEncontrado) {
            return false;
        }
        if (password_verify($password, $usuarioEncontrado['password'])) {
            return $usuarioEncontrado;
        }
        return false;
    }

    public function obtenerPorId($id)
    {
        $campos = 'u.id, u.nombre, u.usuario, u.rol, u.estado, u.creado_en';
        if ($this->tieneColumna('sucursal')) {
            $campos .= ', u.sucursal';
        } else {
            $campos .= ", '' AS sucursal";
        }
        if ($this->tieneColumna('caja_id')) {
            $campos .= ', u.caja_id';
        } else {
            $campos .= ", 0 AS caja_id";
        }

        $sql = 'SELECT ' . $campos . ' FROM usuarios u WHERE u.id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerTodos()
    {
        $camposAsignacion = $this->obtenerColumnasAsignacion();
        $joinCajas = $this->tieneColumna('caja_id') && $this->existeTabla('cajas') ? ' LEFT JOIN cajas c ON c.id = u.caja_id' : '';
        $stmt = $this->pdo->query('SELECT u.id, u.nombre, u.usuario, u.rol, u.estado, u.creado_en, ' . $camposAsignacion . ' FROM usuarios u' . $joinCajas . ' ORDER BY u.nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCajas()
    {
        if (!$this->existeTabla('cajas')) return [];
        $stmt = $this->pdo->query('SELECT id, nombre FROM cajas WHERE estado = 1 ORDER BY nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerNombreCaja($cajaId)
    {
        if (!$this->existeTabla('cajas') || (int)$cajaId <= 0) return null;
        $stmt = $this->pdo->prepare('SELECT nombre FROM cajas WHERE id = :id AND estado = 1 LIMIT 1');
        $stmt->execute([':id' => (int)$cajaId]);
        $caja = $stmt->fetch(PDO::FETCH_ASSOC);
        return $caja ? $caja['nombre'] : null;
    }

    public function insertar($datos)
    {
        $nombre = trim((string)($datos['nombre'] ?? ''));
        $usuario = trim((string)($datos['usuario'] ?? $datos['username'] ?? ''));
        $password = (string)($datos['password'] ?? '');
        $rol = (string)($datos['rol'] ?? 'cajero');
        $estado = (int)($datos['estado'] ?? 1);
        $sucursal = trim((string)($datos['sucursal_id'] ?? $datos['sucursal'] ?? ''));
        $cajaRecibida = $datos['caja_id'] ?? $datos['caja'] ?? 1;
        $cajaId = is_numeric($cajaRecibida) ? (int)$cajaRecibida : 1;

        $campos = 'nombre, usuario, password, rol, estado';
        $valores = ':nombre, :usuario, :password, :rol, :estado';
        $parametros = [
            ':nombre' => $nombre, ':usuario' => $usuario,
            ':password' => password_hash($password, PASSWORD_BCRYPT),
            ':rol' => $rol, ':estado' => $estado
        ];
        if ($this->tieneColumna('sucursal')) {
            $campos .= ', sucursal';
            $valores .= ', :sucursal';
            $parametros[':sucursal'] = $sucursal;
        }
        if ($this->tieneColumna('caja')) {
            $campos .= ', caja';
            $valores .= ', :caja';
            $parametros[':caja'] = (string)($datos['caja'] ?? '');
        }
        if ($this->tieneColumna('caja_id')) {
            $campos .= ', caja_id';
            $valores .= ', :caja_id';
            $parametros[':caja_id'] = $cajaId > 0 ? $cajaId : null;
        }
        $stmt = $this->pdo->prepare("INSERT INTO usuarios ({$campos}) VALUES ({$valores})");
        return $stmt->execute($parametros);
    }

    public function actualizar($id, $datos)
    {
        $sucursal = trim((string)($datos['sucursal_id'] ?? $datos['sucursal'] ?? ''));
        $cajaRecibida = $datos['caja_id'] ?? $datos['caja'] ?? 1;
        $cajaId = is_numeric($cajaRecibida) ? (int)$cajaRecibida : 1;
        $sql = 'UPDATE usuarios SET nombre=:nombre, usuario=:usuario, rol=:rol, estado=:estado';
        $parametros = [':nombre'=>(string)($datos['nombre'] ?? ''), ':usuario'=>(string)($datos['usuario'] ?? $datos['username'] ?? ''), ':rol'=>(string)($datos['rol'] ?? 'cajero'), ':estado'=>(int)($datos['estado'] ?? 1), ':id'=>(int)$id];
        if ($this->tieneColumna('sucursal')) { $sql .= ', sucursal=:sucursal'; $parametros[':sucursal'] = $sucursal; }
        if ($this->tieneColumna('caja')) { $sql .= ', caja=:caja'; $parametros[':caja'] = (string)($datos['caja'] ?? ''); }
        if ($this->tieneColumna('caja_id')) { $sql .= ', caja_id=:caja_id'; $parametros[':caja_id'] = $cajaId > 0 ? $cajaId : null; }
        if ((string)($datos['password'] ?? '') !== '') {
            $sql .= ', password=:password';
            $parametros[':password'] = password_hash((string)$datos['password'], PASSWORD_BCRYPT);
        }
        $stmt = $this->pdo->prepare($sql . ' WHERE id=:id');
        return $stmt->execute($parametros);
    }

    private function obtenerColumnasAsignacion()
    {
        $caja = $this->tieneColumna('caja_id') && $this->existeTabla('cajas')
            ? 'u.caja_id, COALESCE(c.nombre, \'\') AS caja_nombre'
            : "0 AS caja_id, '' AS caja_nombre";
        $sucursal = $this->tieneColumna('sucursal') ? 'u.sucursal' : "'' AS sucursal";
        return $sucursal . ', ' . $caja;
    }

    private function tieneColumna($columna)
    {
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
        if ($columna === '') return false;
        $stmt = $this->pdo->query("SHOW COLUMNS FROM `usuarios` LIKE '{$columna}'");
        return $stmt !== false && $stmt->rowCount() > 0;
    }

    private function existeTabla($tabla)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        if ($tabla === '') return false;
        $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tabla}'");
        return $stmt !== false && $stmt->rowCount() > 0;
    }

    public function cambiarPassword($id, $password)
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET password=:password WHERE id=:id');
        return $stmt->execute([':password'=>password_hash($password, PASSWORD_BCRYPT), ':id'=>(int)$id]);
    }
}
