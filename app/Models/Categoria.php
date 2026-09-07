<?php

require_once CORE_PATH . 'Controller.php';

class Categoria extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerTodas()
    {
        $stmt = $this->pdo->query('SELECT id, nombre, descripcion FROM categorias ORDER BY nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodasConConteo()
    {
        $stmt = $this->pdo->query(
            'SELECT c.id, c.nombre, c.descripcion, COUNT(p.id) AS total_productos
             FROM categorias c
             LEFT JOIN productos p ON p.categoria_id = c.id
             GROUP BY c.id, c.nombre, c.descripcion
             ORDER BY c.nombre ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, descripcion FROM categorias WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar($datos)
    {
        $stmt = $this->pdo->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)');
        $stmt->bindValue(':nombre', trim((string)($datos['nombre'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim((string)($datos['descripcion'] ?? '')) ?: null, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar($id, $datos)
    {
        $stmt = $this->pdo->prepare('UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE id = :id');
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', trim((string)($datos['nombre'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim((string)($datos['descripcion'] ?? '')) ?: null, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM categorias WHERE id = :id');
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function contarProductos($id)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM productos WHERE categoria_id = :id');
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function existeNombre($nombre, $idExcluir = null)
    {
        $sql = 'SELECT COUNT(*) FROM categorias WHERE nombre = :nombre';
        if ($idExcluir !== null) {
            $sql .= ' AND id <> :id_excluir';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':nombre', trim((string)$nombre), PDO::PARAM_STR);
        if ($idExcluir !== null) {
            $stmt->bindValue(':id_excluir', (int)$idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }
}