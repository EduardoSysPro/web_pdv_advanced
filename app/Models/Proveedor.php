<?php

require_once CORE_PATH . 'Controller.php';

class Proveedor extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerTodos($busqueda = '')
    {
        $termino = '%' . $busqueda . '%';
        $stmt = $this->pdo->prepare(
            'SELECT p.*,
                    COALESCE((SELECT SUM(c.saldo_pendiente) FROM compras c WHERE c.proveedor_id = p.id AND c.estado = "recibida"), 0) AS saldo_pendiente
             FROM proveedores p
             WHERE p.nombre LIKE :busqueda OR p.rtn LIKE :busqueda_rtn OR p.contacto LIKE :busqueda_contacto
             ORDER BY p.nombre ASC'
        );
        $stmt->execute([':busqueda' => $termino, ':busqueda_rtn' => $termino, ':busqueda_contacto' => $termino]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM proveedores WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorRtn($rtn)
    {
        $rtn = trim((string)$rtn);
        if ($rtn === '') return null;
        $stmt = $this->pdo->prepare('SELECT * FROM proveedores WHERE rtn = :rtn LIMIT 1');
        $stmt->execute([':rtn' => $rtn]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar($datos)
    {
        $tipo = in_array($datos['tipo'] ?? '', ['contribuyente', 'no_contribuyente'], true) ? $datos['tipo'] : 'contribuyente';
        $stmt = $this->pdo->prepare(
            'INSERT INTO proveedores (nombre, rtn, tipo, telefono, direccion, contacto, correo)
             VALUES (:nombre, :rtn, :tipo, :telefono, :direccion, :contacto, :correo)'
        );
        $ok = $stmt->execute([
            ':nombre'    => trim((string)$datos['nombre']),
            ':rtn'       => trim((string)($datos['rtn'] ?? '')),
            ':tipo'      => $tipo,
            ':telefono'  => trim((string)($datos['telefono'] ?? '')) ?: null,
            ':direccion' => trim((string)($datos['direccion'] ?? '')) ?: null,
            ':contacto'  => trim((string)($datos['contacto'] ?? '')) ?: null,
            ':correo'    => trim((string)($datos['correo'] ?? '')) ?: null,
        ]);
        if (!$ok) {
            return false;
        }
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar($id, $datos)
    {
        $tipo = in_array($datos['tipo'] ?? '', ['contribuyente', 'no_contribuyente'], true) ? $datos['tipo'] : 'contribuyente';
        $stmt = $this->pdo->prepare(
            'UPDATE proveedores SET nombre = :nombre, rtn = :rtn, tipo = :tipo,
                    telefono = :telefono, direccion = :direccion, contacto = :contacto, correo = :correo
             WHERE id = :id'
        );
        return $stmt->execute([
            ':nombre'    => trim((string)$datos['nombre']),
            ':rtn'       => trim((string)($datos['rtn'] ?? '')),
            ':tipo'      => $tipo,
            ':telefono'  => trim((string)($datos['telefono'] ?? '')) ?: null,
            ':direccion' => trim((string)($datos['direccion'] ?? '')) ?: null,
            ':contacto'  => trim((string)($datos['contacto'] ?? '')) ?: null,
            ':correo'    => trim((string)($datos['correo'] ?? '')) ?: null,
            ':id'        => (int)$id,
        ]);
    }

    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM proveedores WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        return $stmt->rowCount() === 1;
    }

    public function rtnExiste($rtn, $idExcluir = null)
    {
        $rtn = trim((string)$rtn);
        if ($rtn === '') return false;
        $sql = 'SELECT COUNT(*) FROM proveedores WHERE rtn = :rtn';
        $params = [':rtn' => $rtn];
        if ($idExcluir !== null) {
            $sql .= ' AND id <> :id_excluir';
            $params[':id_excluir'] = (int)$idExcluir;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function existeId($id)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM proveedores WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}