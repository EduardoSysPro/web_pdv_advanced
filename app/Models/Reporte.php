<?php

require_once CORE_PATH . 'Controller.php';

class Reporte extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerResumenGeneral($fechaInicio, $fechaFin, $vendedorId = 0, $cajaId = 0)
    {
        // La tabla derivada se restringe a las ventas del rango (evita agrupar
        // todo el histórico de detalle_ventas en cada reporte).
        $condiciones = ['v.fecha_venta BETWEEN :inicio AND :fin'];
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
        if ((int)$vendedorId > 0) {
            $condiciones[] = 'v.usuario_id = :vendedor';
            $params[':vendedor'] = (int)$vendedorId;
        }
        if ((int)$cajaId > 0 && $this->columnaExiste('ventas', 'caja_id')) {
            $condiciones[] = 'v.caja_id = :caja';
            $params[':caja'] = (int)$cajaId;
        }
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(v.total),0) AS total_ventas,
                COALESCE(SUM(d.costo_total),0) AS total_costo, COUNT(DISTINCT v.id) AS transacciones
                FROM ventas v LEFT JOIN (SELECT venta_id, SUM(cantidad * COALESCE(factor_unidades, 1.000) * p.precio_costo) AS costo_total FROM detalle_ventas d INNER JOIN productos p ON p.id=d.producto_id WHERE d.venta_id IN (SELECT id FROM ventas WHERE fecha_venta BETWEEN :inicio2 AND :fin2) GROUP BY venta_id) d ON d.venta_id=v.id
                WHERE ' . implode(' AND ', $condiciones));
        $stmt->execute($params + [':inicio2' => $fechaInicio, ':fin2' => $fechaFin]);
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
        $resumen['ganancia_neta'] = (float)$resumen['total_ventas'] - (float)$resumen['total_costo'];
        $resumen['promedio_ticket'] = $resumen['transacciones'] ? (float)$resumen['total_ventas'] / $resumen['transacciones'] : 0;
        return $resumen;
    }

    public function obtenerVentasPorDia($fechaInicio, $fechaFin, $vendedorId = 0, $cajaId = 0)
    {
        $condiciones = ['v.fecha_venta BETWEEN :inicio AND :fin'];
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
        if ((int)$vendedorId > 0) {
            $condiciones[] = 'v.usuario_id = :vendedor';
            $params[':vendedor'] = (int)$vendedorId;
        }
        if ((int)$cajaId > 0 && $this->columnaExiste('ventas', 'caja_id')) {
            $condiciones[] = 'v.caja_id = :caja';
            $params[':caja'] = (int)$cajaId;
        }
        $stmt = $this->pdo->prepare('SELECT DATE(v.fecha_venta) AS dia, COALESCE(SUM(v.total),0) AS total, COUNT(*) AS tickets
                FROM ventas v WHERE ' . implode(' AND ', $condiciones) . ' GROUP BY DATE(v.fecha_venta) ORDER BY dia ASC');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTopProductos($fechaInicio, $fechaFin, $limite = 10, $categoriaId = 0, $vendedorId = 0, $cajaId = 0)
    {
        $condiciones = ['v.fecha_venta BETWEEN :inicio AND :fin'];
        if ((int)$categoriaId > 0) {
            $condiciones[] = 'p.categoria_id = :categoria';
        }
        if ((int)$vendedorId > 0) {
            $condiciones[] = 'v.usuario_id = :vendedor';
        }
        if ((int)$cajaId > 0 && $this->columnaExiste('ventas', 'caja_id')) {
            $condiciones[] = 'v.caja_id = :caja';
        }
        $stmt = $this->pdo->prepare('SELECT p.codigo_barras, p.nombre, SUM(d.cantidad * COALESCE(d.factor_unidades, 1.000)) AS unidades_vendidas, SUM(d.subtotal) AS ingreso_total
                FROM detalle_ventas d INNER JOIN ventas v ON v.id=d.venta_id INNER JOIN productos p ON p.id=d.producto_id
                WHERE ' . implode(' AND ', $condiciones) . ' GROUP BY p.id,p.codigo_barras,p.nombre ORDER BY unidades_vendidas DESC, ingreso_total DESC LIMIT :limite');
        $stmt->bindValue(':inicio',$fechaInicio); $stmt->bindValue(':fin',$fechaFin); $stmt->bindValue(':limite',(int)$limite,PDO::PARAM_INT);
        if ((int)$categoriaId > 0) $stmt->bindValue(':categoria',(int)$categoriaId,PDO::PARAM_INT);
        if ((int)$vendedorId > 0) $stmt->bindValue(':vendedor',(int)$vendedorId,PDO::PARAM_INT);
        if ((int)$cajaId > 0 && $this->columnaExiste('ventas', 'caja_id')) $stmt->bindValue(':caja',(int)$cajaId,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerVentasPorMetodoPago($fechaInicio, $fechaFin, $vendedorId = 0, $cajaId = 0)
    {
        $tienePagos = $this->columnaExiste('ventas', 'pagos');
        $condiciones = ['fecha_venta BETWEEN :inicio AND :fin'];
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
        if ((int)$vendedorId > 0) {
            $condiciones[] = 'usuario_id = :vendedor';
            $params[':vendedor'] = (int)$vendedorId;
        }
        if ((int)$cajaId > 0 && $this->columnaExiste('ventas', 'caja_id')) {
            $condiciones[] = 'caja_id = :caja';
            $params[':caja'] = (int)$cajaId;
        }
        $sql = 'SELECT metodo_pago, COALESCE(SUM(total),0) AS total' . ($tienePagos ? ', pagos' : '') . ' FROM ventas WHERE ' . implode(' AND ', $condiciones) . ' GROUP BY metodo_pago' . ($tienePagos ? ', pagos' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $totales = ['efectivo'=>0,'tarjeta'=>0,'transferencia'=>0,'credito'=>0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if ($fila['metodo_pago'] === 'mixto' && $tienePagos && !empty($fila['pagos'])) {
                $detalle = json_decode($fila['pagos'], true);
                if (is_array($detalle)) {
                    foreach ($detalle as $pago) {
                        $metodo = $pago['metodo'] ?? '';
                        if (isset($totales[$metodo])) $totales[$metodo] += (float)($pago['monto'] ?? 0);
                    }
                    continue;
                }
            }
            if (isset($totales[$fila['metodo_pago']])) $totales[$fila['metodo_pago']] += (float)$fila['total'];
        }
        return $totales;
    }

    public function obtenerVentas($fechaInicio, $fechaFin, $limite = 500, $vendedorId = 0, $cajaId = 0)
    {
        $limite = max(0, (int)$limite);
        $condiciones = ['v.fecha_venta BETWEEN :inicio AND :fin'];
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
        if ((int)$vendedorId > 0) {
            $condiciones[] = 'v.usuario_id = :vendedor';
            $params[':vendedor'] = (int)$vendedorId;
        }
        if ((int)$cajaId > 0 && $this->columnaExiste('ventas', 'caja_id')) {
            $condiciones[] = 'v.caja_id = :caja';
            $params[':caja'] = (int)$cajaId;
        }
        $sql = 'SELECT v.id,v.folio,v.fecha_venta,v.total,v.metodo_pago,u.nombre AS vendedor,COALESCE(c.nombre,\'Consumidor final\') AS cliente FROM ventas v INNER JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN clientes c ON c.id=v.cliente_id WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY v.fecha_venta DESC' . ($limite > 0 ? ' LIMIT ' . $limite : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerReporteCajas($fechaInicio, $fechaFin)
    {
        $tablaTurnos = $this->existeTabla('cajas_turnos') ? 'cajas_turnos' : 'caja_cortes';
        if (!$this->existeTabla($tablaTurnos)) {
            return [];
        }

        $montoApertura = $tablaTurnos === 'cajas_turnos' ? 'monto_apertura' : 'fondo_inicial';
        $montoCierre = $tablaTurnos === 'cajas_turnos' ? 'monto_cierre_real' : 'monto_declarado';
        $unirCajas = $this->existeTabla('cajas') && $this->columnaExiste($tablaTurnos, 'caja_id');
        $sql = "SELECT t.id, t.usuario_id, " . ($this->columnaExiste($tablaTurnos, 'caja_id') ? 't.caja_id' : 'NULL AS caja_id') . ",
                    t.fecha_apertura, t.fecha_cierre, t.{$montoApertura} AS fondo_inicial,
                    t.{$montoCierre} AS monto_cierre, t.diferencia, t.estado,
                    COALESCE(u.nombre, u.usuario, 'Usuario') AS cajero,
                    " . ($unirCajas ? "COALESCE(c.nombre, 'Caja sin asignar')" : "'Caja'" ) . " AS caja_nombre
                FROM {$tablaTurnos} t
                INNER JOIN usuarios u ON u.id = t.usuario_id
                " . ($unirCajas ? 'LEFT JOIN cajas c ON c.id = t.caja_id' : '') . "
                WHERE t.fecha_apertura BETWEEN :inicio AND :fin
                ORDER BY t.fecha_apertura DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$turnos || !$this->existeTabla('caja_movimientos')) {
            return $turnos;
        }

        foreach ($turnos as &$turno) {
            $turno['movimientos'] = [];
            $turno['total_ingresos'] = 0.0;
            $turno['total_egresos'] = 0.0;
        }
        unset($turno);

        $fechaMovimiento = $this->columnaExiste('caja_movimientos', 'fecha_hora') ? 'fecha_hora' : 'fecha';
        $usaTurnoId = $this->columnaExiste('caja_movimientos', 'turno_id');
        $tieneCajaId = $this->columnaExiste('caja_movimientos', 'caja_id');
        $ids = array_column($turnos, 'id');
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT cm.*, {$fechaMovimiento} AS fecha_movimiento, COALESCE(u.nombre, u.usuario, 'Usuario') AS usuario_nombre
                FROM caja_movimientos cm
                LEFT JOIN usuarios u ON u.id = cm.usuario_id
                WHERE cm.tipo IN ('ingreso', 'ingreso_abono', 'egreso')";
        $parametros = [];
        if ($usaTurnoId) {
            $sql .= " AND cm.turno_id IN ({$marcadores})";
            $parametros = $ids;
        } else {
            $sql .= " AND {$fechaMovimiento} BETWEEN ? AND ?";
            $parametros = [$fechaInicio, $fechaFin];
        }
        $sql .= " ORDER BY {$fechaMovimiento} DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        // Índice por turno/usuario para asignar cada movimiento en O(1)
        // en vez del doble bucle O(M*T).
        $porId = [];
        $porUsuario = [];
        foreach ($turnos as $i => $t) {
            $porId[(int)$t['id']] = $i;
            $porUsuario[(int)$t['usuario_id']][] = $i;
        }
        $acumular = function ($i, $movimiento) use (&$turnos) {
            $turnos[$i]['movimientos'][] = $movimiento;
            if (in_array($movimiento['tipo'], ['ingreso', 'ingreso_abono'], true)) {
                $turnos[$i]['total_ingresos'] += (float)$movimiento['monto'];
            } else {
                $turnos[$i]['total_egresos'] += (float)$movimiento['monto'];
            }
        };
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $movimiento) {
            if ($usaTurnoId) {
                $i = $porId[(int)($movimiento['turno_id'] ?? 0)] ?? null;
                if ($i !== null) {
                    $acumular($i, $movimiento);
                }
                continue;
            }
            foreach ($porUsuario[(int)$movimiento['usuario_id']] ?? [] as $i) {
                $turno = $turnos[$i];
                if ((!$tieneCajaId || !$turno['caja_id'] || (int)$movimiento['caja_id'] === (int)$turno['caja_id'])
                    && $movimiento['fecha_movimiento'] >= $turno['fecha_apertura']
                    && $movimiento['fecha_movimiento'] <= ($turno['fecha_cierre'] ?: $fechaFin)) {
                    $acumular($i, $movimiento);
                    break;
                }
            }
        }

        return $turnos;
    }

    private function existeTabla($tabla)
    {
        static $cache = [];
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        if (array_key_exists($tabla, $cache)) return $cache[$tabla];
        $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tabla}'");
        return $cache[$tabla] = ($stmt !== false && $stmt->rowCount() > 0);
    }

    private function columnaExiste($tabla, $columna)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
        static $cache = [];
        $clave = $tabla . '.' . $columna;
        if (array_key_exists($clave, $cache)) return $cache[$clave];
        $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
        return $cache[$clave] = ($stmt !== false && $stmt->rowCount() > 0);
    }
}
