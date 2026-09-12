<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Reporte.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';

class ComprobantesController extends Controller
{
    private $modeloReporte;
    private $modeloConfiguracion;

    public function __construct()
    {
        parent::__construct();
        $this->modeloReporte = new Reporte();
        $this->modeloConfiguracion = new Configuracion();
    }

    public function index()
    {
        $this->requerirAutenticacion();
        $busqueda = trim($_GET['busqueda'] ?? '');
        $tipoBusqueda = $_GET['tipo'] ?? 'folio'; // folio, cliente, fecha
        $tipoComprobante = $_GET['tipo_comprobante'] ?? 'all';
        if (!in_array($tipoComprobante, ['all', 'recibo', 'factura'], true)) {
            $tipoComprobante = 'all';
        }
        $comprobantes = [];
        $error = null;

        if ($busqueda !== '') {
            $comprobantes = $this->buscarComprobantes($busqueda, $tipoBusqueda, $tipoComprobante);
            if (empty($comprobantes)) {
                $error = 'No se encontraron comprobantes con esa búsqueda.';
            }
        } else {
            // Mostrar últimos 50 comprobantes si no hay búsqueda
            $comprobantes = $this->obtenerUltimosComprobantes(50, $tipoComprobante);
        }

        $mensaje = $_SESSION['mensaje_comprobantes'] ?? null;
        unset($_SESSION['mensaje_comprobantes']);
        require APP_PATH . 'Views/comprobantes/index.php';
    }

    public function imprimir($parametros)
    {
        $this->requerirAutenticacion();
        $id = (int)($parametros['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error_comprobantes'] = 'Comprobante no válido.';
            $this->redirigir('comprobantes');
        }

        // Obtener la venta
        $sql = "SELECT v.*, u.nombre AS cajero, c.nombre AS cliente
                FROM ventas v
                LEFT JOIN usuarios u ON u.id = v.usuario_id
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE v.id = :id LIMIT 1";

        $stmt = Database::getInstancia()->getConexion()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            $_SESSION['error_comprobantes'] = 'Comprobante no encontrado.';
            $this->redirigir('comprobantes');
        }

        if (($venta['metodo_pago'] ?? 'efectivo') === 'credito' && (($venta['tipo_comprobante'] ?? 'recibo') === 'factura')) {
            $saldoCliente = 0.0;
            if (!empty($venta['cliente_id'])) {
                $stmtSaldo = Database::getInstancia()->getConexion()->prepare('SELECT saldo_pendiente FROM clientes WHERE id = :id LIMIT 1');
                $stmtSaldo->execute([':id' => (int)$venta['cliente_id']]);
                $saldoCliente = (float)($stmtSaldo->fetchColumn() ?: 0);
            }

            if ($saldoCliente > 0) {
                $_SESSION['error_comprobantes'] = 'La factura a crédito aún no está saldada. No puede imprimirse hasta que el cliente pague el total.';
                $this->redirigir('comprobantes');
            }
        }

        // Obtener detalles de la venta
        $pdo = Database::getInstancia()->getConexion();
        $hasPrecioLista = $this->columnaExiste($pdo, 'detalle_ventas', 'precio_lista');
        $hasDescuentoDetalle = $this->columnaExiste($pdo, 'detalle_ventas', 'descuento_unitario');
        $columnasDescuento = ($hasPrecioLista ? ', dv.precio_lista' : '')
            . ($hasDescuentoDetalle ? ', dv.descuento_unitario' : '');
        $sql = "SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, p.nombre,
                       dv.tipo_presentacion, dv.nombre_presentacion, dv.factor_unidades{$columnasDescuento}
                FROM detalle_ventas dv
                INNER JOIN productos p ON p.id = dv.producto_id
                WHERE dv.venta_id = :venta_id ORDER BY dv.id ASC";

        $stmt = Database::getInstancia()->getConexion()->prepare($sql);
        $stmt->execute([':venta_id' => $id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tipoComprobante = $venta['tipo_comprobante'] ?? 'recibo';
        $configuracion = $this->modeloConfiguracion->obtenerTodas();
        $copiasTicket = max(1, min(5, (int)($_GET['copias'] ?? 2)));

        // Mantener compatibilidad con la vista del ticket
        $venta['items'] = $detalles;

        require APP_PATH . 'Views/ventas/ticket.php';
    }

    private function columnaExiste($pdo, $tabla, $columna)
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna");
        $stmt->execute([':tabla' => $tabla, ':columna' => $columna]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function buscarComprobantes($termino, $tipo, $tipoComprobante = 'all')
    {
        $pdo = Database::getInstancia()->getConexion();
        $termino = trim((string)$termino);

        $condicionCredito = "(v.metodo_pago <> 'credito' OR v.tipo_comprobante <> 'factura' OR COALESCE(c.saldo_pendiente, 0) <= 0)";
        $condicionTipo = '1=1';
        $parametrosTipo = [];
        if (in_array($tipoComprobante, ['recibo', 'factura'], true)) {
            $condicionTipo = 'v.tipo_comprobante = :tipo_comprobante';
            $parametrosTipo[':tipo_comprobante'] = $tipoComprobante;
        }

        if ($tipo === 'folio') {
            $sql = "SELECT v.id, v.folio, v.fecha_venta, v.total, v.tipo_comprobante, v.metodo_pago,
                           COALESCE(v.cliente_nombre, c.nombre) AS cliente
                    FROM ventas v
                    LEFT JOIN clientes c ON c.id = v.cliente_id
                    WHERE v.folio LIKE :termino
                      AND {$condicionCredito}
                                            AND {$condicionTipo}
                    ORDER BY v.fecha_venta DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
                        $stmt->execute(array_merge([':termino' => '%' . $termino . '%'], $parametrosTipo));
        } elseif ($tipo === 'cliente') {
            $sql = "SELECT v.id, v.folio, v.fecha_venta, v.total, v.tipo_comprobante, v.metodo_pago,
                           COALESCE(v.cliente_nombre, c.nombre) AS cliente
                    FROM ventas v
                    LEFT JOIN clientes c ON c.id = v.cliente_id
                    WHERE COALESCE(v.cliente_nombre, c.nombre) LIKE :termino
                      AND {$condicionCredito}
                                            AND {$condicionTipo}
                    ORDER BY v.fecha_venta DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
                        $stmt->execute(array_merge([':termino' => '%' . $termino . '%'], $parametrosTipo));
        } elseif ($tipo === 'fecha') {
            // Intentar parsear la fecha en formato dd/mm/yyyy o yyyy-mm-dd
            $fecha = $this->parsearFecha($termino);
            if (!$fecha) {
                return [];
            }
            $sql = "SELECT v.id, v.folio, v.fecha_venta, v.total, v.tipo_comprobante, v.metodo_pago,
                           COALESCE(v.cliente_nombre, c.nombre) AS cliente
                    FROM ventas v
                    LEFT JOIN clientes c ON c.id = v.cliente_id
                    WHERE DATE(v.fecha_venta) = :fecha
                      AND {$condicionCredito}
                                            AND {$condicionTipo}
                    ORDER BY v.fecha_venta DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
                        $stmt->execute(array_merge([':fecha' => $fecha], $parametrosTipo));
        } else {
            return [];
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function parsearFecha($fechaStr)
    {
        // Intentar formato dd/mm/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fechaStr, $matches)) {
            $dia = (int)$matches[1];
            $mes = (int)$matches[2];
            $anio = (int)$matches[3];
            if ($dia > 0 && $dia <= 31 && $mes > 0 && $mes <= 12 && $anio > 2000) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }
        // Intentar formato yyyy-mm-dd
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fechaStr, $matches)) {
            return $fechaStr;
        }
        return null;
    }

    private function obtenerUltimosComprobantes($limite = 50, $tipoComprobante = 'all')
    {
        $pdo = Database::getInstancia()->getConexion();
        $condicionTipo = '1=1';
        $parametrosTipo = [];
        if (in_array($tipoComprobante, ['recibo', 'factura'], true)) {
            $condicionTipo = 'v.tipo_comprobante = :tipo_comprobante';
            $parametrosTipo[':tipo_comprobante'] = $tipoComprobante;
        }
        $sql = "SELECT v.id, v.folio, v.fecha_venta, v.total, v.tipo_comprobante, v.metodo_pago,
                       COALESCE(v.cliente_nombre, c.nombre) AS cliente
                FROM ventas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE (v.metodo_pago <> 'credito'
                   OR v.tipo_comprobante <> 'factura'
                   OR COALESCE(c.saldo_pendiente, 0) <= 0)
                   AND {$condicionTipo}
                ORDER BY v.fecha_venta DESC LIMIT :limite";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        if (isset($parametrosTipo[':tipo_comprobante'])) {
            $stmt->bindValue(':tipo_comprobante', $parametrosTipo[':tipo_comprobante'], PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
