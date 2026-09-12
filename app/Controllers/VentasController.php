<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Producto.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Cliente.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';

class VentasController extends Controller
{
    private $modeloProducto;
    private $modeloCliente;
    private $modeloConfiguracion;

    public function __construct()
    {
        parent::__construct();
        $this->modeloProducto = new Producto();
        $this->modeloCliente = new Cliente();
        $this->modeloConfiguracion = new Configuracion();
    }

    public function index()
    {
        $this->requerirAutenticacion();
        $nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Cajero');
        $rolUsuario = $_SESSION['rol'] ?? 'cajero';
        $rolEtiqueta = $rolUsuario === 'admin' ? 'Administrador' : ($rolUsuario === 'cajero_movil' ? 'Cajero Móvil' : 'Cajero');
        $rolClase = $rolUsuario === 'admin' ? 'badge-admin' : 'badge-cajero';
        $fechaActual = date('d/m/Y H:i');
        $urlBase = URL_BASE;
        $clientes = $this->modeloCliente->obtenerTodos();
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $impresoraLanActiva = (string)($configuracion['impresora_lan_activa'] ?? '0') === '1' && trim((string)($configuracion['impresora_lan_ip'] ?? '')) !== '';

        require_once __DIR__ . '/../Views/ventas/index.php';
    }

    public function movil()
    {
        $this->requerirAutenticacion();
        $nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Cajero Móvil');
        $rolUsuario = $_SESSION['rol'] ?? 'cajero_movil';
        $cajaNombre = htmlspecialchars($_SESSION['caja_nombre'] ?? 'Caja Móvil');
        $sucursalNombre = htmlspecialchars($_SESSION['sucursal_nombre'] ?? 'Abarrotes Central');
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $clientes = $this->modeloCliente->obtenerTodos();
        $urlBase = URL_BASE;
        $impresoraLanActiva = (string)($configuracion['impresora_lan_activa'] ?? '0') === '1' && trim((string)($configuracion['impresora_lan_ip'] ?? '')) !== '';

        require_once APP_PATH . 'Views' . DIRECTORY_SEPARATOR . 'ventas' . DIRECTORY_SEPARATOR . 'movil.php';
    }

    public function buscarProducto()
    {
        $this->requerirAutenticacion();

        header('Content-Type: application/json; charset=utf-8');

        $codigo = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
        } else {
            $codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
        }

        if (empty($codigo)) {
            echo json_encode([
                'exito' => false,
                'mensaje' => 'Código de barras vacío.'
            ]);
            return;
        }

        $producto = $this->modeloProducto->buscarPorCodigoBarras($codigo);

        if ($producto) {
            $tipoCoincidencia = $producto['tipo_coincidencia'] ?? 'unidad';
            $tipoVenta = $producto['tipo_venta'] ?? 'solo_unidad';
            $nombreEmpaque = !empty($producto['nombre_empaque']) ? $producto['nombre_empaque'] : 'Caja';
            $factor = max(1.0, (float)($producto['unidades_por_empaque'] ?? 1.0));
            $factorTexto = rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.');
            $precioEmpaque = (float)($producto['precio_empaque'] ?? 0);
            $stockBase = (float)$producto['stock'];

            // Determinar si por el código escaneado se debe cargar como empaque
            $esEmpaque = ($tipoCoincidencia === 'empaque') || ($tipoVenta === 'solo_empaque');

            if ($esEmpaque && $precioEmpaque > 0) {
                $stockEmpaques = $factor > 0 ? floor($stockBase / $factor) : 0;
                $payload = [
                    'id'                => (int)$producto['id'],
                    'item_key'          => $producto['id'] . '_empaque',
                    'codigo_barras'     => $producto['codigo_barras_empaque'] ?: $producto['codigo_barras'],
                    'nombre'            => '[' . $nombreEmpaque . ' x' . $factorTexto . '] ' . $producto['nombre'],
                    'nombre_original'   => $producto['nombre'],
                    'precio_venta'      => $precioEmpaque,
                    'precio_costo'      => (float)$producto['precio_costo'] * $factor,
                    'stock'             => (float)$stockEmpaques,
                    'stock_minimo'      => 0,
                    'unidad_medida'     => strtolower($nombreEmpaque),
                    'permite_decimales' => false,
                    'categoria'         => $producto['categoria_nombre'],
                    'tipo_presentacion' => 'empaque',
                    'nombre_presentacion' => $nombreEmpaque,
                    'factor_unidades'   => $factor
                ];
            } else {
                $payload = [
                    'id'                => (int)$producto['id'],
                    'item_key'          => $producto['id'] . '_unidad',
                    'codigo_barras'     => $producto['codigo_barras'],
                    'nombre'            => ($tipoVenta === 'ambos' ? '[Unidad] ' : '') . $producto['nombre'],
                    'nombre_original'   => $producto['nombre'],
                    'precio_venta'      => (float)$producto['precio_venta'],
                    'precio_costo'      => (float)$producto['precio_costo'],
                    'stock'             => $stockBase,
                    'stock_minimo'      => (float)$producto['stock_minimo'],
                    'unidad_medida'     => $producto['unidad_medida'] ?? 'unidad',
                    'permite_decimales' => !empty($producto['permite_decimales']) || (($producto['unidad_medida'] ?? 'unidad') !== 'unidad'),
                    'categoria'         => $producto['categoria_nombre'],
                    'tipo_presentacion' => 'unidad',
                    'nombre_presentacion' => 'Unidad',
                    'factor_unidades'   => 1.0
                ];
            }

            echo json_encode([
                'exito' => true,
                'producto' => $payload
            ]);
        } else {
            echo json_encode([
                'exito' => false,
                'mensaje' => 'Producto no encontrado para el código: ' . htmlspecialchars($codigo)
            ]);
        }
    }

    public function buscarProductosAjax()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        $termino = trim($_GET['q'] ?? $_POST['q'] ?? '');
        echo json_encode($termino === '' ? [] : $this->modeloProducto->buscarProductosAjax($termino));
    }

    /**
     * Devuelve las ventas registradas el día de hoy (para la terminal móvil),
     * filtradas por la caja o el usuario en sesión.
     */
    public function historialHoy()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');

        $pdo = Database::getInstancia()->getConexion();
        $usuarioId = (int)($_SESSION['id'] ?? 0);
        $cajaId = (int)($_SESSION['caja_id'] ?? 0);
        $hasCaja = $this->columnaExiste($pdo, 'ventas', 'caja_id');

        $condiciones = ['DATE(v.fecha_venta) = CURDATE()'];
        $params = [];
        if ($hasCaja && $cajaId > 0) {
            $condiciones[] = 'v.caja_id = :caja_id';
            $params[':caja_id'] = $cajaId;
        } else {
            $condiciones[] = 'v.usuario_id = :usuario_id';
            $params[':usuario_id'] = $usuarioId;
        }

        $sql = "SELECT v.id, v.folio,
                       DATE_FORMAT(v.fecha_venta, '%H:%i') AS hora,
                       v.total, v.metodo_pago,
                       COALESCE(v.cliente_nombre, c.nombre, 'Consumidor Final') AS cliente
                FROM ventas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE " . implode(' AND ', $condiciones) . "
                ORDER BY v.fecha_venta DESC
                LIMIT 100";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['exito' => true, 'ventas' => $ventas]);
        } catch (Exception $e) {
            echo json_encode(['exito' => false, 'mensaje' => 'No se pudo consultar el historial del día.']);
        }
    }

    /**
     * Busca coincidencias de clientes por RTN/Identidad en la tabla clientes
     * y en el historial previo de ventas registradas.
     */
    public function buscarClientePorRtn()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');

        $rtn = trim($_GET['rtn'] ?? $_POST['rtn'] ?? '');

        if (empty($rtn)) {
            echo json_encode(['exito' => false, 'mensaje' => 'RTN no proporcionado.']);
            return;
        }

        $pdo = Database::getInstancia()->getConexion(); //[cite: 10]

        // 1. Buscar en catálogo de clientes registrados
        $hasIdentidad = $this->columnaExiste($pdo, 'clientes', 'identidad');
        $hasRtnCliente = $this->columnaExiste($pdo, 'clientes', 'rtn');

        $condicionesCliente = [];
        if ($hasRtnCliente) $condicionesCliente[] = 'rtn = :rtn';
        if ($hasIdentidad)  $condicionesCliente[] = 'identidad = :rtn';

        $registrosClientes = [];
        if (!empty($condicionesCliente)) {
            $sqlCliente = 'SELECT id, nombre, ' . ($hasRtnCliente ? 'rtn' : '"" AS rtn') . ', telefono, direccion FROM clientes WHERE ' . implode(' OR ', $condicionesCliente);
            $stmt1 = $pdo->prepare($sqlCliente);
            $stmt1->execute([':rtn' => $rtn]);
            $registrosClientes = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        }

        // 2. Buscar en historial de ventas eventuales
        $hasRtnVenta = $this->columnaExiste($pdo, 'ventas', 'cliente_rtn');
        $registrosVentas = [];
        if ($hasRtnVenta) {
            $stmt2 = $pdo->prepare('SELECT DISTINCT cliente_nombre AS nombre, cliente_rtn AS rtn, cliente_telefono AS telefono, cliente_direccion AS direccion 
                                    FROM ventas 
                                    WHERE cliente_rtn = :rtn AND cliente_nombre IS NOT NULL AND cliente_nombre != ""');
            $stmt2->execute([':rtn' => $rtn]);
            $registrosVentas = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        // Agrupar por nombre exacto para evitar redundancias
        $mapa = [];
        foreach (array_merge($registrosClientes, $registrosVentas) as $row) {
            $key = mb_strtolower(trim($row['nombre']));
            if (!isset($mapa[$key])) {
                $mapa[$key] = [
                    'cliente_id' => $row['id'] ?? null,
                    'nombre'     => $row['nombre'],
                    'rtn'        => $row['rtn'] ?? $rtn,
                    'telefono'   => $row['telefono'] ?? '',
                    'direccion'  => $row['direccion'] ?? ''
                ];
            }
        }

        $coincidencias = array_values($mapa);

        if (count($coincidencias) > 0) {
            echo json_encode([
                'exito' => true,
                'coincidencias' => $coincidencias
            ]);
        } else {
            echo json_encode([
                'exito' => false,
                'mensaje' => 'No se encontraron registros previos para este RTN.'
            ]);
        }
    }

    /**
     * Endpoint que guarda la venta completa en la base de datos
     * mediante una transacción SQL (venta + detalle + stock + movimiento de caja).
     */
    public function guardarVenta()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'exito' => false,
                'mensaje' => 'Método HTTP no permitido.'
            ]);
            return;
        }

        $pdo = Database::getInstancia()->getConexion(); //[cite: 10]

        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);

        if (!is_array($datos)) {
            $datos = [
                'total'            => $_POST['total']            ?? 0,
                'efectivo'         => $_POST['efectivo']         ?? 0,
                'cambio'           => $_POST['cambio']           ?? 0,
                'metodo_pago'      => $_POST['metodo_pago']      ?? 'efectivo',
                'cliente_id'       => $_POST['cliente_id']       ?? 0,
                'cliente_nombre'   => $_POST['cliente_nombre']   ?? '',
                'cliente_rtn'      => $_POST['cliente_rtn']      ?? '',
                'cliente_telefono' => $_POST['cliente_telefono'] ?? '',
                'cliente_direccion'=> $_POST['cliente_direccion']?? '',
                'productos'        => isset($_POST['productos']) && is_array($_POST['productos'])
                    ? $_POST['productos'] : []
            ];
        }

        $total            = 0.0;
        $efectivo         = isset($datos['efectivo'])         ? (float)$datos['efectivo']         : 0;
        $cambio           = isset($datos['cambio'])           ? (float)$datos['cambio']           : 0;
        $metodoPago       = isset($datos['metodo_pago'])      ? trim($datos['metodo_pago'])       : 'efectivo';
        $tipoComprobante  = isset($datos['tipo_comprobante']) ? trim($datos['tipo_comprobante'])  : ($this->modeloConfiguracion->obtenerMapa()['tipo_comprobante_default'] ?? 'recibo');
        $clienteId        = isset($datos['cliente_id'])       ? (int)$datos['cliente_id']        : 0;
        $clienteNombre    = isset($datos['cliente_nombre'])   ? trim($datos['cliente_nombre'])    : '';
        $clienteRtn       = isset($datos['cliente_rtn'])      ? trim($datos['cliente_rtn'])       : '';
        $clienteTelefono  = isset($datos['cliente_telefono']) ? trim($datos['cliente_telefono'])  : '';
        $clienteDireccion = isset($datos['cliente_direccion'])? trim($datos['cliente_direccion']) : '';

        $productos = isset($datos['productos']) && is_array($datos['productos'])
            ? $datos['productos'] : [];

        if (count($productos) === 0) {
            echo json_encode([
                'exito' => false,
                'mensaje' => 'No hay productos en la venta.'
            ]);
            return;
        }

        $metodosValidos = ['efectivo', 'tarjeta', 'transferencia', 'credito'];
        if (!in_array($metodoPago, $metodosValidos, true)) {
            $metodoPago = 'efectivo';
        }

        if (!in_array($tipoComprobante, ['recibo', 'factura'], true)) {
            $tipoComprobante = $this->modeloConfiguracion->obtenerMapa()['tipo_comprobante_default'] ?? 'recibo';
        }

        $usuarioId = (int)($_SESSION['id'] ?? 0);
        if ($usuarioId <= 0) {
            echo json_encode([
                'exito' => false,
                'mensaje' => 'Sesión de usuario inválida.'
            ]);
            return;
        }

        try {
            $pdo->beginTransaction();

            $numeracionFactura = null;
            if ($tipoComprobante === 'factura') {
                $numeracionFactura = $this->generarNumeracionFactura($pdo, $this->modeloConfiguracion->obtenerMapa());
                $folio = $numeracionFactura['folio'];
            } else {
                $folio = $this->generarFolioRecibo($pdo);
            }

            $cajaId = (int)($_SESSION['caja_id'] ?? 0);
            $columnaCajaVenta = $this->columnaExiste($pdo, 'ventas', 'caja_id');
            $hasNombre = $this->columnaExiste($pdo, 'ventas', 'cliente_nombre');
            $hasRtn    = $this->columnaExiste($pdo, 'ventas', 'cliente_rtn');
            $hasTel    = $this->columnaExiste($pdo, 'ventas', 'cliente_telefono');
            $hasDir    = $this->columnaExiste($pdo, 'ventas', 'cliente_direccion');
            $hasCai              = $this->columnaExiste($pdo, 'ventas', 'cai');
            $hasCorrelativoSar   = $this->columnaExiste($pdo, 'ventas', 'correlativo_sar');
            $hasRangoAutorizado  = $this->columnaExiste($pdo, 'ventas', 'rango_autorizado');
            $hasFechaLimite      = $this->columnaExiste($pdo, 'ventas', 'fecha_limite_emision');
            $hasDesgloseIsv      = $this->columnaExiste($pdo, 'ventas', 'importe_gravado_15');
            $hasDescuentoVenta   = $this->columnaExiste($pdo, 'ventas', 'descuento_total');
            $hasProductoImpuesto = $this->columnaExiste($pdo, 'productos', 'tipo_impuesto');
            $hasDetalleIsv       = $this->columnaExiste($pdo, 'detalle_ventas', 'porcentaje_isv');
            $hasPrecioLista      = $this->columnaExiste($pdo, 'detalle_ventas', 'precio_lista');
            $hasDescuentoDetalle = $this->columnaExiste($pdo, 'detalle_ventas', 'descuento_unitario');

            $columnasExtra = '';
            $valoresExtra  = '';
            $ventaTieneTipo = $this->columnaExiste($pdo, 'ventas', 'tipo_comprobante');

            if ($columnaCajaVenta) {
                $columnasExtra .= ', caja_id';
                $valoresExtra  .= ', :caja_id';
            }
            if ($ventaTieneTipo) {
                $columnasExtra .= ', tipo_comprobante';
                $valoresExtra  .= ', :tipo_comprobante';
            }
            if ($hasNombre) {
                $columnasExtra .= ', cliente_nombre';
                $valoresExtra  .= ', :cliente_nombre';
            }
            if ($hasRtn) {
                $columnasExtra .= ', cliente_rtn';
                $valoresExtra  .= ', :cliente_rtn';
            }
            if ($hasTel) {
                $columnasExtra .= ', cliente_telefono';
                $valoresExtra  .= ', :cliente_telefono';
            }
            if ($hasDir) {
                $columnasExtra .= ', cliente_direccion';
                $valoresExtra  .= ', :cliente_direccion';
            }
            if ($hasCai) {
                $columnasExtra .= ', cai';
                $valoresExtra  .= ', :cai';
            }
            if ($hasCorrelativoSar) {
                $columnasExtra .= ', correlativo_sar';
                $valoresExtra  .= ', :correlativo_sar';
            }
            if ($hasRangoAutorizado) {
                $columnasExtra .= ', rango_autorizado';
                $valoresExtra  .= ', :rango_autorizado';
            }
            if ($hasFechaLimite) {
                $columnasExtra .= ', fecha_limite_emision';
                $valoresExtra  .= ', :fecha_limite_emision';
            }
            if ($hasDesgloseIsv) {
                $columnasExtra .= ', importe_exento, importe_exonerado, importe_gravado_15, isv_15, importe_gravado_18, isv_18';
                $valoresExtra  .= ', :importe_exento, :importe_exonerado, :importe_gravado_15, :isv_15, :importe_gravado_18, :isv_18';
            }
            if ($hasDescuentoVenta) {
                $columnasExtra .= ', descuento_total';
                $valoresExtra  .= ', :descuento_total';
            }

            // Primera pasada: releer cada producto desde BD (nunca confiar en el impuesto enviado por el cliente)
            // y calcular el desglose de ISV exento/15%/18% acumulado para la venta.
            $itemsProcesados = [];
            $importeExento = 0.0;
            $importeExonerado = 0.0;
            $importeGravado15 = 0.0;
            $isv15Total = 0.0;
            $importeGravado18 = 0.0;
            $isv18Total = 0.0;
            $descuentoTotal = 0.0;

            foreach ($productos as $item) {
                $productoId       = isset($item['id'])                 ? (int)$item['id']                 : 0;
                $cantidad         = isset($item['cantidad'])           ? (float)$item['cantidad']         : 0.0;
                $precioFinal      = isset($item['precio_unitario'])    ? round((float)$item['precio_unitario'], 2) : 0.0;
                $precioLista      = array_key_exists('precio_lista', $item)
                    ? round((float)$item['precio_lista'], 2)
                    : $precioFinal;
                $descuentoUnitario = array_key_exists('descuento_unitario', $item)
                    ? round((float)$item['descuento_unitario'], 2)
                    : round($precioLista - $precioFinal, 2);

                if ($precioLista < 0 || $precioFinal < 0 || $descuentoUnitario < 0 || $descuentoUnitario > $precioLista) {
                    throw new Exception('El precio o descuento del artículo no es válido.');
                }
                if (abs(round($precioLista - $descuentoUnitario - $precioFinal, 2)) > 0.01) {
                    throw new Exception('El precio final y el descuento del artículo no coinciden.');
                }
                $subtotal         = round($cantidad * $precioFinal, 2);
                $descuentoLinea   = round($cantidad * $descuentoUnitario, 2);
                $tipoPresentacion = ($item['tipo_presentacion'] ?? 'unidad') === 'empaque' ? 'empaque' : 'unidad';
                $nombrePres       = trim((string)($item['nombre_presentacion'] ?? ($tipoPresentacion === 'empaque' ? 'Caja' : 'Unidad')));
                $factorUnidades   = max(1.0, (float)($item['factor_unidades'] ?? 1.0));

                if ($productoId <= 0 || $cantidad <= 0) {
                    throw new Exception('Artículo inválido en el detalle de la venta.');
                }

                $tipoImpuesto = 'gravado_15';
                $porcentajeIsv = 15.0;
                $stmtProd = $pdo->prepare('SELECT nombre_empaque, unidades_por_empaque' . ($hasProductoImpuesto ? ', tipo_impuesto, porcentaje_isv' : '') . ' FROM productos WHERE id = :id LIMIT 1');
                $stmtProd->execute([':id' => $productoId]);
                $prodInfo = $stmtProd->fetch(PDO::FETCH_ASSOC);
                if ($prodInfo) {
                    if ($tipoPresentacion === 'empaque') {
                        if ($factorUnidades <= 1.0 && (float)$prodInfo['unidades_por_empaque'] > 1.0) {
                            $factorUnidades = (float)$prodInfo['unidades_por_empaque'];
                        }
                        if (empty($nombrePres) || $nombrePres === 'Unidad' || $nombrePres === 'Caja') {
                            $nombrePres = !empty($prodInfo['nombre_empaque']) ? $prodInfo['nombre_empaque'] : 'Caja';
                        }
                    }
                    if ($hasProductoImpuesto && !empty($prodInfo['tipo_impuesto'])) {
                        $tipoImpuesto = $prodInfo['tipo_impuesto'];
                        $porcentajeIsv = (float)$prodInfo['porcentaje_isv'];
                    }
                }

                $esExento = $tipoImpuesto === 'exento' ? 1 : 0;
                $esExonerado = $tipoImpuesto === 'exonerado' ? 1 : 0;
                // El precio de venta ya incluye el ISV, por eso se extrae el impuesto del subtotal en vez de sumarlo.
                $montoIsv = $porcentajeIsv > 0 ? round($subtotal - ($subtotal / (1 + $porcentajeIsv / 100)), 2) : 0.0;
                $baseGravada = $subtotal - $montoIsv;

                if ($esExento) {
                    $importeExento += $subtotal;
                } elseif ($esExonerado) {
                    $importeExonerado += $subtotal;
                } elseif ($porcentajeIsv >= 18) {
                    $importeGravado18 += $baseGravada;
                    $isv18Total += $montoIsv;
                } else {
                    $importeGravado15 += $baseGravada;
                    $isv15Total += $montoIsv;
                }

                // Total de unidades base a descontar de inventario
                $unidadesDescontar = $cantidad * $factorUnidades;

                $total += $subtotal;
                $descuentoTotal += $descuentoLinea;
                $itemsProcesados[] = [
                    'item' => $item,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioFinal,
                    'precio_lista' => $precioLista,
                    'descuento_unitario' => $descuentoUnitario,
                    'subtotal' => $subtotal,
                    'tipo_presentacion' => $tipoPresentacion,
                    'nombre_presentacion' => $nombrePres,
                    'factor_unidades' => $factorUnidades,
                    'unidades_descontar' => $unidadesDescontar,
                    'porcentaje_isv' => $porcentajeIsv,
                    'monto_isv' => $montoIsv,
                    'es_exento' => $esExento,
                    'es_exonerado' => $esExonerado
                ];
            }

            $total = round($total, 2);
            $descuentoTotal = round($descuentoTotal, 2);
            if ($total <= 0) {
                throw new Exception('El total de la venta no es válido.');
            }
            if ($metodoPago !== 'credito' && $efectivo < $total) {
                throw new Exception('El efectivo recibido es menor al total.');
            }
            if ($metodoPago === 'credito') {
                if ($clienteId <= 0) throw new Exception('Debes seleccionar un cliente para la venta a crédito.');
                $stmtCredito = $pdo->prepare('SELECT limite_credito, saldo_pendiente FROM clientes WHERE id = :id FOR UPDATE');
                $stmtCredito->execute([':id' => $clienteId]);
                $cliente = $stmtCredito->fetch(PDO::FETCH_ASSOC);
                if (!$cliente || (float)$cliente['saldo_pendiente'] + $total > (float)$cliente['limite_credito']) {
                    throw new Exception('La venta supera el crédito disponible del cliente.');
                }
                $efectivo = 0;
                $cambio = 0;
            } else {
                $cambio = round($efectivo - $total, 2);
            }

            $sqlVenta = 'INSERT INTO ventas (folio, usuario_id' . $columnasExtra . ', total, pagado_con, cambio, metodo_pago, cliente_id, fecha_venta)
                         VALUES (:folio, :usuario_id' . $valoresExtra . ', :total, :pagado_con, :cambio, :metodo_pago, :cliente_id, NOW())';

            $stmtVenta = $pdo->prepare($sqlVenta);
            $stmtVenta->bindValue(':folio',       $folio,     PDO::PARAM_STR);
            $stmtVenta->bindValue(':usuario_id',  $usuarioId, PDO::PARAM_INT);
            if ($columnaCajaVenta) $stmtVenta->bindValue(':caja_id', $cajaId > 0 ? $cajaId : null, $cajaId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            if ($ventaTieneTipo) $stmtVenta->bindValue(':tipo_comprobante', $tipoComprobante, PDO::PARAM_STR);
            if ($hasNombre) $stmtVenta->bindValue(':cliente_nombre', $clienteNombre !== '' ? $clienteNombre : null, $clienteNombre !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasRtn)    $stmtVenta->bindValue(':cliente_rtn',    $clienteRtn !== '' ? $clienteRtn : null, $clienteRtn !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasTel)    $stmtVenta->bindValue(':cliente_telefono', $clienteTelefono !== '' ? $clienteTelefono : null, $clienteTelefono !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasDir)    $stmtVenta->bindValue(':cliente_direccion', $clienteDireccion !== '' ? $clienteDireccion : null, $clienteDireccion !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasCai)             $stmtVenta->bindValue(':cai', $numeracionFactura['cai'] ?? null, $numeracionFactura ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasCorrelativoSar)  $stmtVenta->bindValue(':correlativo_sar', $numeracionFactura['folio'] ?? null, $numeracionFactura ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasRangoAutorizado) $stmtVenta->bindValue(':rango_autorizado', $numeracionFactura['rango_autorizado'] ?? null, $numeracionFactura ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasFechaLimite)     $stmtVenta->bindValue(':fecha_limite_emision', $numeracionFactura['fecha_limite'] ?? null, (!empty($numeracionFactura['fecha_limite'])) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($hasDesgloseIsv) {
                $stmtVenta->bindValue(':importe_exento',     $importeExento,     PDO::PARAM_STR);
                $stmtVenta->bindValue(':importe_exonerado',  $importeExonerado,  PDO::PARAM_STR);
                $stmtVenta->bindValue(':importe_gravado_15', $importeGravado15,  PDO::PARAM_STR);
                $stmtVenta->bindValue(':isv_15',             $isv15Total,        PDO::PARAM_STR);
                $stmtVenta->bindValue(':importe_gravado_18', $importeGravado18,  PDO::PARAM_STR);
                $stmtVenta->bindValue(':isv_18',             $isv18Total,        PDO::PARAM_STR);
            }
            if ($hasDescuentoVenta) {
                $stmtVenta->bindValue(':descuento_total', $descuentoTotal, PDO::PARAM_STR);
            }

            $stmtVenta->bindValue(':total',       $total,     PDO::PARAM_STR);
            $stmtVenta->bindValue(':pagado_con',  $efectivo,  PDO::PARAM_STR);
            $stmtVenta->bindValue(':cambio',      $cambio,    PDO::PARAM_STR);
            $stmtVenta->bindValue(':metodo_pago', $metodoPago, PDO::PARAM_STR);
            $stmtVenta->bindValue(':cliente_id', $metodoPago === 'credito' || $clienteId > 0 ? $clienteId : null, ($metodoPago === 'credito' || $clienteId > 0) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtVenta->execute();
            $ventaId = (int)$pdo->lastInsertId();

                $sqlDetalle = 'INSERT INTO detalle_ventas
                                        (venta_id, producto_id, cantidad, precio_unitario' . ($hasPrecioLista ? ', precio_lista' : '') . ($hasDescuentoDetalle ? ', descuento_unitario' : '') . ', subtotal, tipo_presentacion, nombre_presentacion, factor_unidades' . ($hasDetalleIsv ? ', porcentaje_isv, monto_isv, es_exento, es_exonerado' : '') . ')
                           VALUES
                                        (:venta_id, :producto_id, :cantidad, :precio_unitario' . ($hasPrecioLista ? ', :precio_lista' : '') . ($hasDescuentoDetalle ? ', :descuento_unitario' : '') . ', :subtotal, :tipo_presentacion, :nombre_presentacion, :factor_unidades' . ($hasDetalleIsv ? ', :porcentaje_isv, :monto_isv, :es_exento, :es_exonerado' : '') . ')';
            $stmtDetalle = $pdo->prepare($sqlDetalle);

            $sqlActualizaStock = 'UPDATE productos
                                     SET stock = stock - :cantidad_decimal
                                   WHERE id = :producto_id
                                     AND stock >= :cantidad_limite';
            $stmtStock = $pdo->prepare($sqlActualizaStock);

            foreach ($itemsProcesados as $procesado) {
                $item              = $procesado['item'];
                $productoId        = $procesado['producto_id'];
                $unidadesDescontar = $procesado['unidades_descontar'];

                $stmtDetalle->bindValue(':venta_id',            $ventaId,                        PDO::PARAM_INT);
                $stmtDetalle->bindValue(':producto_id',         $productoId,                     PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad',            $procesado['cantidad'],          PDO::PARAM_STR);
                $stmtDetalle->bindValue(':precio_unitario',     $procesado['precio_unitario'],   PDO::PARAM_STR);
                if ($hasPrecioLista) $stmtDetalle->bindValue(':precio_lista', $procesado['precio_lista'], PDO::PARAM_STR);
                if ($hasDescuentoDetalle) $stmtDetalle->bindValue(':descuento_unitario', $procesado['descuento_unitario'], PDO::PARAM_STR);
                $stmtDetalle->bindValue(':subtotal',            $procesado['subtotal'],          PDO::PARAM_STR);
                $stmtDetalle->bindValue(':tipo_presentacion',   $procesado['tipo_presentacion'], PDO::PARAM_STR);
                $stmtDetalle->bindValue(':nombre_presentacion', $procesado['nombre_presentacion'], PDO::PARAM_STR);
                $stmtDetalle->bindValue(':factor_unidades',     $procesado['factor_unidades'],   PDO::PARAM_STR);
                if ($hasDetalleIsv) {
                    $stmtDetalle->bindValue(':porcentaje_isv', $procesado['porcentaje_isv'], PDO::PARAM_STR);
                    $stmtDetalle->bindValue(':monto_isv',      $procesado['monto_isv'],      PDO::PARAM_STR);
                    $stmtDetalle->bindValue(':es_exento',      $procesado['es_exento'],       PDO::PARAM_INT);
                    $stmtDetalle->bindValue(':es_exonerado',   $procesado['es_exonerado'],    PDO::PARAM_INT);
                }
                $stmtDetalle->execute();

                if (is_numeric($productoId) && strpos((string)$productoId, 'varios_') !== 0) {
                    $stmtStock->bindValue(':cantidad_decimal', $unidadesDescontar, PDO::PARAM_STR);
                    $stmtStock->bindValue(':producto_id',      $productoId,        PDO::PARAM_INT);
                    $stmtStock->bindValue(':cantidad_limite',  $unidadesDescontar, PDO::PARAM_STR);
                    $stmtStock->execute();
                    if ($stmtStock->rowCount() === 0) {
                        throw new Exception('Stock insuficiente para el producto "' . ($item['nombre'] ?? 'ID ' . $productoId) . '". Requiere ' . $unidadesDescontar . ' unidades.');
                    }
                }
            }

            if ($metodoPago === 'credito') {
                $stmtCredito = $pdo->prepare('UPDATE clientes SET saldo_pendiente = saldo_pendiente + :total WHERE id = :id');
                $stmtCredito->execute([':total' => $total, ':id' => $clienteId]);
            }

            // La secuencia SAR solo avanza tras insertar venta/detalle sin error, dentro de la misma transacción.
            if ($numeracionFactura !== null) {
                $this->modeloConfiguracion->guardar(['sar_correlativo_actual' => $numeracionFactura['correlativo_siguiente']]);
            }

            $columnaCajaMovimiento = $this->columnaExiste($pdo, 'caja_movimientos', 'caja_id');
            $sqlMov = 'INSERT INTO caja_movimientos (usuario_id' . ($columnaCajaMovimiento ? ', caja_id' : '') . ', tipo, monto, concepto, fecha)
                       VALUES (:usuario_id' . ($columnaCajaMovimiento ? ', :caja_id' : '') . ', :tipo, :monto, :concepto, NOW())';
            $stmtMov = $pdo->prepare($sqlMov);
            $concepto = 'Venta registrada - Folio ' . $folio . ' - Método: ' . ucfirst($metodoPago);
            $stmtMov->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            if ($columnaCajaMovimiento) $stmtMov->bindValue(':caja_id', $cajaId > 0 ? $cajaId : null, $cajaId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtMov->bindValue(':tipo',       'ingreso', PDO::PARAM_STR);
            $stmtMov->bindValue(':monto',      $total,    PDO::PARAM_STR);
            $stmtMov->bindValue(':concepto',   $concepto, PDO::PARAM_STR);
            if ($metodoPago === 'efectivo') {
                $stmtMov->execute();
            }

            $pdo->commit();

            echo json_encode([
                'exito'   => true,
                'mensaje' => 'Venta registrada exitosamente.',
                'folio'   => $folio,
                'venta_id'=> $ventaId
            ]);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode([
                'exito'   => false,
                'mensaje' => 'Error al registrar la venta: ' . $e->getMessage()
            ]);
        }
    }

    public function imprimirTicket($parametros)
    {
        $this->requerirAutenticacion();
        $valor = is_array($parametros) ? ($parametros['id'] ?? '') : $parametros;
        $pdo = Database::getInstancia()->getConexion(); //[cite: 10]

        $hasNombre = $this->columnaExiste($pdo, 'ventas', 'cliente_nombre');
        $hasRtn    = $this->columnaExiste($pdo, 'ventas', 'cliente_rtn');
        $hasTel    = $this->columnaExiste($pdo, 'ventas', 'cliente_telefono');
        $hasDir    = $this->columnaExiste($pdo, 'ventas', 'cliente_direccion');
        $hasTipoComprobante = $this->columnaExiste($pdo, 'ventas', 'tipo_comprobante');
        $hasCaiVenta = $this->columnaExiste($pdo, 'ventas', 'cai');
        $hasDesgloseIsvVenta = $this->columnaExiste($pdo, 'ventas', 'importe_gravado_15');
        $hasDescuentoVenta = $this->columnaExiste($pdo, 'ventas', 'descuento_total');

        $colsCliente = '';
        if ($hasTipoComprobante) $colsCliente .= ', v.tipo_comprobante';
        if ($hasNombre) $colsCliente .= ', v.cliente_nombre';
        if ($hasRtn)    $colsCliente .= ', v.cliente_rtn';
        if ($hasTel)    $colsCliente .= ', v.cliente_telefono';
        if ($hasDir)    $colsCliente .= ', v.cliente_direccion';
        if ($hasCaiVenta) $colsCliente .= ', v.cai, v.correlativo_sar, v.rango_autorizado, v.fecha_limite_emision';
        if ($hasDesgloseIsvVenta) $colsCliente .= ', v.importe_exento, v.importe_exonerado, v.importe_gravado_15, v.isv_15, v.importe_gravado_18, v.isv_18';
        if ($hasDescuentoVenta) $colsCliente .= ', v.descuento_total';

        $stmt = $pdo->prepare('SELECT v.id, v.folio, v.total, v.pagado_con, v.cambio, v.metodo_pago, v.fecha_venta,
                                      u.nombre AS cajero, c.nombre AS cliente_registrado' . $colsCliente . '
                               FROM ventas v 
                               INNER JOIN usuarios u ON u.id = v.usuario_id
                               LEFT JOIN clientes c ON c.id = v.cliente_id
                               WHERE v.id = :id OR v.folio = :folio
                               ORDER BY v.id DESC LIMIT 1');
        $stmt->bindValue(':id', ctype_digit((string)$valor) ? (int)$valor : 0, PDO::PARAM_INT);
        $stmt->bindValue(':folio', (string)$valor, PDO::PARAM_STR);
        $stmt->execute();
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            http_response_code(404);
            echo 'Venta no encontrada.';
            return;
        }

        if (($venta['metodo_pago'] ?? 'efectivo') === 'credito' && (($venta['tipo_comprobante'] ?? 'recibo') === 'factura')) {
            $saldoCliente = 0.0;
            if (!empty($venta['cliente_id'])) {
                $stmtSaldo = $pdo->prepare('SELECT saldo_pendiente FROM clientes WHERE id = :id LIMIT 1');
                $stmtSaldo->execute([':id' => (int)$venta['cliente_id']]);
                $saldoCliente = (float)($stmtSaldo->fetchColumn() ?: 0);
            }

            if ($saldoCliente > 0) {
                http_response_code(403);
                echo 'La factura a crédito aún no está saldada. Se emitirá cuando el cliente haya pagado el total.';
                return;
            }
        }

        if (empty($venta['cliente_nombre']) && !empty($venta['cliente_registrado'])) {
            $venta['cliente_nombre'] = $venta['cliente_registrado'];
        }

        $hasTipoPres = $this->columnaExiste($pdo, 'detalle_ventas', 'tipo_presentacion');
        $hasNomPres  = $this->columnaExiste($pdo, 'detalle_ventas', 'nombre_presentacion');
        $hasFactor   = $this->columnaExiste($pdo, 'detalle_ventas', 'factor_unidades');
        $hasDetalleIsvTicket = $this->columnaExiste($pdo, 'detalle_ventas', 'porcentaje_isv');
        $hasPrecioListaTicket = $this->columnaExiste($pdo, 'detalle_ventas', 'precio_lista');
        $hasDescuentoDetalleTicket = $this->columnaExiste($pdo, 'detalle_ventas', 'descuento_unitario');

        $colsDetalle = '';
        if ($hasTipoPres) $colsDetalle .= ', d.tipo_presentacion';
        if ($hasNomPres)  $colsDetalle .= ', d.nombre_presentacion';
        if ($hasFactor)   $colsDetalle .= ', d.factor_unidades';
        if ($hasDetalleIsvTicket) $colsDetalle .= ', d.porcentaje_isv, d.monto_isv, d.es_exento, d.es_exonerado';
        if ($hasPrecioListaTicket) $colsDetalle .= ', d.precio_lista';
        if ($hasDescuentoDetalleTicket) $colsDetalle .= ', d.descuento_unitario';

        $stmt = $pdo->prepare('SELECT d.cantidad, d.precio_unitario, d.subtotal, p.nombre' . $colsDetalle . '
                               FROM detalle_ventas d
                               INNER JOIN productos p ON p.id = d.producto_id
                               WHERE d.venta_id = :venta_id ORDER BY d.id ASC');
        $stmt->bindValue(':venta_id', (int)$venta['id'], PDO::PARAM_INT);
        $stmt->execute();
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $configuracion = array_merge([
            'empresa_nombre' => $configuracion['nombre_negocio'] ?? 'MI TIENDA',
            'empresa_rtn' => $configuracion['rtn'] ?? '',
            'empresa_direccion' => $configuracion['direccion'] ?? '',
            'empresa_telefono' => $configuracion['telefono'] ?? '',
            'empresa_email' => $configuracion['email'] ?? '',
            'cai' => $configuracion['sar_cai'] ?? '',
            'rango_inicial' => $configuracion['sar_rango_inicial'] ?? '',
            'rango_final' => $configuracion['sar_rango_final'] ?? '',
            'fecha_limite' => $configuracion['sar_fecha_limite'] ?? '',
        ], $configuracion);
        $tipoComprobante = isset($_GET['tipo']) ? trim($_GET['tipo']) : ($venta['tipo_comprobante'] ?? ($configuracion['tipo_comprobante_default'] ?? 'recibo'));
        if (!in_array($tipoComprobante, ['recibo', 'factura'], true)) {
            $tipoComprobante = $configuracion['tipo_comprobante_default'] ?? 'recibo';
        }
        require APP_PATH . 'Views/ventas/ticket.php';
    }

    private function generarFolioRecibo($pdo)
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ventas WHERE folio LIKE 'REC-%'");
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $consecutivo = (int)($fila['total'] ?? 0) + 1;
        return 'REC-' . str_pad((string)$consecutivo, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Genera la numeración oficial PPP-EEE-TD-CCCCCCCC y bloquea el correlativo SAR
     * (FOR UPDATE dentro de la transacción activa) para evitar folios duplicados por concurrencia.
     */
    private function generarNumeracionFactura($pdo, array $config)
    {
        if (($config['sar_activo'] ?? '0') !== '1' || trim((string)($config['sar_cai'] ?? '')) === '') {
            throw new Exception('La facturación fiscal no está habilitada. Configure el CAI en Configuración antes de emitir facturas.');
        }

        $stmtLock = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'sar_correlativo_actual' FOR UPDATE");
        $stmtLock->execute();
        $correlativoActual = (int)($stmtLock->fetchColumn() ?: ($config['sar_correlativo_actual'] ?? 0));
        $correlativoSiguiente = $correlativoActual + 1;

        $rangoFinal = trim((string)($config['sar_rango_final'] ?? ''));
        if ($rangoFinal !== '' && preg_match('/(\d{8})$/', $rangoFinal, $coincidencia)) {
            $limiteFinal = (int)$coincidencia[1];
            if ($correlativoSiguiente > $limiteFinal) {
                throw new Exception('El rango de facturación CAI autorizado se ha agotado. Solicite un nuevo CAI a la SAR.');
            }
        }

        $puntoVenta     = str_pad((string)($config['sar_punto_venta'] ?? '001'), 3, '0', STR_PAD_LEFT);
        $establecimiento = str_pad((string)($config['sar_establecimiento'] ?? '001'), 3, '0', STR_PAD_LEFT);
        $tipoDocumento  = str_pad((string)($config['sar_tipo_documento'] ?? '01'), 2, '0', STR_PAD_LEFT);
        $folio = $puntoVenta . '-' . $establecimiento . '-' . $tipoDocumento . '-' . str_pad((string)$correlativoSiguiente, 8, '0', STR_PAD_LEFT);

        $rangoInicial = trim((string)($config['sar_rango_inicial'] ?? ''));
        $fechaLimite = trim((string)($config['sar_fecha_limite'] ?? ''));

        return [
            'folio' => $folio,
            'correlativo_siguiente' => $correlativoSiguiente,
            'cai' => $config['sar_cai'] ?? '',
            'rango_autorizado' => ($rangoInicial !== '' || $rangoFinal !== '') ? ($rangoInicial . ' al ' . $rangoFinal) : null,
            'fecha_limite' => $fechaLimite !== '' ? $fechaLimite : null
        ];
    }

    private function columnaExiste($pdo, $tabla, $columna)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
        if ($tabla === '' || $columna === '') return false;
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
        return $stmt !== false && $stmt->rowCount() > 0;
    }
}