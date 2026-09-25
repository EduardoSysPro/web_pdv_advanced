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
        // Búsqueda unificada: un solo campo para folio, cliente, RTN o ID.
        // Se conservan los parámetros legacy (busqueda/tipo) mapeándolos a los nuevos.
        $q = trim($_GET['q'] ?? '');
        $desde = $this->fechaValida($_GET['desde'] ?? '');
        $hasta = $this->fechaValida($_GET['hasta'] ?? '');
        if ($q === '' && isset($_GET['busqueda'])) {
            $legacy = trim((string)$_GET['busqueda']);
            $tipoLegacy = $_GET['tipo'] ?? '';
            if ($tipoLegacy === 'fecha') {
                $f = $this->parsearFecha($legacy);
                if ($f) {
                    $desde = $f;
                    $hasta = $f;
                }
            } else {
                $q = $legacy;
            }
        }
        $tipoComprobante = $_GET['tipo_comprobante'] ?? 'all';
        if (!in_array($tipoComprobante, ['all', 'recibo', 'factura'], true)) {
            $tipoComprobante = 'all';
        }
        $metodoPago = $_GET['metodo_pago'] ?? 'all';
        if (!in_array($metodoPago, ['all', 'efectivo', 'tarjeta', 'transferencia', 'credito', 'mixto'], true)) {
            $metodoPago = 'all';
        }
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 20;
        $hayFiltros = $q !== '' || $desde !== null || $hasta !== null
            || $tipoComprobante !== 'all' || $metodoPago !== 'all';

        $resultado = $this->buscarComprobantes($q, $tipoComprobante, $metodoPago, $desde, $hasta, $pagina, $porPagina);
        $comprobantes = $resultado['datos'];
        $total = $resultado['total'];
        $paginas = max(1, (int)ceil($total / $porPagina));
        if ($pagina > $paginas) {
            $pagina = $paginas;
            $resultado = $this->buscarComprobantes($q, $tipoComprobante, $metodoPago, $desde, $hasta, $pagina, $porPagina);
            $comprobantes = $resultado['datos'];
        }
        $error = null;
        if ($hayFiltros && empty($comprobantes)) {            $error = 'No se encontraron comprobantes con esa búsqueda.';
        }

        $mensaje = $_SESSION['mensaje_comprobantes'] ?? null;
        unset($_SESSION['mensaje_comprobantes']);
        $filtrosVista = [
            'q' => $q,
            'desde' => $desde ?? '',
            'hasta' => $hasta ?? '',
            'tipo_comprobante' => $tipoComprobante,
            'metodo_pago' => $metodoPago,
            'pagina' => $pagina,
        ];
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
        static $cache = [];
        $clave = $tabla . '.' . $columna;
        if (array_key_exists($clave, $cache)) return $cache[$clave];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND 
TABLE_NAME = :tabla AND COLUMN_NAME = :columna");
        $stmt->execute([':tabla' => $tabla, ':columna' => $columna]);
        return $cache[$clave] = ((int)$stmt->fetchColumn() > 0);
    }

    private function buscarComprobantes($q, $tipoComprobante = 'all', $metodoPago = 'all', $desde = null, $hasta = null, $pagina = 1, $porPagina = 20)
    {
        $pdo = Database::getInstancia()->getConexion();
        $q = trim((string)$q);
        $pagina = max(1, (int)$pagina);
        $porPagina = max(1, min(100, (int)$porPagina));

        $condiciones = ["(v.metodo_pago <> 'credito' OR v.tipo_comprobante <> 'factura' OR COALESCE(c.saldo_pendiente, 0) <= 0)"];
        $parametros = [];
        if (in_array($tipoComprobante, ['recibo', 'factura'], true)) {
            $condiciones[] = 'v.tipo_comprobante = :tipo_comprobante';
            $parametros[':tipo_comprobante'] = $tipoComprobante;
        }
        if (in_array($metodoPago, ['efectivo', 'tarjeta', 'transferencia', 'credito', 'mixto'], true)) {
            $condiciones[] = 'v.metodo_pago = :metodo_pago';
            $parametros[':metodo_pago'] = $metodoPago;
        }
        if ($desde !== null) {
            $condiciones[] = 'v.fecha_venta >= :desde';
            $parametros[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null) {
            $condiciones[] = 'v.fecha_venta <= :hasta';
            $parametros[':hasta'] = $hasta . ' 23:59:59';
        }
        if ($q !== '') {
            // Prefijo primero (usa índice de folio); el resto por coincidencia parcial.
            // OJO: prepares nativos, cada placeholder debe ser único.
            $partes = ['v.folio LIKE :q_pref', 'v.folio LIKE :q_like1',
                'COALESCE(v.cliente_nombre, c.nombre) LIKE :q_like2',
                'c.rtn_identidad LIKE :q_like3', 'v.cliente_rtn LIKE :q_like4'];
            $parametros[':q_pref'] = $q . '%';
            $like = '%' . $q . '%';
            $parametros[':q_like1'] = $like;
            $parametros[':q_like2'] = $like;
            $parametros[':q_like3'] = $like;
            $parametros[':q_like4'] = $like;
            if (ctype_digit($q)) {
                $partes[] = 'v.id = :q_id';
                $parametros[':q_id'] = (int)$q;
            }
            $condiciones[] = '(' . implode(' OR ', $partes) . ')';
        }
        $where = ' WHERE ' . implode(' AND ', $condiciones);

        $sqlCount = 'SELECT COUNT(*) FROM ventas v LEFT JOIN clientes c ON c.id = v.cliente_id' . $where;
        $stmt = $pdo->prepare($sqlCount);
        $stmt->execute($parametros);
        $total = (int)$stmt->fetchColumn();

        $offset = ($pagina - 1) * $porPagina;
        $sql = 'SELECT v.id, v.folio, v.fecha_venta, v.total, v.tipo_comprobante, v.metodo_pago,
                       COALESCE(v.cliente_nombre, c.nombre) AS cliente
                FROM ventas v
                LEFT JOIN clientes c ON c.id = v.cliente_id'
            . $where . ' ORDER BY v.fecha_venta DESC LIMIT ' . $porPagina . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        return ['datos' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total];
    }

    private function fechaValida($fecha)
    {
        $fecha = trim((string)$fecha);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
            return null;
        }
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $fecha : null;
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

}
