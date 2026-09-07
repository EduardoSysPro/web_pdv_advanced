<?php

require_once CORE_PATH . 'Controller.php';

class Caja extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerTurnoAbierto($usuarioId)
    {
        $tablaTurnos = $this->obtenerNombreTablaTurnos();
        $cajaId = (int)($_SESSION['caja_id'] ?? 0);
        $filtroCaja = $this->columnaExiste($tablaTurnos, 'caja_id') ? ' AND caja_id = :caja_id' : '';

        if ($tablaTurnos === 'cajas_turnos') {
            $stmt = $this->pdo->prepare("SELECT * FROM cajas_turnos WHERE usuario_id = :usuario_id{$filtroCaja} AND estado = 'abierta' ORDER BY id DESC LIMIT 1");
            $parametros = [':usuario_id' => (int)$usuarioId];
            if ($filtroCaja) $parametros[':caja_id'] = $cajaId;
            $stmt->execute($parametros);
            $turno = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($turno) {
                $turno['fondo_inicial'] = $turno['monto_apertura'] ?? ($turno['fondo_inicial'] ?? 0.00);
                return $turno;
            }
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM caja_cortes WHERE usuario_id = :usuario_id{$filtroCaja} AND estado = 'abierta' AND fecha_apertura >= CURDATE() ORDER BY id DESC LIMIT 1");
        $parametros = [':usuario_id' => (int)$usuarioId];
        if ($filtroCaja) $parametros[':caja_id'] = $cajaId;
        $stmt->execute($parametros);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerCajasDisponibles()
    {
        if (!$this->existeTabla('cajas')) {
            return [['id' => 1, 'nombre' => 'Caja 01']];
        }
        $stmt = $this->pdo->query('SELECT id, nombre FROM cajas WHERE estado = 1 ORDER BY id ASC');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function obtenerCajaActiva($cajaId)
    {
        $cajaId = (int)$cajaId;
        if ($cajaId <= 0) return null;
        if (!$this->existeTabla('cajas')) {
            return $cajaId === 1 ? ['id' => 1, 'nombre' => 'Caja 01'] : null;
        }
        $stmt = $this->pdo->prepare('SELECT id, nombre FROM cajas WHERE id = :id AND estado = 1 LIMIT 1');
        $stmt->execute([':id' => $cajaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function obtenerPrimeraCajaDisponible()
    {
        $cajas = $this->obtenerCajasDisponibles();
        return $cajas[0] ?? null;
    }

    public function registrarApertura($usuarioId, $monto)
    {
        $tablaTurnos = $this->obtenerNombreTablaTurnos();
        $cajaId = (int)($_SESSION['caja_id'] ?? 0);
        $columnaCaja = $this->columnaExiste($tablaTurnos, 'caja_id');

        if ($tablaTurnos === 'cajas_turnos') {
            $sql = "INSERT INTO cajas_turnos (usuario_id" . ($columnaCaja ? ', caja_id' : '') . ", monto_apertura, estado, fecha_apertura) VALUES (:usuario_id" . ($columnaCaja ? ', :caja_id' : '') . ", :monto, 'abierta', NOW())";
            $stmt = $this->pdo->prepare($sql);
            $parametros = [
                ':usuario_id' => (int)$usuarioId,
                ':monto' => number_format((float)$monto, 2, '.', '')
            ];
            if ($columnaCaja) $parametros[':caja_id'] = $cajaId > 0 ? $cajaId : null;
            $stmt->execute($parametros);
        } else {
            $sql = "INSERT INTO caja_cortes (usuario_id" . ($columnaCaja ? ', caja_id' : '') . ", fondo_inicial, estado, fecha_apertura) VALUES (:usuario_id" . ($columnaCaja ? ', :caja_id' : '') . ", :monto, 'abierta', NOW())";
            $stmt = $this->pdo->prepare($sql);
            $parametros = [
                ':usuario_id' => (int)$usuarioId,
                ':monto' => number_format((float)$monto, 2, '.', '')
            ];
            if ($columnaCaja) $parametros[':caja_id'] = $cajaId > 0 ? $cajaId : null;
            $stmt->execute($parametros);
        }

        $turnoId = (int)$this->pdo->lastInsertId();
        $this->registrarMovimientoUsuario((int)$usuarioId, $turnoId, 'apertura', (float)$monto, 'Apertura de caja');

        return $turnoId;
    }

    public function registrarMovimientoUsuario($usuarioId, $turnoId, $tipo, $monto, $concepto)
    {
        $tipo = in_array($tipo, ['ingreso', 'egreso', 'apertura', 'cierre'], true) ? $tipo : 'ingreso';
        $monto = (float)$monto;
        $concepto = trim((string)$concepto);

        if ($monto <= 0 || $concepto === '') {
            return false;
        }

        $campos = [
            'usuario_id' => (int)$usuarioId,
            'tipo' => $tipo,
            'monto' => number_format($monto, 2, '.', ''),
            'concepto' => $concepto,
        ];

        if ($this->columnaExiste('caja_movimientos', 'turno_id') && $turnoId > 0) {
            $campos['turno_id'] = (int)$turnoId;
        }
        if ($this->columnaExiste('caja_movimientos', 'caja_id')) {
            $cajaId = (int)($_SESSION['caja_id'] ?? 0);
            $campos['caja_id'] = $cajaId > 0 ? $cajaId : null;
        }

        $sql = 'INSERT INTO caja_movimientos (' . implode(', ', array_keys($campos)) . ') VALUES (:' . implode(', :', array_keys($campos)) . ')';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($campos);
    }

    public function registrarMovimientoDuranteTurno($usuarioId, $turnoId, $tipo, $monto, $concepto)
    {
        return $this->registrarMovimientoUsuario($usuarioId, $turnoId, $tipo, $monto, $concepto);
    }

    public function obtenerMovimientos($usuarioId, $desde, $hasta)
    {
        $fechaCampo = $this->columnaExiste('caja_movimientos', 'fecha_hora') ? 'fecha_hora' : 'fecha';
        $filtroCaja = $this->columnaExiste('caja_movimientos', 'caja_id') ? ' AND caja_id = :caja_id' : '';
        $sql = "SELECT tipo, COALESCE(SUM(monto), 0) AS total FROM caja_movimientos WHERE usuario_id = :usuario_id{$filtroCaja} AND {$fechaCampo} >= :desde AND {$fechaCampo} <= :hasta AND tipo IN ('ingreso', 'ingreso_abono', 'egreso') AND concepto NOT LIKE 'Venta registrada -%' GROUP BY tipo";
        $stmt = $this->pdo->prepare($sql);
        $parametros = [
            ':usuario_id' => (int)$usuarioId,
            ':desde' => $desde,
            ':hasta' => $hasta,
        ];
        if ($filtroCaja) $parametros[':caja_id'] = (int)($_SESSION['caja_id'] ?? 0);
        $stmt->execute($parametros);

        $totales = ['ingreso' => 0.0, 'egreso' => 0.0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $tipo = $fila['tipo'] === 'ingreso_abono' ? 'ingreso' : $fila['tipo'];
            if (isset($totales[$tipo])) {
                $totales[$tipo] += (float)$fila['total'];
            }
        }

        return $totales;
    }

    public function obtenerMovimientosTurno($turnoId, $turno = null)
    {
        $fechaCampo = $this->columnaExiste('caja_movimientos', 'fecha_hora') ? 'fecha_hora' : 'fecha';
        $sql = "SELECT cm.*, u.usuario AS usuario_nombre
                FROM caja_movimientos cm
                LEFT JOIN usuarios u ON u.id = cm.usuario_id
                WHERE cm.usuario_id = :usuario_id";

        $parametros = ['usuario_id' => (int)($turno['usuario_id'] ?? $_SESSION['id'] ?? 0)];

        if ($this->columnaExiste('caja_movimientos', 'turno_id')) {
            $sql .= ' AND cm.turno_id = :turno_id';
            $parametros['turno_id'] = (int)$turnoId;
        }
        if ($this->columnaExiste('caja_movimientos', 'caja_id')) {
            $sql .= ' AND cm.caja_id = :caja_id';
            $parametros['caja_id'] = (int)($_SESSION['caja_id'] ?? 0);
        }

        $sql .= " AND {$fechaCampo} >= :desde AND {$fechaCampo} <= :hasta ORDER BY {$fechaCampo} DESC";

        $desde = $turno['fecha_apertura'] ?? date('Y-m-d 00:00:00');
        $hasta = date('Y-m-d H:i:s');
        $parametros['desde'] = $desde;
        $parametros['hasta'] = $hasta;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerResumen($usuarioId, $caja, $venta)
    {
        $hasta = date('Y-m-d H:i:s');
        $desde = $caja['fecha_apertura'] ?? date('Y-m-d 00:00:00');
        $movimientos = $this->obtenerMovimientos($usuarioId, $desde, $hasta);
        $ventas = $venta->sumarPorMetodoPago($usuarioId, $desde, $hasta);
        $fondoInicial = isset($caja['monto_apertura']) ? (float)$caja['monto_apertura'] : (float)($caja['fondo_inicial'] ?? 0.0);
        $esperado = $fondoInicial + $ventas['efectivo'] + $movimientos['ingreso'] - $movimientos['egreso'];

        return [
            'caja' => $caja,
            'ventas' => $ventas,
            'movimientos' => $movimientos,
            'efectivo_esperado' => $esperado,
            'fondo_inicial' => $fondoInicial,
        ];
    }

    public function cerrarTurno($usuarioId, $turnoId, $montoDeclarado, $diferencia = null)
    {
        $tablaTurnos = $this->obtenerNombreTablaTurnos();
        $montoDeclarado = (float)$montoDeclarado;
        $diferencia = $diferencia !== null ? (float)$diferencia : null;

        if ($tablaTurnos === 'cajas_turnos') {
            $sql = "UPDATE cajas_turnos SET fecha_cierre = NOW(), monto_cierre_real = :monto_declarado, diferencia = :diferencia, estado = 'cerrada' WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':monto_declarado' => number_format($montoDeclarado, 2, '.', ''),
                ':diferencia' => $diferencia !== null ? number_format($diferencia, 2, '.', '') : null,
                ':id' => (int)$turnoId,
                ':usuario_id' => (int)$usuarioId,
            ]);
        } else {
            $sql = "UPDATE caja_cortes SET fecha_cierre = NOW(), monto_declarado = :monto_declarado, diferencia = :diferencia, estado = 'cerrada' WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':monto_declarado' => number_format($montoDeclarado, 2, '.', ''),
                ':diferencia' => $diferencia !== null ? number_format($diferencia, 2, '.', '') : null,
                ':id' => (int)$turnoId,
                ':usuario_id' => (int)$usuarioId,
            ]);
        }

        return $stmt->rowCount() > 0;
    }

    public function obtenerNombreTablaTurnos()
    {
        return $this->existeTabla('cajas_turnos') ? 'cajas_turnos' : 'caja_cortes';
    }

    private function existeTabla($tabla)
    {
        $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tabla}'");
        return $stmt !== false && $stmt->rowCount() > 0;
    }

    public function obtenerUltimosCortesCerrados($usuarioId, $limite = 5)
    {
        $tablaTurnos = $this->obtenerNombreTablaTurnos();
        $fechaCampo = $tablaTurnos === 'cajas_turnos' ? 'fecha_cierre' : 'fecha_cierre';
        $montoField = $tablaTurnos === 'cajas_turnos' ? 'monto_cierre_real' : 'monto_declarado';

        $sql = "SELECT id, {$fechaCampo} AS fecha_cierre, {$montoField} AS monto FROM {$tablaTurnos} WHERE usuario_id = :usuario_id AND estado = 'cerrada' ORDER BY {$fechaCampo} DESC LIMIT :limite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', (int)$usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function columnaExiste($tabla, $columna)
    {
        $tablaLimpia = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        $columnaLimpia = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);

        if ($tablaLimpia === '' || $columnaLimpia === '') {
            return false;
        }

        $sql = "SHOW COLUMNS FROM `{$tablaLimpia}` LIKE '{$columnaLimpia}'";
        $stmt = $this->pdo->query($sql);

        return $stmt !== false && $stmt->rowCount() > 0;
    }
}