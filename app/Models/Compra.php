<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Proveedor.php';

class Compra extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerSiguienteFolio()
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM compras');
        $consecutivo = (int)$stmt->fetchColumn() + 1;
        return 'COMP-' . str_pad((string)$consecutivo, 6, '0', STR_PAD_LEFT);
    }

    public function obtenerHistorial($busqueda = '', $proveedorId = null, $fechaDesde = '', $fechaHasta = '', $estado = '', $limite = 100)
    {
        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(c.proveedor_nombre LIKE :busqueda OR c.numero_factura LIKE :busqueda_nf OR c.folio LIKE :busqueda_folio)';
            $termino = '%' . $busqueda . '%';
            $parametros[':busqueda'] = $termino;
            $parametros[':busqueda_nf'] = $termino;
            $parametros[':busqueda_folio'] = $termino;
        }
        if (!empty($proveedorId)) {
            $condiciones[] = 'c.proveedor_id = :proveedor_id';
            $parametros[':proveedor_id'] = (int)$proveedorId;
        }
        if ($fechaDesde !== '') {
            $condiciones[] = 'c.fecha_emision >= :fecha_desde';
            $parametros[':fecha_desde'] = $fechaDesde;
        }
        if ($fechaHasta !== '') {
            $condiciones[] = 'c.fecha_emision <= :fecha_hasta';
            $parametros[':fecha_hasta'] = $fechaHasta;
        }
        if (in_array($estado, ['recibida', 'anulada'], true)) {
            $condiciones[] = 'c.estado = :estado';
            $parametros[':estado'] = $estado;
        }

        $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
        $sql = 'SELECT c.*, p.tipo AS proveedor_tipo_catalogo
                FROM compras c
                LEFT JOIN proveedores p ON p.id = c.proveedor_id' . $where . '
                ORDER BY c.fecha_registro DESC, c.id DESC
                LIMIT :limite';
        $stmt = $this->pdo->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nombre AS usuario_nombre
             FROM compras c
             INNER JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => (int)$id]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$compra) return null;

        $stmt = $this->pdo->prepare('SELECT * FROM detalle_compras WHERE compra_id = :id ORDER BY id ASC');
        $stmt->execute([':id' => (int)$id]);
        $compra['detalle'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare('SELECT pp.*, u.nombre AS usuario_nombre FROM pagos_proveedores pp INNER JOIN usuarios u ON u.id = pp.usuario_id WHERE pp.compra_id = :id ORDER BY pp.fecha DESC');
        $stmt->execute([':id' => (int)$id]);
        $compra['pagos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $compra;
    }

    public function obtenerCuentasPorPagar($proveedorId = null, $busqueda = '')
    {
        $condiciones = ['c.estado = "recibida"', 'c.condicion_pago = "credito"', 'c.saldo_pendiente > 0'];
        $parametros = [];
        if (!empty($proveedorId)) {
            $condiciones[] = 'c.proveedor_id = :proveedor_id';
            $parametros[':proveedor_id'] = (int)$proveedorId;
        }
        if ($busqueda !== '') {
            $condiciones[] = '(c.proveedor_nombre LIKE :busqueda OR c.numero_factura LIKE :busqueda_nf OR c.folio LIKE :busqueda_folio)';
            $termino = '%' . $busqueda . '%';
            $parametros[':busqueda'] = $termino;
            $parametros[':busqueda_nf'] = $termino;
            $parametros[':busqueda_folio'] = $termino;
        }
        $where = ' WHERE ' . implode(' AND ', $condiciones);
        $sql = 'SELECT c.*, COALESCE((SELECT SUM(monto) FROM pagos_proveedores pp WHERE pp.compra_id = c.id), 0) AS abonado
                FROM compras c' . $where . '
                ORDER BY COALESCE(c.fecha_vencimiento, c.fecha_emision) ASC, c.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totales = ['total_credito' => 0.0, 'total_abonado' => 0.0, 'total_pendiente' => 0.0, 'vencidas' => 0];
        foreach ($compras as $compra) {
            $totales['total_credito'] += (float)$compra['total'];
            $totales['total_abonado'] += (float)$compra['abonado'];
            $totales['total_pendiente'] += (float)$compra['saldo_pendiente'];
            if ($compra['fecha_vencimiento'] !== null && $compra['fecha_vencimiento'] < date('Y-m-d')) {
                $totales['vencidas']++;
            }
        }

        return ['compras' => $compras, 'totales' => $totales];
    }

    public function registrarCompra(array $datos)
    {
        $usuarioId = (int)($_SESSION['id'] ?? 0);
        if ($usuarioId <= 0) {
            throw new Exception('Sesión de usuario inválida.');
        }

        $sucursal = isset($_SESSION['sucursal_nombre']) ? (string)$_SESSION['sucursal_nombre'] : null;

        $tipoDocumento = in_array($datos['tipo_documento'] ?? '', ['factura_cai', 'recibo', 'nota_credito', 'nota_debito'], true)
            ? $datos['tipo_documento'] : 'factura_cai';
        $numeroFactura = trim((string)($datos['numero_factura'] ?? ''));
        $fechaEmision = trim((string)($datos['fecha_emision'] ?? ''));
        $condicionPago = ($datos['condicion_pago'] ?? 'contado') === 'credito' ? 'credito' : 'contado';
        $diasCredito = max(0, (int)($datos['dias_credito'] ?? 0));
        $descuento = max(0.0, (float)($datos['descuento'] ?? 0));
        $flete = max(0.0, (float)($datos['flete'] ?? 0));
        $prorratear = !empty($datos['prorratear']);
        $documentoReferenciaId = max(0, (int)($datos['documento_referencia_id'] ?? 0));
        $observaciones = trim((string)($datos['observaciones'] ?? ''));

        if ($numeroFactura === '') {
            throw new Exception('El número de factura del proveedor es obligatorio.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEmision)) {
            throw new Exception('La fecha de emisión no es válida.');
        }

        $lineas = $datos['lineas'] ?? [];
        if (!is_array($lineas) || count($lineas) === 0) {
            throw new Exception('Agrega al menos una línea (producto o gasto operativo).');
        }

        $this->pdo->beginTransaction();
        try {
            $proveedorId = (int)($datos['proveedor_id'] ?? 0);
            $proveedorNombre = '';
            $proveedorRtn = trim((string)($datos['proveedor_rtn'] ?? ''));
            $proveedorTipo = ($datos['proveedor_tipo'] ?? 'contribuyente') === 'no_contribuyente' ? 'no_contribuyente' : 'contribuyente';

            if ($proveedorId > 0) {
                $stmt = $this->pdo->prepare('SELECT id, nombre, rtn, tipo FROM proveedores WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => $proveedorId]);
                $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$proveedor) throw new Exception('El proveedor seleccionado no existe.');
                $proveedorNombre = trim((string)($datos['proveedor_nombre'] ?? $proveedor['nombre']));
                $proveedorRtn = trim((string)$proveedor['rtn']);
                $proveedorTipo = $proveedor['tipo'];
            } else {
                $proveedorNombre = trim((string)($datos['proveedor_nombre'] ?? ''));
                if ($proveedorNombre === '') throw new Exception('El nombre del proveedor es obligatorio.');
                if (in_array($tipoDocumento, ['factura_cai', 'recibo'], true) && $proveedorRtn === '') {
                    throw new Exception('El RTN del proveedor es obligatorio en facturas y recibos.');
                }
                $stmt = $this->pdo->prepare('SELECT id, nombre, tipo FROM proveedores WHERE rtn = :rtn LIMIT 1');
                $stmt->execute([':rtn' => $proveedorRtn]);
                $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existente) {
                    $stmt = $this->pdo->prepare('UPDATE proveedores SET nombre = :nombre, tipo = :tipo WHERE id = :id');
                    $stmt->execute([':nombre' => $proveedorNombre, ':tipo' => $proveedorTipo, ':id' => (int)$existente['id']]);
                    $proveedorId = (int)$existente['id'];
                } else {
                    $stmt = $this->pdo->prepare('INSERT INTO proveedores (nombre, rtn, tipo) VALUES (:nombre, :rtn, :tipo)');
                    $stmt->execute([':nombre' => $proveedorNombre, ':rtn' => $proveedorRtn, ':tipo' => $proveedorTipo]);
                    $proveedorId = (int)$this->pdo->lastInsertId();
                }
            }

            // Evitar duplicados de la misma factura del mismo proveedor
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM compras WHERE proveedor_id = :pid AND numero_factura = :nf AND tipo_documento = :td AND estado = "recibida"');
            $stmt->execute([':pid' => $proveedorId, ':nf' => $numeroFactura, ':td' => $tipoDocumento]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('La factura "' . $numeroFactura . '" de este proveedor ya fue registrada.');
            }

            if ($documentoReferenciaId > 0) {
                $stmt = $this->pdo->prepare('SELECT id, folio FROM compras WHERE id = :id AND estado = "recibida" LIMIT 1');
                $stmt->execute([':id' => $documentoReferenciaId]);
                if (!$stmt->fetch()) throw new Exception('El documento de referencia de la nota no existe o está anulado.');
            }

            // Procesar líneas y desglose de ISV (el impuesto del producto se relee de la BD)
            $itemsProcesados = [];
            $importeExento = 0.0;
            $importeExonerado = 0.0;
            $importeGravado15 = 0.0;
            $isv15Total = 0.0;
            $importeGravado18 = 0.0;
            $isv18Total = 0.0;
            $subtotal = 0.0;
            $baseProductos = 0.0;
            $baseGastos = 0.0;

            foreach ($lineas as $linea) {
                $tipoLinea = ($linea['tipo_linea'] ?? 'producto') === 'gasto_operativo' ? 'gasto_operativo' : 'producto';
                $cantidad = max(0.000, (float)($linea['cantidad'] ?? 0));
                $costoUnitario = max(0.0, round((float)($linea['costo_unitario'] ?? 0), 2));

                if ($costoUnitario <= 0) {
                    throw new Exception($tipoLinea === 'gasto_operativo'
                        ? 'El monto de los gastos operativos debe ser mayor a cero.'
                        : 'El costo unitario de los productos debe ser mayor a cero.');
                }

                $base = round($cantidad * $costoUnitario, 2);
                $tipoPresentacion = ($linea['tipo_presentacion'] ?? 'unidad') === 'empaque' ? 'empaque' : 'unidad';
                $nombrePresentacion = trim((string)($linea['nombre_presentacion'] ?? ($tipoPresentacion === 'empaque' ? 'Caja' : 'Unidad')));
                $factorUnidades = max(1.0, (float)($linea['factor_unidades'] ?? 1.0));
                $tipoImpuesto = 'gravado_15';
                $porcentajeIsv = 15.0;
                $productoId = 0;
                $nombreLinea = '';

                if ($tipoLinea === 'producto') {
                    $productoId = (int)($linea['producto_id'] ?? 0);
                    if ($productoId <= 0) throw new Exception('Línea de producto inválida.');
                    if ($cantidad <= 0) throw new Exception('La cantidad de los productos debe ser mayor a cero.');

                    $stmt = $this->pdo->prepare('SELECT nombre, nombre_empaque, unidades_por_empaque, tipo_impuesto, porcentaje_isv, stock FROM productos WHERE id = :id LIMIT 1');
                    $stmt->execute([':id' => $productoId]);
                    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$producto) throw new Exception('Producto no encontrado (ID ' . $productoId . ').');

                    $nombreLinea = trim((string)$producto['nombre']);
                    $tipoImpuesto = in_array($producto['tipo_impuesto'] ?? '', ['exento', 'gravado_15', 'gravado_18', 'exonerado'], true)
                        ? $producto['tipo_impuesto'] : 'gravado_15';
                    $porcentajeIsv = (float)$producto['porcentaje_isv'];

                    if ($tipoPresentacion === 'empaque') {
                        if ($factorUnidades <= 1.0 && (float)$producto['unidades_por_empaque'] > 1.0) {
                            $factorUnidades = (float)$producto['unidades_por_empaque'];
                        }
                        if ($nombrePresentacion === 'Unidad' || $nombrePresentacion === 'Caja') {
                            $nombrePresentacion = !empty($producto['nombre_empaque']) ? $producto['nombre_empaque'] : 'Caja';
                        }
                    }

                    $baseProductos += $base;
                } else {
                    $nombreLinea = trim((string)($linea['nombre'] ?? ''));
                    if ($nombreLinea === '') throw new Exception('El concepto del gasto operativo es obligatorio.');
                    $tipoImpuesto = in_array($linea['tipo_impuesto'] ?? '', ['exento', 'gravado_15', 'gravado_18', 'exonerado'], true)
                        ? $linea['tipo_impuesto'] : 'gravado_15';
                    $porcentajeIsv = round((float)($linea['porcentaje_isv'] ?? 0), 2);
                    $baseGastos += $base;
                }

                $montoIsv = $porcentajeIsv > 0 ? round($base * ($porcentajeIsv / 100), 2) : 0.0;

                if ($tipoImpuesto === 'exento') {
                    $importeExento += $base;
                } elseif ($tipoImpuesto === 'exonerado') {
                    $importeExonerado += $base;
                } elseif ($porcentajeIsv >= 18) {
                    $importeGravado18 += $base;
                    $isv18Total += $montoIsv;
                } else {
                    $importeGravado15 += $base;
                    $isv15Total += $montoIsv;
                }

                $subtotal += $base;

                $itemsProcesados[] = [
                    'tipo_linea'           => $tipoLinea,
                    'producto_id'          => $productoId,
                    'nombre'               => $nombreLinea,
                    'cantidad'             => $cantidad,
                    'costo_unitario'       => $costoUnitario,
                    'costo_ingreso'        => null,
                    'tipo_presentacion'    => $tipoPresentacion,
                    'nombre_presentacion'  => $nombrePresentacion,
                    'factor_unidades'      => $factorUnidades,
                    'tipo_impuesto'        => $tipoImpuesto,
                    'porcentaje_isv'       => $porcentajeIsv,
                    'monto_isv'            => $montoIsv,
                    'base'                 => $base,
                ];
            }

            $subtotal = round($subtotal, 2);
            $descuento = min($descuento, $subtotal);
            $isvTotal = round($isv15Total + $isv18Total, 2);
            $gastosOperativos = round($baseGastos, 2);
            $total = round($subtotal - $descuento + $isvTotal + $flete, 2);

            if ($total <= 0) {
                throw new Exception('El total de la compra no es válido.');
            }

            $isvAcreditable = ($tipoDocumento === 'factura_cai' && $proveedorTipo === 'contribuyente') ? 1 : 0;

            // Prorrateo de flete y gastos operativos al costo de ingreso de cada producto
            $prorrateoTotal = $flete + $gastosOperativos;
            if ($prorratear && $prorrateoTotal > 0 && $baseProductos > 0) {
                foreach ($itemsProcesados as &$item) {
                    if ($item['tipo_linea'] === 'producto') {
                        $parte = round($item['base'] / $baseProductos * $prorrateoTotal, 2);
                        $item['costo_ingreso'] = round($item['costo_unitario'] + ($item['cantidad'] > 0 ? $parte / $item['cantidad'] : 0), 2);
                    }
                }
                unset($item);
            } else {
                foreach ($itemsProcesados as &$item) {
                    if ($item['tipo_linea'] === 'producto') {
                        $item['costo_ingreso'] = $item['costo_unitario'];
                    }
                }
                unset($item);
            }

            $fechaVencimiento = null;
            if ($condicionPago === 'credito' && $diasCredito > 0) {
                $fechaVencimiento = date('Y-m-d', strtotime($fechaEmision . ' + ' . $diasCredito . ' days'));
            }
            $saldoPendiente = $condicionPago === 'credito' ? $total : 0.0;

            $folio = $this->obtenerSiguienteFolio();

            $stmt = $this->pdo->prepare(
                'INSERT INTO compras
                    (folio, proveedor_id, proveedor_nombre, proveedor_rtn, proveedor_tipo, usuario_id, sucursal,
                     tipo_documento, numero_factura, cai, rango_autorizado, fecha_limite_emision, fecha_emision,
                     condicion_pago, dias_credito, fecha_vencimiento, documento_referencia_id, estado,
                     importe_exento, importe_exonerado, importe_gravado_15, isv_15, importe_gravado_18, isv_18,
                     subtotal, descuento_total, flete, total, saldo_pendiente, isv_acreditable, observaciones)
                 VALUES
                    (:folio, :proveedor_id, :proveedor_nombre, :proveedor_rtn, :proveedor_tipo, :usuario_id, :sucursal,
                     :tipo_documento, :numero_factura, :cai, :rango_autorizado, :fecha_limite_emision, :fecha_emision,
                     :condicion_pago, :dias_credito, :fecha_vencimiento, :documento_referencia_id, "recibida",
                     :importe_exento, :importe_exonerado, :importe_gravado_15, :isv_15, :importe_gravado_18, :isv_18,
                     :subtotal, :descuento_total, :flete, :total, :saldo_pendiente, :isv_acreditable, :observaciones)'
            );
            $stmt->bindValue(':folio', $folio, PDO::PARAM_STR);
            $stmt->bindValue(':proveedor_id', $proveedorId > 0 ? $proveedorId : null, $proveedorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':proveedor_nombre', $proveedorNombre, PDO::PARAM_STR);
            $stmt->bindValue(':proveedor_rtn', $proveedorRtn !== '' ? $proveedorRtn : null, $proveedorRtn !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':proveedor_tipo', $proveedorTipo, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':sucursal', $sucursal, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_documento', $tipoDocumento, PDO::PARAM_STR);
            $stmt->bindValue(':numero_factura', $numeroFactura, PDO::PARAM_STR);
            $stmt->bindValue(':cai', trim((string)($datos['cai'] ?? '')) !== '' ? trim($datos['cai']) : null, !empty($datos['cai']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':rango_autorizado', trim((string)($datos['rango_autorizado'] ?? '')) !== '' ? trim($datos['rango_autorizado']) : null, !empty($datos['rango_autorizado']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fecha_limite_emision', !empty($datos['fecha_limite_emision']) ? $datos['fecha_limite_emision'] : null, !empty($datos['fecha_limite_emision']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fecha_emision', $fechaEmision, PDO::PARAM_STR);
            $stmt->bindValue(':condicion_pago', $condicionPago, PDO::PARAM_STR);
            $stmt->bindValue(':dias_credito', $diasCredito, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_vencimiento', $fechaVencimiento, $fechaVencimiento ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':documento_referencia_id', $documentoReferenciaId > 0 ? $documentoReferenciaId : null, $documentoReferenciaId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':importe_exento', round($importeExento, 2), PDO::PARAM_STR);
            $stmt->bindValue(':importe_exonerado', round($importeExonerado, 2), PDO::PARAM_STR);
            $stmt->bindValue(':importe_gravado_15', round($importeGravado15, 2), PDO::PARAM_STR);
            $stmt->bindValue(':isv_15', round($isv15Total, 2), PDO::PARAM_STR);
            $stmt->bindValue(':importe_gravado_18', round($importeGravado18, 2), PDO::PARAM_STR);
            $stmt->bindValue(':isv_18', round($isv18Total, 2), PDO::PARAM_STR);
            $stmt->bindValue(':subtotal', $subtotal, PDO::PARAM_STR);
            $stmt->bindValue(':descuento_total', $descuento, PDO::PARAM_STR);
            $stmt->bindValue(':flete', $flete, PDO::PARAM_STR);
            $stmt->bindValue(':total', $total, PDO::PARAM_STR);
            $stmt->bindValue(':saldo_pendiente', round($saldoPendiente, 2), PDO::PARAM_STR);
            $stmt->bindValue(':isv_acreditable', $isvAcreditable, PDO::PARAM_INT);
            $stmt->bindValue(':observaciones', $observaciones !== '' ? $observaciones : null, $observaciones !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            $compraId = (int)$this->pdo->lastInsertId();

            $sqlLinea = 'INSERT INTO detalle_compras (compra_id, tipo_linea, producto_id, nombre, cantidad, costo_unitario, costo_ingreso,
                            tipo_presentacion, nombre_presentacion, factor_unidades, tipo_impuesto, porcentaje_isv, monto_isv, subtotal)
                        VALUES (:compra_id, :tipo_linea, :producto_id, :nombre, :cantidad, :costo_unitario, :costo_ingreso,
                            :tipo_presentacion, :nombre_presentacion, :factor_unidades, :tipo_impuesto, :porcentaje_isv, :monto_isv, :subtotal)';
            $stmtLinea = $this->pdo->prepare($sqlLinea);

            $esNotaCredito = $tipoDocumento === 'nota_credito';

            $stmtMov = $this->pdo->prepare('INSERT INTO inventario_movimientos (producto_id, usuario_id, tipo_movimiento, cantidad, motivo) VALUES (?, ?, ?, ?, ?)');

            foreach ($itemsProcesados as $item) {
                $stmtLinea->bindValue(':compra_id', $compraId, PDO::PARAM_INT);
                $stmtLinea->bindValue(':tipo_linea', $item['tipo_linea'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':producto_id', $item['tipo_linea'] === 'producto' ? $item['producto_id'] : null, $item['tipo_linea'] === 'producto' ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmtLinea->bindValue(':nombre', $item['nombre'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':cantidad', $item['cantidad'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':costo_unitario', $item['costo_unitario'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':costo_ingreso', $item['costo_ingreso'] !== null ? $item['costo_ingreso'] : null, $item['costo_ingreso'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmtLinea->bindValue(':tipo_presentacion', $item['tipo_presentacion'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':nombre_presentacion', $item['nombre_presentacion'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':factor_unidades', $item['factor_unidades'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':tipo_impuesto', $item['tipo_impuesto'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':porcentaje_isv', $item['porcentaje_isv'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':monto_isv', $item['monto_isv'], PDO::PARAM_STR);
                $stmtLinea->bindValue(':subtotal', $item['base'], PDO::PARAM_STR);
                $stmtLinea->execute();

                if ($item['tipo_linea'] === 'producto') {
                    $unidades = round($item['cantidad'] * $item['factor_unidades'], 3);
                    $motivo = $esNotaCredito
                        ? 'Nota de crédito ' . $folio . ($documentoReferenciaId > 0 ? ' (ref. compra #' . $documentoReferenciaId . ')' : '')
                        : ($tipoDocumento === 'nota_debito' ? 'Nota de débito ' . $folio : 'Compra ' . $folio);

                    if ($esNotaCredito) {
                        // Devuelve mercadería al proveedor: sale stock, sin tocar el costo promedio
                        $stmt = $this->pdo->prepare('UPDATE productos SET stock = stock - :unidades WHERE id = :id AND stock >= :unidades_min');
                        $stmt->bindValue(':unidades', $unidades, PDO::PARAM_STR);
                        $stmt->bindValue(':unidades_min', $unidades, PDO::PARAM_STR);
                        $stmt->bindValue(':id', $item['producto_id'], PDO::PARAM_INT);
                        $stmt->execute();
                        if ($stmt->rowCount() === 0) {
                            throw new Exception('Stock insuficiente para aplicar la nota de crédito del producto "' . $item['nombre'] . '".');
                        }
                    } else {
                        // Ingreso: actualiza stock y recalcula el costo promedio ponderado
                        $stmt = $this->pdo->prepare('SELECT stock, precio_costo FROM productos WHERE id = :id FOR UPDATE');
                        $stmt->execute([':id' => $item['producto_id']]);
                        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$fila) throw new Exception('Producto no encontrado al actualizar inventario (ID ' . $item['producto_id'] . ').');
                        $stockActual = (float)$fila['stock'];
                        $costoActual = (float)$fila['precio_costo'];
                        $nuevoStock = round($stockActual + $unidades, 3);
                        $nuevoCosto = $nuevoStock > 0
                            ? round(($stockActual * $costoActual + $unidades * $item['costo_ingreso']) / $nuevoStock, 2)
                            : $item['costo_ingreso'];
                        $stmt = $this->pdo->prepare('UPDATE productos SET stock = :stock, precio_costo = :costo WHERE id = :id');
                        $stmt->bindValue(':stock', $nuevoStock, PDO::PARAM_STR);
                        $stmt->bindValue(':costo', $nuevoCosto, PDO::PARAM_STR);
                        $stmt->bindValue(':id', $item['producto_id'], PDO::PARAM_INT);
                        $stmt->execute();
                    }

                    $stmtMov->execute([$item['producto_id'], $usuarioId, $esNotaCredito ? 'salida' : 'entrada', $unidades, $motivo]);
                }
            }

            $this->pdo->commit();

            return [
                'compra_id' => $compraId,
                'folio' => $folio,
                'total' => $total,
                'isv_acreditable' => $isvAcreditable,
                'importe_exento' => round($importeExento, 2),
                'importe_exonerado' => round($importeExonerado, 2),
                'importe_gravado_15' => round($importeGravado15, 2),
                'isv_15' => round($isv15Total, 2),
                'importe_gravado_18' => round($importeGravado18, 2),
                'isv_18' => round($isv18Total, 2),
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function anular($compraId)
    {
        $compraId = (int)$compraId;
        $usuarioId = (int)($_SESSION['id'] ?? 0);
        if ($usuarioId <= 0) throw new Exception('Sesión de usuario inválida.');

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM compras WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $compraId]);
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$compra) throw new Exception('La compra no existe.');
            if ($compra['estado'] !== 'recibida') throw new Exception('La compra ya está anulada.');

            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM pagos_proveedores WHERE compra_id = :id');
            $stmt->execute([':id' => $compraId]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('No se puede anular: esta compra ya tiene pagos registrados.');
            }

            $stmt = $this->pdo->prepare('SELECT * FROM detalle_compras WHERE compra_id = :id AND tipo_linea = "producto"');
            $stmt->execute([':id' => $compraId]);
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $esNotaCredito = $compra['tipo_documento'] === 'nota_credito';
            $stmtMov = $this->pdo->prepare('INSERT INTO inventario_movimientos (producto_id, usuario_id, tipo_movimiento, cantidad, motivo) VALUES (?, ?, ?, ?, ?)');

            foreach ($detalle as $linea) {
                $unidades = round((float)$linea['cantidad'] * (float)$linea['factor_unidades'], 3);
                // Original entrada -> ahora sale; original salida (NC) -> ahora entra de nuevo
                $signo = $esNotaCredito ? '+' : '-';
                $condicion = $signo === '-' ? ' AND stock >= :unidades_min' : '';
                $stmt = $this->pdo->prepare('UPDATE productos SET stock = stock ' . $signo . ' :unidades WHERE id = :id' . $condicion);
                $stmt->bindValue(':unidades', $unidades, PDO::PARAM_STR);
                if ($signo === '-') $stmt->bindValue(':unidades_min', $unidades, PDO::PARAM_STR);
                $stmt->bindValue(':id', (int)$linea['producto_id'], PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    throw new Exception('No se pudo revertir el inventario: stock insuficiente para el producto "' . $linea['nombre'] . '".');
                }

                // Revertir la capa de costo promedio que agregó esta compra (cuando aún es la última)
                if (!$esNotaCredito) {
                    $stmt = $this->pdo->prepare('SELECT stock, precio_costo FROM productos WHERE id = :id');
                    $stmt->execute([':id' => (int)$linea['producto_id']]);
                    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stockNuevo = (float)$fila['stock'];
                    $costoActual = (float)$fila['precio_costo'];
                    $stockAntes = $stockNuevo + $unidades;
                    $costoIngreso = (float)$linea['costo_ingreso'];
                    $costoRevertido = $stockNuevo > 0
                        ? max(0.0, round(($costoActual * $stockAntes - $unidades * $costoIngreso) / $stockNuevo, 2))
                        : $costoIngreso;
                    $stmtC = $this->pdo->prepare('UPDATE productos SET precio_costo = :costo WHERE id = :id');
                    $stmtC->bindValue(':costo', $costoRevertido, PDO::PARAM_STR);
                    $stmtC->bindValue(':id', (int)$linea['producto_id'], PDO::PARAM_INT);
                    $stmtC->execute();
                }

                $stmtMov->execute([(int)$linea['producto_id'], $usuarioId, $signo === '+' ? 'entrada' : 'salida', $unidades, 'Anulación compra ' . $compra['folio']]);
            }

            $stmt = $this->pdo->prepare('UPDATE compras SET estado = "anulada", saldo_pendiente = 0 WHERE id = :id');
            $stmt->execute([':id' => $compraId]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function registrarPago($compraId, $monto, $formaPago, $observacion, $usuarioId)
    {
        $compraId = (int)$compraId;
        $monto = max(0.0, round((float)$monto, 2));
        if ($monto <= 0) throw new Exception('El monto del pago debe ser mayor a cero.');
        if (!in_array($formaPago, ['efectivo', 'tarjeta', 'transferencia'], true)) {
            throw new Exception('Forma de pago no válida.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM compras WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $compraId]);
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$compra) throw new Exception('La compra no existe.');
            if ($compra['estado'] !== 'recibida') throw new Exception('La compra está anulada y no admite pagos.');
            if ((float)$compra['saldo_pendiente'] <= 0) throw new Exception('Esta compra ya está saldada.');
            if ($monto > (float)$compra['saldo_pendiente']) {
                throw new Exception('El pago supera el saldo pendiente (L ' . number_format((float)$compra['saldo_pendiente'], 2) . ').');
            }

            $stmt = $this->pdo->prepare('INSERT INTO pagos_proveedores (compra_id, proveedor_id, usuario_id, monto, forma_pago, observacion) VALUES (:compra_id, :proveedor_id, :usuario_id, :monto, :forma_pago, :observacion)');
            $stmt->execute([
                ':compra_id' => $compraId,
                ':proveedor_id' => !empty($compra['proveedor_id']) ? (int)$compra['proveedor_id'] : null,
                ':usuario_id' => (int)$usuarioId,
                ':monto' => $monto,
                ':forma_pago' => $formaPago,
                ':observacion' => $observacion !== '' ? $observacion : null,
            ]);
            $pagoId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('UPDATE compras SET saldo_pendiente = ROUND(saldo_pendiente - :monto, 2) WHERE id = :id');
            $stmt->execute([':monto' => $monto, ':id' => $compraId]);

            // Reflejar la salida de efectivo en caja si el pago es en efectivo
            if ($formaPago === 'efectivo') {
                $tieneCaja = $this->pdo->query("SHOW COLUMNS FROM caja_movimientos LIKE 'caja_id'")->rowCount() > 0;
                $cajaId = (int)($_SESSION['caja_id'] ?? 0);
                $stmtMov = $this->pdo->prepare('INSERT INTO caja_movimientos (usuario_id' . ($tieneCaja ? ', caja_id' : '') . ', tipo, monto, concepto) VALUES (:usuario_id' . ($tieneCaja ? ', :caja_id' : '') . ', "egreso", :monto, :concepto)');
                $params = [':usuario_id' => (int)$usuarioId, ':monto' => $monto, ':concepto' => 'Pago a proveedor - ' . $compra['folio'] . ' (' . $compra['proveedor_nombre'] . ')'];
                if ($tieneCaja) $params[':caja_id'] = $cajaId > 0 ? $cajaId : null;
                $stmtMov->execute($params);
            }

            $this->pdo->commit();
            return $pagoId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}