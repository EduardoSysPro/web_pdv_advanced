<?php

require_once CORE_PATH . 'Controller.php';

class Cliente extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerTodos($busqueda = '')
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clientes WHERE nombre LIKE :busqueda_nombre OR rtn_identidad LIKE :busqueda_rtn ORDER BY nombre');
        $termino = '%' . $busqueda . '%';
        $stmt->execute([':busqueda_nombre' => $termino, ':busqueda_rtn' => $termino]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clientes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar($datos)
    {
        $stmt = $this->pdo->prepare('INSERT INTO clientes (rtn_identidad,nombre,telefono,direccion,limite_credito) VALUES (:rtn,:nombre,:telefono,:direccion,:limite)');
        $ok = $stmt->execute([':rtn' => $datos['rtn_identidad'] ?: null, ':nombre' => $datos['nombre'], ':telefono' => $datos['telefono'], ':direccion' => $datos['direccion'], ':limite' => $datos['limite_credito']]);
        if (!$ok) {
            return false;
        }
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar($id, $datos)
    {
        $stmt = $this->pdo->prepare('UPDATE clientes SET rtn_identidad=:rtn,nombre=:nombre,telefono=:telefono,direccion=:direccion,limite_credito=:limite WHERE id=:id');
        return $stmt->execute([':rtn' => $datos['rtn_identidad'] ?: null, ':nombre' => $datos['nombre'], ':telefono' => $datos['telefono'], ':direccion' => $datos['direccion'], ':limite' => $datos['limite_credito'], ':id' => (int)$id]);
    }

    public function obtenerEstadoCuenta($clienteId)
    {
        $cliente = $this->obtenerPorId($clienteId);
        $stmt = $this->pdo->prepare("SELECT id, folio, total, fecha_venta AS fecha, 'compra' AS tipo, total AS cargo, 0 AS abono FROM ventas WHERE cliente_id=:id AND metodo_pago='credito' ORDER BY fecha_venta DESC");
        $stmt->execute([':id' => (int)$clienteId]);
        $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $this->pdo->prepare("SELECT id, fecha, 'abono' AS tipo, 0 AS cargo, monto AS abono, forma_pago, observacion FROM pagos_clientes WHERE cliente_id=:id ORDER BY fecha DESC");
        $stmt->execute([':id' => (int)$clienteId]);
        return ['cliente' => $cliente, 'compras' => $compras, 'abonos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function registrarAbono($clienteId, $monto, $formaPago, $observacion, $usuarioId)
    {
        if ($monto <= 0 || !in_array($formaPago, ['efectivo','tarjeta','transferencia'], true)) return false;
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT saldo_pendiente FROM clientes WHERE id=:id FOR UPDATE');
            $stmt->execute([':id' => (int)$clienteId]);
            $saldo = $stmt->fetchColumn();
            if ($saldo === false || $monto > (float)$saldo) throw new Exception('El abono supera el saldo pendiente.');
            $stmt = $this->pdo->prepare('INSERT INTO pagos_clientes (cliente_id,usuario_id,monto,forma_pago,observacion) VALUES (:cliente,:usuario,:monto,:forma,:observacion)');
            $stmt->execute([':cliente'=>(int)$clienteId, ':usuario'=>(int)$usuarioId, ':monto'=>$monto, ':forma'=>$formaPago, ':observacion'=>$observacion]);
            $pagoId = (int)$this->pdo->lastInsertId();
            $stmt = $this->pdo->prepare('UPDATE clientes SET saldo_pendiente=saldo_pendiente-:monto WHERE id=:id');
            $stmt->execute([':monto'=>$monto, ':id'=>(int)$clienteId]);
            $this->pdo->commit();
            return $pagoId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
