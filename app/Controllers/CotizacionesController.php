<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Cotizacion.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Producto.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';

class CotizacionesController extends Controller
{
    private $modeloCotizacion;
    private $modeloProducto;
    private $modeloConfiguracion;

    public function __construct()
    {
        parent::__construct();
        $this->modeloCotizacion = new Cotizacion();
        $this->modeloProducto = new Producto();
        $this->modeloConfiguracion = new Configuracion();
    }

    private function esVendedor()
    {
        return in_array(strtolower((string)($_SESSION['rol'] ?? '')), ['vendedor', 'cajero_movil'], true);
    }

    public function index()
    {
        $this->requerirAutenticacion();
        $rol = strtolower((string)($_SESSION['rol'] ?? ''));
        $filtro = trim($_GET['estado'] ?? 'pendiente');
        $todas = $this->modeloCotizacion->obtenerTodas();
        $conteos = ['pendiente' => 0, 'facturada' => 0, 'cancelada' => 0];
        foreach ($todas as $fila) {
            $conteos[$fila['estado']] = ($conteos[$fila['estado']] ?? 0) + 1;
        }
        $seleccion = in_array($filtro, ['pendiente', 'facturada', 'cancelada', 'todas'], true) ? $filtro : 'pendiente';
        $vendedorId = $this->esVendedor() ? (int)$_SESSION['id'] : null;
        $cotizaciones = $seleccion === 'todas'
            ? $this->modeloCotizacion->obtenerTodas(null, $vendedorId)
            : $this->modeloCotizacion->obtenerTodas($seleccion, $vendedorId);
        $mensaje = $_SESSION['mensaje_cotizaciones'] ?? null;
        $error = $_SESSION['error_cotizaciones'] ?? null;
        unset($_SESSION['mensaje_cotizaciones'], $_SESSION['error_cotizaciones']);

        require APP_PATH . 'Views' . DIRECTORY_SEPARATOR . 'cotizaciones' . DIRECTORY_SEPARATOR . 'index.php';
    }

    public function crear()
    {
        $this->requerirAutenticacion();
        if (!$this->esVendedor() && !$this->esAdministradorSesion()) {
            $_SESSION['error_cotizaciones'] = 'No tienes permisos para crear cotizaciones.';
            $this->redirigir('cotizaciones');
        }
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $clientes = $this->todosLosClientes();
        $diasValidez = max(0, (int)($configuracion['cotizacion_dias_validez'] ?? 15));
        $fechaValidez = $diasValidez > 0 ? date('Y-m-d', strtotime('+' . $diasValidez . ' days')) : '';
        $cotizacion = null;
        $itemsEdicion = [];
        $urlBase = URL_BASE;

        require APP_PATH . 'Views' . DIRECTORY_SEPARATOR . 'cotizaciones' . DIRECTORY_SEPARATOR . 'crear.php';
    }

    public function editar($parametros)
    {
        $this->requerirAutenticacion();
        $id = (int)($parametros['id'] ?? 0);
        $cotizacion = $this->modeloCotizacion->obtenerPorId($id);
        if (!$cotizacion) {
            $_SESSION['error_cotizaciones'] = 'La cotización no existe.';
            $this->redirigir('cotizaciones');
        }
        if ($cotizacion['estado'] !== 'pendiente') {
            $_SESSION['error_cotizaciones'] = 'Solo se pueden editar cotizaciones pendientes.';
            $this->redirigir('cotizaciones/ver/' . $id);
        }
        if ($this->esVendedor() && (int)$cotizacion['vendedor_id'] !== (int)$_SESSION['id']) {
            $_SESSION['error_cotizaciones'] = 'No puedes editar una cotización de otro vendedor.';
            $this->redirigir('cotizaciones');
        }
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $clientes = $this->todosLosClientes();
        $itemsEdicion = $this->transformarDetallesParaEdicion($this->modeloCotizacion->obtenerDetalles($id));
        $urlBase = URL_BASE;

        require APP_PATH . 'Views' . DIRECTORY_SEPARATOR . 'cotizaciones' . DIRECTORY_SEPARATOR . 'crear.php';
    }

    public function ver($parametros)
    {
        $this->requerirAutenticacion();
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';
        $id = (int)($parametros['id'] ?? 0);
        $cotizacion = $this->modeloCotizacion->obtenerPorId($id);
        if (!$cotizacion) {
            $_SESSION['error_cotizaciones'] = 'La cotización no existe.';
            $this->redirigir('cotizaciones');
        }
        if ($this->esVendedor() && (int)$cotizacion['vendedor_id'] !== (int)$_SESSION['id']) {
            $_SESSION['error_cotizaciones'] = 'No puedes ver cotizaciones de otro vendedor.';
            $this->redirigir('cotizaciones');
        }
        $detalles = $this->modeloCotizacion->obtenerDetalles($id);
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $esVendedor = $this->esVendedor();

        require APP_PATH . 'Views' . DIRECTORY_SEPARATOR . 'cotizaciones' . DIRECTORY_SEPARATOR . 'ver.php';
    }

    public function imprimir($parametros)
    {
        $this->requerirAutenticacion();
        $id = (int)($parametros['id'] ?? 0);
        $cotizacion = $this->modeloCotizacion->obtenerPorId($id);
        if (!$cotizacion) {
            http_response_code(404);
            echo 'Cotización no encontrada.';
            return;
        }
        if ($this->esVendedor() && (int)$cotizacion['vendedor_id'] !== (int)$_SESSION['id']) {
            http_response_code(403);
            echo 'No puedes imprimir cotizaciones de otro vendedor.';
            return;
        }
        $detalles = $this->modeloCotizacion->obtenerDetalles($id);
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $configuracion = array_merge([
            'empresa_nombre' => $configuracion['nombre_negocio'] ?? 'MI NEGOCIO',
            'empresa_rtn' => $configuracion['rtn'] ?? '',
            'empresa_direccion' => $configuracion['direccion'] ?? '',
            'empresa_telefono' => $configuracion['telefono'] ?? '',
            'empresa_email' => $configuracion['email'] ?? '',
        ], $configuracion);

        require APP_PATH . 'Views' . DIRECTORY_SEPARATOR . 'cotizaciones' . DIRECTORY_SEPARATOR . 'imprimir.php';
    }

    public function guardar()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->esVendedor() && !$this->esAdministradorSesion()) {
            echo json_encode(['exito' => false, 'mensaje' => 'No tienes permisos para crear cotizaciones.']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['exito' => false, 'mensaje' => 'Método HTTP no permitido.']);
            return;
        }

        $datos = $this->leerPeticion();
        $datos = $this->normalizarDatos($datos);

        try {
            $pdo = Database::getInstancia()->getConexion();
            $procesado = $this->procesarItems($datos['productos']);

            $pdo->beginTransaction();
            $folio = $this->modeloCotizacion->generarFolio();
            $cotizacionId = $this->modeloCotizacion->insertarEncabezado([
                'folio'               => $folio,
                'vendedor_id'         => (int)($_SESSION['id'] ?? 0),
                'cliente_id'          => $datos['cliente_id'],
                'cliente_nombre'      => $datos['cliente_nombre'],
                'cliente_rtn'         => $datos['cliente_rtn'],
                'cliente_telefono'    => $datos['cliente_telefono'],
                'cliente_direccion'   => $datos['cliente_direccion'],
                'importe_exento'      => $procesado['importeExento'],
                'importe_exonerado'   => $procesado['importeExonerado'],
                'importe_gravado_15'  => $procesado['gravado15'],
                'isv_15'              => $procesado['isv15'],
                'importe_gravado_18'  => $procesado['gravado18'],
                'isv_18'              => $procesado['isv18'],
                'subtotal'            => $procesado['total'],
                'descuento_total'     => $procesado['descuentoTotal'],
                'total'               => $procesado['total'],
                'observaciones'       => $datos['observaciones'],
                'fecha_validez'       => $datos['fecha_validez']
            ]);
            $this->modeloCotizacion->reemplazarDetalle($cotizacionId, $procesado['items']);
            $pdo->commit();

            echo json_encode(['exito' => true, 'mensaje' => 'Cotización guardada correctamente.', 'folio' => $folio, 'cotizacion_id' => $cotizacionId, 'url_imprimir' => URL_BASE . 'cotizaciones/imprimir/' . $cotizacionId]);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar la cotización: ' . $e->getMessage()]);
        }
    }

    public function actualizar($parametros)
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($parametros['id'] ?? 0);
        $cotizacion = $this->modeloCotizacion->obtenerPorId($id);
        if (!$cotizacion || $cotizacion['estado'] !== 'pendiente') {
            echo json_encode(['exito' => false, 'mensaje' => 'La cotización no está pendiente.']);
            return;
        }
        if ($this->esVendedor() && (int)$cotizacion['vendedor_id'] !== (int)$_SESSION['id']) {
            echo json_encode(['exito' => false, 'mensaje' => 'No puedes editar cotizaciones de otro vendedor.']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['exito' => false, 'mensaje' => 'Método HTTP no permitido.']);
            return;
        }

        $datos = $this->normalizarDatos($this->leerPeticion());

        try {
            $pdo = Database::getInstancia()->getConexion();
            $procesado = $this->procesarItems($datos['productos']);

            $pdo->beginTransaction();
            $this->modeloCotizacion->actualizarEncabezado($id, [
                'cliente_id'          => $datos['cliente_id'],
                'cliente_nombre'      => $datos['cliente_nombre'],
                'cliente_rtn'         => $datos['cliente_rtn'],
                'cliente_telefono'    => $datos['cliente_telefono'],
                'cliente_direccion'   => $datos['cliente_direccion'],
                'importe_exento'      => $procesado['importeExento'],
                'importe_exonerado'   => $procesado['importeExonerado'],
                'importe_gravado_15'  => $procesado['gravado15'],
                'isv_15'              => $procesado['isv15'],
                'importe_gravado_18'  => $procesado['gravado18'],
                'isv_18'              => $procesado['isv18'],
                'subtotal'            => $procesado['total'],
                'descuento_total'     => $procesado['descuentoTotal'],
                'total'               => $procesado['total'],
                'observaciones'       => $datos['observaciones'],
                'fecha_validez'       => $datos['fecha_validez']
            ]);
            $this->modeloCotizacion->reemplazarDetalle($id, $procesado['items']);
            $pdo->commit();

            echo json_encode(['exito' => true, 'mensaje' => 'Cotización actualizada correctamente.', 'folio' => $cotizacion['folio'], 'cotizacion_id' => $id, 'url_imprimir' => URL_BASE . 'cotizaciones/imprimir/' . $id]);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar la cotización: ' . $e->getMessage()]);
        }
    }

    public function cancelar($parametros)
    {
        $this->requerirAutenticacion();
        $id = (int)($parametros['id'] ?? 0);
        $cotizacion = $this->modeloCotizacion->obtenerPorId($id);
        if (!$cotizacion) {
            $_SESSION['error_cotizaciones'] = 'La cotización no existe.';
            $this->redirigir('cotizaciones');
        }
        if ($this->esVendedor() && (int)$cotizacion['vendedor_id'] !== (int)$_SESSION['id']) {
            $_SESSION['error_cotizaciones'] = 'No puedes cancelar cotizaciones de otro vendedor.';
            $this->redirigir('cotizaciones');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cotizacion['estado'] === 'pendiente') {
            $this->modeloCotizacion->cancelar($id);
            $_SESSION['mensaje_cotizaciones'] = 'Cotización ' . $cotizacion['folio'] . ' cancelada.';
        } else {
            $_SESSION['error_cotizaciones'] = 'Solo se pueden cancelar cotizaciones pendientes.';
        }
        $this->redirigir('cotizaciones');
    }

    /**
     * Devuelve el payload JSON para cargar la cotización en el POS de caja.
     * Solo usuarios de caja/administrador pueden facturar.
     */
    public function facturar($parametros)
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        $rol = strtolower((string)($_SESSION['rol'] ?? ''));
        if (in_array($rol, ['vendedor', 'cajero_movil'], true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Solo el cajero puede facturar cotizaciones.']);
            return;
        }
        $id = (int)($parametros['id'] ?? 0);
        $cotizacion = $this->modeloCotizacion->obtenerPorId($id);
        if (!$cotizacion) {
            echo json_encode(['exito' => false, 'mensaje' => 'Cotización no encontrada.']);
            return;
        }
        if ($cotizacion['estado'] !== 'pendiente') {
            echo json_encode(['exito' => false, 'mensaje' => 'La cotización ya fue procesada o cancelada.']);
            return;
        }
        $detalles = $this->modeloCotizacion->obtenerDetalles($id);

        $items = [];
        foreach ($detalles as $detalle) {
            $tipoPres = $detalle['tipo_presentacion'] ?? 'unidad';
            $nomPres = !empty($detalle['nombre_presentacion']) ? $detalle['nombre_presentacion'] : 'Unidad';
            $factor = max(1.0, (float)($detalle['factor_unidades'] ?? 1.0));
            $nombreBase = !empty($detalle['producto_actual']) ? $detalle['producto_actual'] : $detalle['nombre_producto'];
            $prefijo = '';
            if ($tipoPres === 'empaque') {
                $factorTexto = rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.');
                $prefijo = '[' . $nomPres . ' x' . $factorTexto . '] ';
            } elseif (strpos((string)$detalle['nombre_producto'], '[Unidad]') === false) {
                $prefijo = '[Unidad] ';
            }
            $items[] = [
                'id'                  => (int)($detalle['producto_id'] ?? 0),
                'item_key'            => (($detalle['producto_id'] ?? 0) > 0 ? (int)$detalle['producto_id'] : 'libre_' . $detalle['id']) . '_' . ($tipoPres === 'empaque' ? 'empaque' : 'unidad'),
                'codigo_barras'       => $detalle['codigo_barras'] ?? '',
                'nombre'              => $prefijo . $nombreBase,
                'nombre_original'     => $nombreBase,
                'precio_lista'        => (float)$detalle['precio_lista'],
                'precio_unitario'     => (float)$detalle['precio_unitario'],
                'descuento_unitario'  => (float)$detalle['descuento_unitario'],
                'cantidad'            => (float)$detalle['cantidad'],
                'stock'               => (float)($detalle['stock_actual'] ?? 0),
                'stock_minimo'        => 0,
                'unidad_medida'       => $tipoPres === 'empaque' ? strtolower($nomPres) : 'unidad',
                'permite_decimales'   => false,
                'tipo_presentacion'   => $tipoPres,
                'nombre_presentacion' => $nomPres,
                'factor_unidades'     => $factor,
                'porcentaje_isv'      => (float)($detalle['porcentaje_isv'] ?? 15),
                'imagen'              => $detalle['producto_imagen'] ?? ''
            ];
        }

        echo json_encode([
            'exito' => true,
            'cotizacion' => [
                'id'               => (int)$cotizacion['id'],
                'folio'            => $cotizacion['folio'],
                'cliente_id'       => (int)($cotizacion['cliente_id'] ?? 0),
                'cliente_nombre'   => $cotizacion['cliente_nombre'] ?? '',
                'cliente_rtn'      => $cotizacion['cliente_rtn'] ?? '',
                'cliente_telefono' => $cotizacion['cliente_telefono'] ?? '',
                'cliente_direccion'=> $cotizacion['cliente_direccion'] ?? '',
                'total'            => (float)$cotizacion['total'],
                'items'            => $items
            ]
        ]);
    }

    public function buscarProductosAjax()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        $termino = trim($_GET['q'] ?? $_POST['q'] ?? '');
        echo json_encode($termino === '' ? [] : $this->modeloProducto->buscarProductosAjax($termino, 20));
    }

    /**
     * Busca un producto por código de barras exacto (escáner o tecleado).
     * Devuelve el mismo formato que buscarProductosAjax para poder agregarlo
     * directo al carrito de la cotización (+1 por escaneo).
     */
    public function buscarPorCodigo()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        $codigo = trim($_GET['codigo'] ?? $_POST['codigo'] ?? '');
        if ($codigo === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'Código de barras vacío.']);
            return;
        }
        $producto = $this->modeloProducto->buscarPorCodigoBarras($codigo);
        if (!$producto) {
            echo json_encode(['exito' => false, 'mensaje' => 'No se encontró un producto con el código: ' . htmlspecialchars($codigo)]);
            return;
        }

        $tipoCoincidencia = $producto['tipo_coincidencia'] ?? 'unidad';
        $tipoVenta = $producto['tipo_venta'] ?? 'solo_unidad';
        $nombreEmpaque = !empty($producto['nombre_empaque']) ? $producto['nombre_empaque'] : 'Caja';
        $factor = max(1.0, (float)($producto['unidades_por_empaque'] ?? 1.0));
        $factorTexto = rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.');
        $precioEmpaque = (float)($producto['precio_empaque'] ?? 0);
        $stockBase = (float)($producto['stock'] ?? 0);
        $esEmpaque = ($tipoCoincidencia === 'empaque') || ($tipoVenta === 'solo_empaque');

        if ($esEmpaque && $precioEmpaque > 0) {
            $stockEmpaques = $factor > 0 ? floor($stockBase / $factor) : 0;
            $payload = [
                'id'                  => (int)$producto['id'],
                'item_key'            => $producto['id'] . '_empaque',
                'codigo_barras'       => $producto['codigo_barras_empaque'] ?: $producto['codigo_barras'],
                'nombre'              => '[' . $nombreEmpaque . ' x' . $factorTexto . '] ' . $producto['nombre'],
                'nombre_original'     => $producto['nombre'],
                'precio_venta'        => $precioEmpaque,
                'stock'               => (float)$stockEmpaques,
                'stock_minimo'        => 0,
                'unidad_medida'       => strtolower($nombreEmpaque),
                'permite_decimales'   => false,
                'tipo_presentacion'   => 'empaque',
                'nombre_presentacion' => $nombreEmpaque,
                'factor_unidades'     => $factor,
                'clave_isv'           => $producto['tipo_impuesto'] ?? 'gravado_15',
                'porcentaje_isv'      => (float)($producto['porcentaje_isv'] ?? 15),
                'imagen'              => (string)($producto['imagen'] ?? '')
            ];
        } else {
            $payload = [
                'id'                  => (int)$producto['id'],
                'item_key'            => $producto['id'] . '_unidad',
                'codigo_barras'       => $producto['codigo_barras'],
                'nombre'              => ($tipoVenta === 'ambos' ? '[Unidad] ' : '') . $producto['nombre'],
                'nombre_original'     => $producto['nombre'],
                'precio_venta'        => (float)$producto['precio_venta'],
                'stock'               => $stockBase,
                'stock_minimo'        => (float)($producto['stock_minimo'] ?? 0),
                'unidad_medida'       => $producto['unidad_medida'] ?? 'unidad',
                'permite_decimales'   => !empty($producto['permite_decimales']),
                'tipo_presentacion'   => 'unidad',
                'nombre_presentacion' => 'Unidad',
                'factor_unidades'     => 1.0,
                'clave_isv'           => $producto['tipo_impuesto'] ?? 'gravado_15',
                'porcentaje_isv'      => (float)($producto['porcentaje_isv'] ?? 15),
                'imagen'              => (string)($producto['imagen'] ?? '')
            ];
        }

        echo json_encode(['exito' => true, 'producto' => $payload]);
    }

    /**
     * Listado JSON de cotizaciones pendientes para el modal del POS.
     */
    public function pendientes()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        $rol = strtolower((string)($_SESSION['rol'] ?? ''));
        if (in_array($rol, ['vendedor', 'cajero_movil'], true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Solo el cajero puede cargar cotizaciones en caja.']);
            return;
        }
        $filas = $this->modeloCotizacion->obtenerTodas('pendiente', null);
        $listado = array_map(static function ($f) {
            return [
                'id'             => (int)$f['id'],
                'folio'          => (string)$f['folio'],
                'vendedor'       => (string)($f['vendedor'] ?? ''),
                'cliente_nombre' => (string)($f['cliente_nombre'] ?: 'Consumidor Final'),
                'cliente_rtn'    => (string)($f['cliente_rtn'] ?? ''),
                'total'          => (float)$f['total'],
                'creada_en'      => (string)$f['creada_en'],
                'fecha_validez'  => (string)($f['fecha_validez'] ?? '')
            ];
        }, $filas);
        echo json_encode(['exito' => true, 'cotizaciones' => $listado]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function esAdministradorSesion()
    {
        return (int)($_SESSION['rol_id'] ?? 0) === 1
            || in_array(strtolower((string)($_SESSION['rol'] ?? '')), ['admin', 'administrador'], true);
    }

    private function todosLosClientes()
    {
        $pdo = Database::getInstancia()->getConexion();
        $stmt = $pdo->query('SELECT id, nombre, rtn_identidad, telefono, direccion FROM clientes ORDER BY nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function leerPeticion()
    {
        $contenido = file_get_contents('php://input');
        $datos = json_decode((string)$contenido, true);
        if (is_array($datos)) {
            return $datos;
        }
        return $_POST;
    }

    private function normalizarDatos($datos)
    {
        $tipoImpuestoDefault = ($this->modeloConfiguracion->obtenerMapa()['tipo_comprobante_default'] ?? 'recibo');
        return [
            'cliente_id'        => isset($datos['cliente_id']) ? (int)$datos['cliente_id'] : 0,
            'cliente_nombre'    => trim((string)($datos['cliente_nombre'] ?? '')),
            'cliente_rtn'       => trim((string)($datos['cliente_rtn'] ?? '')),
            'cliente_telefono'  => trim((string)($datos['cliente_telefono'] ?? '')),
            'cliente_direccion' => trim((string)($datos['cliente_direccion'] ?? '')),
            'observaciones'     => trim((string)($datos['observaciones'] ?? '')),
            'fecha_validez'     => trim((string)($datos['fecha_validez'] ?? '')),
            'productos'         => isset($datos['productos']) && is_array($datos['productos']) ? $datos['productos'] : []
        ];
    }

    /**
     * Procesa y valida cada artículo, calcula el desglose de ISV y los totales.
     * NO descuenta inventario: la cotización solo reserva la intención de venta.
     */
    private function procesarItems($productos)
    {
        if (!is_array($productos) || count($productos) === 0) {
            throw new RuntimeException('No hay productos en la cotización.');
        }

        $pdo = Database::getInstancia()->getConexion();
        $items = [];
        $importeExento = 0.0;
        $importeExonerado = 0.0;
        $gravado15 = 0.0;
        $isv15 = 0.0;
        $gravado18 = 0.0;
        $isv18 = 0.0;
        $descuentoTotal = 0.0;
        $total = 0.0;

        $hasImpuesto = $this->columnaExiste('productos', 'tipo_impuesto');

        // Carga la info de TODOS los productos del carrito en una sola consulta.
        // Con carritos grandes esto evita ejecutar N consultas (una por artículo).
        $infoProductos = [];
        $idsProductos = [];
        foreach ($productos as $item) {
            $idProducto = isset($item['id']) ? (int)$item['id'] : 0;
            if ($idProducto > 0) {
                $idsProductos[$idProducto] = $idProducto;
            }
        }
        if ($idsProductos) {
            $idsTexto = implode(',', array_map('intval', $idsProductos));
            $stmtInfo = $pdo->query('SELECT id, nombre, nombre_empaque, unidades_por_empaque' . ($hasImpuesto ? ', tipo_impuesto, porcentaje_isv' : '') . ' FROM productos WHERE id IN (' . $idsTexto . ')');
            foreach ($stmtInfo->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $infoProductos[(int)$fila['id']] = $fila;
            }
        }

        foreach ($productos as $item) {
            $productoId      = isset($item['id']) ? (int)$item['id'] : 0;
            $cantidad        = isset($item['cantidad']) ? (float)$item['cantidad'] : 0.0;
            $precioFinal     = isset($item['precio_unitario']) ? round((float)$item['precio_unitario'], 2) : 0.0;
            $precioLista     = array_key_exists('precio_lista', $item) ? round((float)$item['precio_lista'], 2) : $precioFinal;
            $descuentoUnit   = array_key_exists('descuento_unitario', $item)
                ? round((float)$item['descuento_unitario'], 2)
                : round($precioLista - $precioFinal, 2);

            if ($productoId <= 0 || $cantidad <= 0) {
                throw new RuntimeException('Artículo inválido en el detalle de la cotización.');
            }
            if ($precioLista < 0 || $precioFinal < 0 || $descuentoUnit < 0 || $descuentoUnit > $precioLista) {
                throw new RuntimeException('El precio o descuento del artículo no es válido.');
            }
            if (abs(round($precioLista - $descuentoUnit - $precioFinal, 2)) > 0.01) {
                throw new RuntimeException('El precio final y el descuento del artículo no coinciden.');
            }

            $subtotal = round($cantidad * $precioFinal, 2);
            $descuentoLinea = round($cantidad * $descuentoUnit, 2);
            $tipoPresentacion = (($item['tipo_presentacion'] ?? 'unidad') === 'empaque') ? 'empaque' : 'unidad';
            $nombrePres = trim((string)($item['nombre_presentacion'] ?? ($tipoPresentacion === 'empaque' ? 'Caja' : 'Unidad')));
            $factorUnidades = max(1.0, (float)($item['factor_unidades'] ?? 1.0));

            $tipoImpuesto = 'gravado_15';
            $porcentajeIsv = 15.0;
            $nombreProducto = 'Producto ' . $productoId;
            $prodInfo = $infoProductos[$productoId] ?? null;
            if ($prodInfo) {
                $nombreProducto = $prodInfo['nombre'];
                if ($tipoPresentacion === 'empaque') {
                    if ($factorUnidades <= 1.0 && (float)$prodInfo['unidades_por_empaque'] > 1.0) {
                        $factorUnidades = (float)$prodInfo['unidades_por_empaque'];
                    }
                    if (empty($nombrePres) || $nombrePres === 'Unidad' || $nombrePres === 'Caja') {
                        $nombrePres = !empty($prodInfo['nombre_empaque']) ? $prodInfo['nombre_empaque'] : 'Caja';
                    }
                }
                if ($hasImpuesto && !empty($prodInfo['tipo_impuesto'])) {
                    $tipoImpuesto = $prodInfo['tipo_impuesto'];
                    $porcentajeIsv = (float)$prodInfo['porcentaje_isv'];
                }
            }

            $esExento = $tipoImpuesto === 'exento' ? 1 : 0;
            $esExonerado = $tipoImpuesto === 'exonerado' ? 1 : 0;
            $montoIsv = $porcentajeIsv > 0 ? round($subtotal - ($subtotal / (1 + $porcentajeIsv / 100)), 2) : 0.0;
            $baseGravada = $subtotal - $montoIsv;

            if ($esExento) {
                $importeExento += $subtotal;
            } elseif ($esExonerado) {
                $importeExonerado += $subtotal;
            } elseif ($porcentajeIsv >= 18) {
                $gravado18 += $baseGravada;
                $isv18 += $montoIsv;
            } else {
                $gravado15 += $baseGravada;
                $isv15 += $montoIsv;
            }

            $total += $subtotal;
            $descuentoTotal += $descuentoLinea;

            $items[] = [
                'producto_id'         => $productoId,
                'nombre_producto'     => $nombreProducto,
                'cantidad'            => $cantidad,
                'precio_lista'        => $precioLista,
                'precio_unitario'     => $precioFinal,
                'descuento_unitario'  => $descuentoUnit,
                'subtotal'            => $subtotal,
                'tipo_presentacion'   => $tipoPresentacion,
                'nombre_presentacion' => $nombrePres,
                'factor_unidades'     => $factorUnidades,
                'porcentaje_isv'      => $porcentajeIsv,
                'monto_isv'           => $montoIsv,
                'es_exento'           => $esExento,
                'es_exonerado'        => $esExonerado
            ];
        }

        $total = round($total, 2);
        if ($total <= 0) {
            throw new RuntimeException('El total de la cotización no es válido.');
        }

        return [
            'items'             => $items,
            'importeExento'     => round($importeExento, 2),
            'importeExonerado'  => round($importeExonerado, 2),
            'gravado15'         => round($gravado15, 2),
            'isv15'             => round($isv15, 2),
            'gravado18'         => round($gravado18, 2),
            'isv18'             => round($isv18, 2),
            'descuentoTotal'    => round($descuentoTotal, 2),
            'total'             => $total
        ];
    }

    private function transformarDetallesParaEdicion($detalles)
    {
        $items = [];
        foreach ($detalles as $d) {
            $tipoPres = $d['tipo_presentacion'] ?? 'unidad';
            $nomPres = !empty($d['nombre_presentacion']) ? $d['nombre_presentacion'] : 'Unidad';
            $factor = max(1.0, (float)($d['factor_unidades'] ?? 1.0));
            $nombreBase = !empty($d['producto_actual']) ? $d['producto_actual'] : $d['nombre_producto'];
            $prefijo = '';
            if ($tipoPres === 'empaque') {
                $factorTexto = rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.');
                $prefijo = '[' . $nomPres . ' x' . $factorTexto . '] ';
            }
            $items[] = [
                'id'                  => (int)($d['producto_id'] ?? 0),
                'item_key'            => (($d['producto_id'] ?? 0) > 0 ? (int)$d['producto_id'] : 'libre_' . $d['id']) . '_' . ($tipoPres === 'empaque' ? 'empaque' : 'unidad'),
                'codigo_barras'       => $d['codigo_barras'] ?? '',
                'nombre'              => $prefijo . $nombreBase,
                'nombre_original'     => $nombreBase,
                'precio_lista'        => (float)$d['precio_lista'],
                'precio_unitario'     => (float)$d['precio_unitario'],
                'descuento_unitario'  => (float)$d['descuento_unitario'],
                'cantidad'            => (float)$d['cantidad'],
                'stock'               => (float)($d['stock_actual'] ?? 0),
                'stock_minimo'        => 0,
                'unidad_medida'       => $tipoPres === 'empaque' ? strtolower($nomPres) : 'unidad',
                'permite_decimales'   => false,
                'tipo_presentacion'   => $tipoPres,
                'nombre_presentacion' => $nomPres,
                'factor_unidades'     => $factor,
                'porcentaje_isv'      => (float)($d['porcentaje_isv'] ?? 15),
                'imagen'              => $d['producto_imagen'] ?? ''
            ];
        }
        return $items;
    }

    private function columnaExiste($tabla, $columna)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
        if ($tabla === '' || $columna === '') return false;
        $stmt = Database::getInstancia()->getConexion()->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
        return $stmt !== false && $stmt->rowCount() > 0;
    }
}