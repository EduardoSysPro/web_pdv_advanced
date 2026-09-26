<?php

require_once CORE_PATH . 'Controller.php';

class Cliente extends Controller
{
    const DIAS_CREDITO_DEFECTO = 30;

    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    // ================= Esquema (P1-P4) =================

    /**
     * Crea columnas/tablas del módulo de crédito si faltan.
     * Idempotente y seguro de llamar en cada request (solo consulta el
     * diccionario cuando todo existe). NO ejecuta DDL dentro de una
     * transacción activa (el DDL en MySQL hace commit implícito).
     */
    public static function asegurarEsquema($pdo = null)
    {
        if ($pdo === null) {
            $pdo = Database::getInstancia()->getConexion();
        }
        $enTx = method_exists($pdo, 'inTransaction') && $pdo->inTransaction();

        $col = function ($tabla, $columna, $definicion) use ($pdo, $enTx) {
            if (self::columnaExiste($pdo, $tabla, $columna)) {
                return;
            }
            if ($enTx) {
                return;
            }
            try {
                $pdo->exec("ALTER TABLE `{$tabla}` ADD COLUMN {$definicion}");
            } catch (Throwable $e) {
                // 1060 = otro request la creó en paralelo; se ignora.
            }
        };

        $col('clientes', 'dias_credito', "dias_credito INT NOT NULL DEFAULT 30 COMMENT 'Días de crédito otorgados (0 = contado)'");
        $col('clientes', 'tipo', "tipo ENUM('minorista','mayorista') NOT NULL DEFAULT 'minorista' COMMENT 'Categoría: minorista (detalle) o mayorista'");
        $col('ventas', 'fecha_vencimiento', "fecha_vencimiento DATE NULL COMMENT 'Vencimiento de la factura a crédito'");
        $col('ventas', 'excede_limite', "excede_limite TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = excedió el límite con autorización'");
        $col('ventas', 'autorizado_por', "autorizado_por INT NULL COMMENT 'Supervisor que autorizó exceder el límite'");
        $col('pagos_clientes', 'estado', "estado ENUM('activo','anulado') NOT NULL DEFAULT 'activo' COMMENT 'activo o anulado (reversado)'");
        $col('pagos_clientes', 'motivo_anulacion', "motivo_anulacion VARCHAR(255) NULL COMMENT 'Motivo de la anulación'");
        $col('pagos_clientes', 'anulado_por', "anulado_por INT NULL COMMENT 'Usuario que anuló el abono'");
        $col('pagos_clientes', 'anulado_en', "anulado_en DATETIME NULL COMMENT 'Fecha y hora de la anulación'");

        if ($enTx) {
            return;
        }
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS abono_aplicaciones (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    pago_id    INT NOT NULL COMMENT 'Abono en pagos_clientes',
                    venta_id   INT NOT NULL COMMENT 'Factura saldada parcial o totalmente',
                    monto      DECIMAL(10,2) NOT NULL COMMENT 'Monto aplicado a esta factura',
                    creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY idx_abono_aplic_pago_venta (pago_id, venta_id),
                    KEY idx_abono_aplic_venta (venta_id),
                    CONSTRAINT fk_abono_aplic_pago FOREIGN KEY (pago_id) REFERENCES pagos_clientes (id) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_abono_aplic_venta FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS credito_auditoria (
                    id              INT AUTO_INCREMENT PRIMARY KEY,
                    cliente_id      INT NULL COMMENT 'Cliente involucrado (si aplica)',
                    usuario_id      INT NOT NULL COMMENT 'Usuario que originó el evento',
                    accion          VARCHAR(40) NOT NULL COMMENT 'venta_excede_limite | abono_anulado | limite_modificado',
                    referencia_tipo VARCHAR(20) NULL COMMENT 'venta | pago | cliente',
                    referencia_id   INT NULL COMMENT 'Id referenciado',
                    detalle         VARCHAR(255) NULL COMMENT 'Descripción legible',
                    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_credito_aud_cliente (cliente_id),
                    KEY idx_credito_aud_usuario (usuario_id),
                    KEY idx_credito_aud_accion (accion),
                    CONSTRAINT fk_credito_aud_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT fk_credito_aud_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {
        }
    }

    private static function columnaExiste($pdo, $tabla, $columna)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
        if ($tabla === '' || $columna === '') {
            return false;
        }
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
            return $stmt !== false && $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function tablaExiste($pdo, $tabla)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        if ($tabla === '') {
            return false;
        }
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tabla}'");
            return $stmt !== false && $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    // ================= Lecturas básicas =================

    /** Normaliza la categoría del cliente. */
    public static function normalizarTipo($tipo)
    {
        return ((string)$tipo === 'mayorista') ? 'mayorista' : 'minorista';
    }

    public function obtenerTodos($busqueda = '', $limite = 200, $pagina = 1, $tipo = '')
    {
        $limite = max(1, min(1000, (int)$limite));
        $pagina = max(1, (int)$pagina);
        $offset = ($pagina - 1) * $limite;
        $tipo = trim((string)$tipo);
        $filtroTipo = ($tipo === 'mayorista' || $tipo === 'minorista') && self::columnaExiste($this->pdo, 'clientes', 'tipo');
        $sql = 'SELECT * FROM clientes WHERE (nombre LIKE :busqueda_nombre OR rtn_identidad LIKE :busqueda_rtn)';
        if ($filtroTipo) {
            $sql .= ' AND tipo = :tipo';
        }
        $sql .= ' ORDER BY nombre LIMIT ' . $limite . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $termino = '%' . $busqueda . '%';
        $stmt->bindValue(':busqueda_nombre', $termino, PDO::PARAM_STR);
        $stmt->bindValue(':busqueda_rtn', $termino, PDO::PARAM_STR);
        if ($filtroTipo) {
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarTodos($busqueda = '', $tipo = '')
    {
        $tipo = trim((string)$tipo);
        $filtroTipo = ($tipo === 'mayorista' || $tipo === 'minorista') && self::columnaExiste($this->pdo, 'clientes', 'tipo');
        $sql = 'SELECT COUNT(*) FROM clientes WHERE (nombre LIKE :busqueda_nombre OR rtn_identidad LIKE :busqueda_rtn)';
        if ($filtroTipo) {
            $sql .= ' AND tipo = :tipo';
        }
        $stmt = $this->pdo->prepare($sql);
        $termino = '%' . $busqueda . '%';
        $stmt->bindValue(':busqueda_nombre', $termino, PDO::PARAM_STR);
        $stmt->bindValue(':busqueda_rtn', $termino, PDO::PARAM_STR);
        if ($filtroTipo) {
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clientes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente && !isset($cliente['dias_credito'])) {
            $cliente['dias_credito'] = self::DIAS_CREDITO_DEFECTO;
        }
        if ($cliente && !isset($cliente['tipo'])) {
            $cliente['tipo'] = 'minorista';
        }
        return $cliente;
    }

    public function insertar($datos)
    {
        $tieneDias = self::columnaExiste($this->pdo, 'clientes', 'dias_credito');
        $tieneTipo = self::columnaExiste($this->pdo, 'clientes', 'tipo');
        $stmt = $this->pdo->prepare('INSERT INTO clientes (rtn_identidad,nombre,telefono,direccion,limite_credito' . ($tieneDias ? ',dias_credito' : '') . ($tieneTipo ? ',tipo' : '') . ') VALUES (:rtn,:nombre,:telefono,:direccion,:limite' . ($tieneDias ? ',:dias' : '') . ($tieneTipo ? ',:tipo' : '') . ')');
        $parametros = [':rtn' => $datos['rtn_identidad'] ?: null, ':nombre' => $datos['nombre'], ':telefono' => $datos['telefono'], ':direccion' => $datos['direccion'], ':limite' => $datos['limite_credito']];
        if ($tieneDias) {
            $parametros[':dias'] = max(0, (int)($datos['dias_credito'] ?? self::DIAS_CREDITO_DEFECTO));
        }
        if ($tieneTipo) {
            $parametros[':tipo'] = self::normalizarTipo($datos['tipo'] ?? 'minorista');
        }
        $ok = $stmt->execute($parametros);
        if (!$ok) {
            return false;
        }
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar($id, $datos)
    {
        $sql = 'UPDATE clientes SET rtn_identidad=:rtn,nombre=:nombre,telefono=:telefono,direccion=:direccion,limite_credito=:limite';
        $parametros = [':rtn' => $datos['rtn_identidad'] ?: null, ':nombre' => $datos['nombre'], ':telefono' => $datos['telefono'], ':direccion' => $datos['direccion'], ':limite' => $datos['limite_credito'], ':id' => (int)$id];
        if (self::columnaExiste($this->pdo, 'clientes', 'dias_credito')) {
            $sql .= ',dias_credito=:dias';
            $parametros[':dias'] = max(0, (int)($datos['dias_credito'] ?? self::DIAS_CREDITO_DEFECTO));
        }
        if (self::columnaExiste($this->pdo, 'clientes', 'tipo')) {
            $sql .= ',tipo=:tipo';
            $parametros[':tipo'] = self::normalizarTipo($datos['tipo'] ?? 'minorista');
        }
        $stmt = $this->pdo->prepare($sql . ' WHERE id=:id');
        return $stmt->execute($parametros);
    }

    // ================= P1: mora y estado de cuenta =================

    /** Días de mora de un vencimiento Y-m-d (<=0 = al día). Función pura. */
    public static function diasMora($vencimientoYmd, $hoyYmd = null)
    {
        if (empty($vencimientoYmd)) {
            return 0;
        }
        $hoy = $hoyYmd ?: date('Y-m-d');
        $d = (int)floor((strtotime($hoy) - strtotime(substr((string)$vencimientoYmd, 0, 10))) / 86400);
        return $d > 0 ? $d : 0;
    }

    /** Clasificación de mora. Función pura. */
    public static function bucketMora($diasMora)
    {
        $diasMora = (int)$diasMora;
        if ($diasMora <= 0) {
            return ['clave' => 'al_dia', 'etiqueta' => 'Vigente'];
        }
        if ($diasMora <= 30) {
            return ['clave' => 'm1_30', 'etiqueta' => '1-30 días'];
        }
        if ($diasMora <= 60) {
            return ['clave' => 'm31_60', 'etiqueta' => '31-60 días'];
        }
        if ($diasMora <= 90) {
            return ['clave' => 'm61_90', 'etiqueta' => '61-90 días'];
        }
        return ['clave' => 'm90', 'etiqueta' => '+90 días'];
    }

    /** Vencimiento = fecha de venta + días de crédito del cliente. Función pura. */
    public static function calcularVencimiento($fechaVenta, $diasCredito)
    {
        $base = strtotime((string)$fechaVenta) ?: time();
        return date('Y-m-d', strtotime('+' . max(0, (int)$diasCredito) . ' days', $base));
    }

    /**
     * Estado de cuenta unificado: un solo movimiento cronológico con saldo
     * corrido, vencimiento y mora por factura, más resumen (vencido,
     * por vencer, mora máxima y cubetas). Los abonos anulan solo si
     * están activos; el saldo final debe conciliar con saldo_pendiente.
     */
    public function obtenerEstadoCuenta($clienteId)
    {
        $clienteId = (int)$clienteId;
        $cliente = $this->obtenerPorId($clienteId);
        if (!$cliente) {
            return ['cliente' => false, 'movimientos' => [], 'facturas' => [], 'abonos' => [], 'resumen' => []];
        }
        $diasCredito = isset($cliente['dias_credito']) ? max(0, (int)$cliente['dias_credito']) : self::DIAS_CREDITO_DEFECTO;
        $tieneVenc = self::columnaExiste($this->pdo, 'ventas', 'fecha_vencimiento');
        $tieneApps = self::tablaExiste($this->pdo, 'abono_aplicaciones');
        $tieneEstadoPago = self::columnaExiste($this->pdo, 'pagos_clientes', 'estado');

        $stmt = $this->pdo->prepare(
            "SELECT v.id, v.folio, v.total, v.fecha_venta" .
            ($tieneVenc ? ", v.fecha_vencimiento" : "") .
            ($tieneApps ? ", COALESCE(ap.aplicado, 0) AS aplicado" : ", 0 AS aplicado") .
            " FROM ventas v" .
            ($tieneApps ? " LEFT JOIN (SELECT venta_id, SUM(monto) AS aplicado FROM abono_aplicaciones GROUP BY venta_id) ap ON ap.venta_id = v.id" : "") .
            " WHERE v.cliente_id = :id AND v.metodo_pago = 'credito' ORDER BY v.fecha_venta ASC, v.id ASC"
        );
        $stmt->execute([':id' => $clienteId]);
        $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.fecha, p.monto, p.forma_pago, p.observacion" .
            ($tieneEstadoPago ? ", p.estado, p.motivo_anulacion" : ", 'activo' AS estado, NULL AS motivo_anulacion") .
            " FROM pagos_clientes p WHERE p.cliente_id = :id" .
            ($tieneEstadoPago ? " AND p.estado = 'activo'" : "") .
            " ORDER BY p.fecha ASC, p.id ASC"
        );
        $stmt->execute([':id' => $clienteId]);
        $abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Detalle de aplicación por abono (qué folios saldó cada abono).
        $aplicacionesPorPago = [];
        if ($tieneApps && $abonos) {
            $idsPagos = implode(',', array_map('intval', array_column($abonos, 'id')));
            if ($idsPagos !== '') {
                foreach ($this->pdo->query(
                    "SELECT a.pago_id, a.monto, v.folio FROM abono_aplicaciones a INNER JOIN ventas v ON v.id = a.venta_id WHERE a.pago_id IN ({$idsPagos}) ORDER BY v.fecha_venta ASC"
                )->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                    $aplicacionesPorPago[(int)$fila['pago_id']][] = $fila;
                }
            }
        }

        $movimientos = [];
        $facturasDetalle = [];
        $totalCargos = 0.0;
        $totalVencido = 0.0;
        $moraMax = 0;
        $cubetas = ['al_dia' => 0.0, 'm1_30' => 0.0, 'm31_60' => 0.0, 'm61_90' => 0.0, 'm90' => 0.0];

        foreach ($facturas as $f) {
            $saldoFactura = round((float)$f['total'] - (float)$f['aplicado'], 2);
            $vencimiento = ($tieneVenc && !empty($f['fecha_vencimiento']))
                ? substr((string)$f['fecha_vencimiento'], 0, 10)
                : self::calcularVencimiento($f['fecha_venta'], $diasCredito);
            $mora = $saldoFactura > 0.005 ? self::diasMora($vencimiento) : 0;
            $bucket = self::bucketMora($mora);
            if ($saldoFactura > 0.005) {
                $cubetas[$bucket['clave']] = round($cubetas[$bucket['clave']] + $saldoFactura, 2);
                if ($mora > 0) {
                    $totalVencido = round($totalVencido + $saldoFactura, 2);
                }
                if ($mora > $moraMax) {
                    $moraMax = $mora;
                }
            }
            $totalCargos = round($totalCargos + (float)$f['total'], 2);
            $movimientos[] = [
                'fecha' => $f['fecha_venta'], 'tipo' => 'cargo', 'folio' => $f['folio'],
                'cargo' => (float)$f['total'], 'abono' => 0.0, 'vencimiento' => $vencimiento,
                'dias_mora' => $mora, 'bucket' => $bucket, 'saldo_factura' => $saldoFactura,
            ];
            $facturasDetalle[] = [
                'id' => (int)$f['id'], 'folio' => $f['folio'], 'fecha' => $f['fecha_venta'],
                'total' => (float)$f['total'], 'aplicado' => round((float)$f['aplicado'], 2),
                'saldo' => $saldoFactura, 'vencimiento' => $vencimiento,
                'dias_mora' => $mora, 'bucket' => $bucket,
            ];
        }

        $totalAbonos = 0.0;
        foreach ($abonos as $a) {
            $totalAbonos = round($totalAbonos + (float)$a['monto'], 2);
            $movimientos[] = [
                'fecha' => $a['fecha'], 'tipo' => 'abono', 'folio' => null,
                'cargo' => 0.0, 'abono' => (float)$a['monto'], 'vencimiento' => null,
                'dias_mora' => 0, 'bucket' => self::bucketMora(0), 'saldo_factura' => 0.0,
                'pago_id' => (int)$a['id'], 'forma_pago' => $a['forma_pago'],
                'observacion' => $a['observacion'],
                'aplicaciones' => $aplicacionesPorPago[(int)$a['id']] ?? [],
            ];
        }

        usort($movimientos, function ($a, $b) {
            $c = strcmp((string)$a['fecha'], (string)$b['fecha']);
            if ($c !== 0) {
                return $c;
            }
            // Ante misma fecha, primero el cargo y luego el abono.
            return $a['tipo'] === $b['tipo'] ? 0 : ($a['tipo'] === 'cargo' ? -1 : 1);
        });
        $corrido = 0.0;
        foreach ($movimientos as &$m) {
            $corrido = round($corrido + $m['cargo'] - $m['abono'], 2);
            $m['saldo_corrido'] = $corrido;
        }
        unset($m);

        $saldo = round((float)$cliente['saldo_pendiente'], 2);
        $limite = round((float)$cliente['limite_credito'], 2);

        return [
            'cliente' => $cliente,
            'movimientos' => $movimientos,
            'facturas' => $facturasDetalle,
            'abonos' => $abonos,
            'resumen' => [
                'cargos' => $totalCargos,
                'abonos' => $totalAbonos,
                'saldo' => $saldo,
                'vencido' => $totalVencido,
                'por_vencer' => round(max(0, $saldo - $totalVencido), 2),
                'mora_max_dias' => $moraMax,
                'cubetas' => $cubetas,
                'disponible' => round($limite - $saldo, 2),
                'dias_credito' => $diasCredito,
            ],
        ];
    }

    /** Saldo abierto de una factura (total menos aplicaciones FIFO). */
    public function saldoVenta($ventaId)
    {
        if (!self::tablaExiste($this->pdo, 'abono_aplicaciones')) {
            $stmt = $this->pdo->prepare('SELECT total FROM ventas WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$ventaId]);
            return round((float)$stmt->fetchColumn(), 2);
        }
        $stmt = $this->pdo->prepare(
            'SELECT v.total - COALESCE(SUM(a.monto), 0) FROM ventas v LEFT JOIN abono_aplicaciones a ON a.venta_id = v.id WHERE v.id = :id GROUP BY v.id'
        );
        $stmt->execute([':id' => (int)$ventaId]);
        $saldo = $stmt->fetchColumn();
        return $saldo === false ? 0.0 : round(max(0, (float)$saldo), 2);
    }

    // ================= P2: abono con aplicación FIFO =================

    public function registrarAbono($clienteId, $monto, $formaPago, $observacion, $usuarioId, $cajaId = 0)
    {
        if ($monto <= 0 || !in_array($formaPago, ['efectivo', 'tarjeta', 'transferencia'], true)) {
            return false;
        }
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT saldo_pendiente FROM clientes WHERE id=:id FOR UPDATE');
            $stmt->execute([':id' => (int)$clienteId]);
            $saldo = $stmt->fetchColumn();
            if ($saldo === false || $monto > (float)$saldo) {
                throw new Exception('El abono supera el saldo pendiente.');
            }
            $stmt = $this->pdo->prepare('INSERT INTO pagos_clientes (cliente_id,usuario_id,monto,forma_pago,observacion) VALUES (:cliente,:usuario,:monto,:forma,:observacion)');
            $stmt->execute([':cliente' => (int)$clienteId, ':usuario' => (int)$usuarioId, ':monto' => $monto, ':forma' => $formaPago, ':observacion' => $observacion]);
            $pagoId = (int)$this->pdo->lastInsertId();
            $stmt = $this->pdo->prepare('UPDATE clientes SET saldo_pendiente=saldo_pendiente-:monto WHERE id=:id');
            $stmt->execute([':monto' => $monto, ':id' => (int)$clienteId]);

            // P2: aplica el abono a las facturas más antiguas primero.
            $this->aplicarAbonoFIFO($clienteId, $pagoId, $monto);

            $this->registrarMovimientoCaja($usuarioId, $cajaId, 'ingreso_abono', $monto, 'Abono cliente ID ' . (int)$clienteId);
            $this->pdo->commit();
            return $pagoId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reparte el monto del abono entre las facturas a crédito con saldo,
     * de la más antigua a la más reciente. Debe llamarse dentro de la
     * transacción del abono (con el cliente ya bloqueado).
     */
    private function aplicarAbonoFIFO($clienteId, $pagoId, $monto)
    {
        if (!self::tablaExiste($this->pdo, 'abono_aplicaciones')) {
            return;
        }
        $stmt = $this->pdo->prepare(
            "SELECT v.id, v.total - COALESCE(SUM(a.monto), 0) AS pendiente
             FROM ventas v LEFT JOIN abono_aplicaciones a ON a.venta_id = v.id
             WHERE v.cliente_id = :id AND v.metodo_pago = 'credito'
             GROUP BY v.id
             HAVING pendiente > 0.005
             ORDER BY MIN(v.fecha_venta) ASC, v.id ASC"
        );
        $stmt->execute([':id' => (int)$clienteId]);
        $restante = round((float)$monto, 2);
        $ins = $this->pdo->prepare('INSERT INTO abono_aplicaciones (pago_id, venta_id, monto) VALUES (:pago, :venta, :monto)');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if ($restante <= 0.005) {
                break;
            }
            $asignar = round(min($restante, (float)$fila['pendiente']), 2);
            if ($asignar <= 0) {
                continue;
            }
            $ins->execute([':pago' => (int)$pagoId, ':venta' => (int)$fila['id'], ':monto' => $asignar]);
            $restante = round($restante - $asignar, 2);
        }
    }

    // ================= P3: anulación de abonos =================

    public function anularAbono($pagoId, $motivo, $usuarioId, $cajaId = 0)
    {
        $motivo = trim((string)$motivo);
        if ($motivo === '') {
            throw new Exception('Debes indicar el motivo de la anulación.');
        }
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, cliente_id, monto' . (self::columnaExiste($this->pdo, 'pagos_clientes', 'estado') ? ', estado' : ", 'activo' AS estado") . ' FROM pagos_clientes WHERE id = :id FOR UPDATE'
            );
            $stmt->execute([':id' => (int)$pagoId]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pago) {
                throw new Exception('Abono no encontrado.');
            }
            if (($pago['estado'] ?? 'activo') !== 'activo') {
                throw new Exception('El abono ya está anulado.');
            }
            $monto = round((float)$pago['monto'], 2);
            $clienteId = (int)$pago['cliente_id'];

            if (self::columnaExiste($this->pdo, 'pagos_clientes', 'estado')) {
                $stmt = $this->pdo->prepare("UPDATE pagos_clientes SET estado = 'anulado', motivo_anulacion = :motivo, anulado_por = :usuario, anulado_en = NOW() WHERE id = :id");
                $stmt->execute([':motivo' => mb_substr($motivo, 0, 255), ':usuario' => (int)$usuarioId, ':id' => (int)$pagoId]);
            }
            if (self::tablaExiste($this->pdo, 'abono_aplicaciones')) {
                $stmt = $this->pdo->prepare('DELETE FROM abono_aplicaciones WHERE pago_id = :id');
                $stmt->execute([':id' => (int)$pagoId]);
            }
            $stmt = $this->pdo->prepare('UPDATE clientes SET saldo_pendiente = saldo_pendiente + :monto WHERE id = :id');
            $stmt->execute([':monto' => $monto, ':id' => $clienteId]);

            $this->registrarMovimientoCaja($usuarioId, $cajaId, 'egreso', $monto, 'Reversión abono #' . (int)$pagoId . ' cliente ID ' . $clienteId);
            $this->auditar($clienteId, $usuarioId, 'abono_anulado', 'pago', (int)$pagoId, 'Anulado abono de L ' . number_format($monto, 2) . '. Motivo: ' . $motivo);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function registrarMovimientoCaja($usuarioId, $cajaId, $tipo, $monto, $concepto)
    {
        $tieneCaja = self::columnaExiste($this->pdo, 'caja_movimientos', 'caja_id');
        $stmt = $this->pdo->prepare(
            'INSERT INTO caja_movimientos (usuario_id' . ($tieneCaja ? ',caja_id' : '') . ',tipo,monto,concepto) VALUES (:usuario' . ($tieneCaja ? ',:caja_id' : '') . ",:tipo,:monto,:concepto)"
        );
        $parametros = [':usuario' => (int)$usuarioId, ':tipo' => $tipo, ':monto' => $monto, ':concepto' => $concepto];
        if ($tieneCaja) {
            $parametros[':caja_id'] = (int)$cajaId > 0 ? (int)$cajaId : null;
        }
        $stmt->execute($parametros);
    }

    // ================= P4: supervisión y auditoría =================

    /**
     * Valida credenciales de un supervisor (admin, distinto del cajero).
     * Retorna el usuario o false.
     */
    public function verificarSupervisor($usuario, $password, $excluirId = 0)
    {
        $usuario = trim((string)$usuario);
        if ($usuario === '' || (string)$password === '') {
            return false;
        }
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Usuario.php';
        $modelo = new Usuario();
        $encontrado = $modelo->validarCredenciales($usuario, $password);
        if (!$encontrado) {
            return false;
        }
        $rol = strtolower((string)($encontrado['rol'] ?? ''));
        if (!in_array($rol, ['admin', 'administrador'], true)) {
            return false;
        }
        if ((int)$encontrado['id'] === (int)$excluirId) {
            return false;
        }
        return $encontrado;
    }

    public function auditar($clienteId, $usuarioId, $accion, $referenciaTipo, $referenciaId, $detalle)
    {
        if (!self::tablaExiste($this->pdo, 'credito_auditoria')) {
            return;
        }
        $stmt = $this->pdo->prepare('INSERT INTO credito_auditoria (cliente_id, usuario_id, accion, referencia_tipo, referencia_id, detalle) VALUES (:cliente, :usuario, :accion, :tipo, :ref, :detalle)');
        $stmt->execute([
            ':cliente' => $clienteId > 0 ? (int)$clienteId : null,
            ':usuario' => (int)$usuarioId,
            ':accion' => substr((string)$accion, 0, 40),
            ':tipo' => $referenciaTipo ? substr((string)$referenciaTipo, 0, 20) : null,
            ':ref' => $referenciaId > 0 ? (int)$referenciaId : null,
            ':detalle' => $detalle ? mb_substr((string)$detalle, 0, 255) : null,
        ]);
    }

    // ================= P5: cartera CxC =================

    /**
     * Cartera de cuentas por cobrar con mora por cliente.
     * $filtro: todos | vencidos | vigentes | sobregirados
     */
    public function obtenerCartera($filtro = 'todos')
    {
        $tieneVenc = self::columnaExiste($this->pdo, 'ventas', 'fecha_vencimiento');
        $tieneApps = self::tablaExiste($this->pdo, 'abono_aplicaciones');
        $tieneDias = self::columnaExiste($this->pdo, 'clientes', 'dias_credito');
        $diasSql = $tieneDias ? 'COALESCE(c.dias_credito, 30)' : '30';
        $vencSql = $tieneVenc
            ? "COALESCE(v.fecha_vencimiento, DATE(v.fecha_venta) + INTERVAL ({$diasSql}) DAY)"
            : "DATE(v.fecha_venta) + INTERVAL ({$diasSql}) DAY";
        $saldoSql = $tieneApps ? 'v.total - COALESCE(ap.aplicado, 0)' : 'v.total';
        $joinApps = $tieneApps
            ? 'LEFT JOIN (SELECT venta_id, SUM(monto) AS aplicado FROM abono_aplicaciones GROUP BY venta_id) ap ON ap.venta_id = v.id'
            : '';

        $filas = $this->pdo->query(
            "SELECT c.id, c.nombre, c.telefono, c.limite_credito, c.saldo_pendiente,
                    COUNT(v.id) AS facturas,
                    ROUND(COALESCE(SUM({$saldoSql}), 0), 2) AS saldo_abierto,
                    ROUND(COALESCE(SUM(CASE WHEN ({$vencSql}) < CURDATE() THEN ({$saldoSql}) ELSE 0 END), 0), 2) AS vencido,
                    COALESCE(MAX(CASE WHEN ({$vencSql}) < CURDATE() THEN DATEDIFF(CURDATE(), ({$vencSql})) ELSE 0 END), 0) AS mora_max,
                    MAX(v.fecha_venta) AS ultima_compra
             FROM clientes c
             LEFT JOIN ventas v ON v.cliente_id = c.id AND v.metodo_pago = 'credito'
             {$joinApps}
             GROUP BY c.id
             HAVING c.saldo_pendiente > 0.005 OR COALESCE(SUM({$saldoSql}), 0) > 0.005
             ORDER BY vencido DESC, saldo_abierto DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $totales = ['clientes' => 0, 'cartera' => 0.0, 'vencido' => 0.0, 'vigente' => 0.0, 'facturas_vencidas' => 0];
        $salida = [];
        foreach ($filas as $f) {
            $saldoAbierto = round((float)$f['saldo_abierto'], 2);
            // El saldo global manda: si difiere del abierto por redondeo/historia, se usa el global.
            $saldo = round((float)$f['saldo_pendiente'], 2);
            if (abs($saldo - $saldoAbierto) < 0.02) {
                $saldo = $saldoAbierto;
            }
            $vencido = round(min((float)$f['vencido'], $saldo), 2);
            $f['saldo'] = $saldo;
            $f['vencido'] = $vencido;
            $f['vigente'] = round(max(0, $saldo - $vencido), 2);
            $f['disponible'] = round((float)$f['limite_credito'] - $saldo, 2);
            $f['bucket'] = self::bucketMora((int)$f['mora_max']);

            $pasa = true;
            if ($filtro === 'vencidos') {
                $pasa = $vencido > 0.005;
            } elseif ($filtro === 'vigentes') {
                $pasa = $vencido <= 0.005;
            } elseif ($filtro === 'sobregirados') {
                $pasa = $f['disponible'] < -0.005;
            }
            if (!$pasa) {
                continue;
            }
            $salida[] = $f;
            $totales['clientes']++;
            $totales['cartera'] = round($totales['cartera'] + $saldo, 2);
            $totales['vencido'] = round($totales['vencido'] + $vencido, 2);
        }
        $totales['vigente'] = round($totales['cartera'] - $totales['vencido'], 2);
        return ['filas' => $salida, 'totales' => $totales, 'filtro' => $filtro];
    }

    /** Mapa cliente_id => vencido para insignias en el listado. */
    public function mapaVencidos()
    {
        try {
            $cartera = $this->obtenerCartera('vencidos');
        } catch (Throwable $e) {
            return [];
        }
        $mapa = [];
        foreach ($cartera['filas'] as $f) {
            $mapa[(int)$f['id']] = (float)$f['vencido'];
        }
        return $mapa;
    }
}
