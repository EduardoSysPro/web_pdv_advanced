<?php

require_once CORE_PATH . 'Controller.php';

class Venta extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function sumarPorMetodoPago($usuarioId, $desde, $hasta)
    {
        $filtroCaja = $this->columnaExiste('ventas', 'caja_id') ? ' AND caja_id = :caja_id' : '';
        $stmt = $this->pdo->prepare('SELECT metodo_pago, COALESCE(SUM(total), 0) AS total
                                     FROM ventas
                                     WHERE usuario_id = :usuario_id' . $filtroCaja . ' AND fecha_venta >= :desde AND fecha_venta <= :hasta
                                     GROUP BY metodo_pago');
        $parametros = [':usuario_id' => (int)$usuarioId, ':desde' => $desde, ':hasta' => $hasta];
        if ($filtroCaja) $parametros[':caja_id'] = (int)($_SESSION['caja_id'] ?? 0);
        $stmt->execute($parametros);
        $totales = ['efectivo' => 0.0, 'tarjeta' => 0.0, 'transferencia' => 0.0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if (isset($totales[$fila['metodo_pago']])) $totales[$fila['metodo_pago']] = (float)$fila['total'];
        }
        return $totales;
    }

    private function columnaExiste($tabla, $columna)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
        if ($tabla === '' || $columna === '') return false;
        $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
        return $stmt !== false && $stmt->rowCount() > 0;
    }
}