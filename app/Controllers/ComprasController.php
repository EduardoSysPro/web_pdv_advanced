<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Producto.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Proveedor.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Compra.php';

class ComprasController extends Controller
{
    private $modeloProducto;
    private $modeloProveedor;
    private $modeloCompra;

    public function __construct()
    {
        parent::__construct();
        $this->modeloProducto = new Producto();
        $this->modeloProveedor = new Proveedor();
        $this->modeloCompra = new Compra();
    }

    public function index()
    {
        $this->requerirAdministrador();

        $busqueda = trim($_GET['busqueda'] ?? '');
        $proveedorId = (int)($_GET['proveedor_id'] ?? 0);
        $fechaDesde = trim($_GET['fecha_desde'] ?? '');
        $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
        $estado = trim($_GET['estado'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) $fechaDesde = '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) $fechaHasta = '';

        $proveedores = $this->modeloProveedor->obtenerTodos();
        $historial = $this->modeloCompra->obtenerHistorial($busqueda, $proveedorId, $fechaDesde, $fechaHasta, $estado);

        $mensaje = $_SESSION['mensaje_compras'] ?? null;
        unset($_SESSION['mensaje_compras']);

        $tituloPagina = 'Compras';
        require APP_PATH . 'Views/compras/index.php';
    }

    public function ver($parametros)
    {
        $this->requerirAdministrador();
        $compra = $this->modeloCompra->obtenerPorId((int)($parametros['id'] ?? 0));
        if (!$compra) $this->redirigir('compras');
        $tituloPagina = 'Detalle de compra';
        require APP_PATH . 'Views/compras/ver.php';
    }

    public function pagos()
    {
        $this->requerirAdministrador();
        $proveedorId = (int)($_GET['proveedor_id'] ?? 0);
        $busqueda = trim($_GET['busqueda'] ?? '');
        $cuentas = $this->modeloCompra->obtenerCuentasPorPagar($proveedorId, $busqueda);
        $proveedores = $this->modeloProveedor->obtenerTodos();
        $mensaje = $_SESSION['mensaje_compras'] ?? null;
        unset($_SESSION['mensaje_compras']);
        $tituloPagina = 'Cuentas por pagar';
        require APP_PATH . 'Views/compras/pagos.php';
    }

    public function anular($parametros)
    {
        $this->requerirAdministrador();
        $id = (int)($parametros['id'] ?? 0);
        try {
            $this->modeloCompra->anular($id);
            $_SESSION['mensaje_compras'] = 'Compra anulada y su inventario revertido correctamente.';
        } catch (Throwable $e) {
            $_SESSION['mensaje_compras'] = 'No se pudo anular la compra: ' . $e->getMessage();
        }
        $this->redirigir('compras');
    }

    public function abonar()
    {
        $this->requerirAdministrador();
        $compraId = (int)($_POST['compra_id'] ?? 0);
        $monto = (float)($_POST['monto'] ?? 0);
        $formaPago = trim($_POST['forma_pago'] ?? 'efectivo');
        $observacion = trim($_POST['observacion'] ?? '');
        try {
            $this->modeloCompra->registrarPago($compraId, $monto, $formaPago, $observacion, (int)$_SESSION['id']);
            $_SESSION['mensaje_compras'] = 'Pago registrado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['mensaje_compras'] = 'No se pudo registrar el pago: ' . $e->getMessage();
        }
        $this->redirigir('compras/ver/' . $compraId);
    }

    // Endpoint de apoyo para el buscador de productos existentes (solo lectura).
    public function buscarProducto()
    {
        $this->requerirAdministrador();
        $termino = trim($_GET['termino'] ?? '');
        header('Content-Type: application/json');
        echo json_encode($termino === '' ? [] : $this->modeloProducto->buscarProductosAjax($termino, 15));
    }

    public function buscarProveedores()
    {
        $this->requerirAdministrador();
        header('Content-Type: application/json; charset=utf-8');
        $termino = trim($_GET['termino'] ?? $_POST['termino'] ?? '');
        echo json_encode($this->modeloProveedor->obtenerTodos($termino));
    }

    public function guardarProveedorAjax()
    {
        $this->requerirAdministrador();
        header('Content-Type: application/json; charset=utf-8');

        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        if (!is_array($datos)) {
            $datos = $_POST;
        }

        $nombre = trim((string)($datos['nombre'] ?? ''));
        $rtn = trim((string)($datos['rtn'] ?? ''));
        $tipo = ($datos['tipo'] ?? 'contribuyente') === 'no_contribuyente' ? 'no_contribuyente' : 'contribuyente';

        if ($nombre === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'El nombre del proveedor es obligatorio.']);
            return;
        }

        try {
            $existente = $this->modeloProveedor->obtenerPorRtn($rtn);
            if ($existente && !empty($rtn)) {
                echo json_encode([
                    'exito' => true,
                    'mensaje' => 'El proveedor ya existía; se usó el registro actual.',
                    'proveedor' => $existente,
                ]);
                return;
            }
            $proveedorId = $this->modeloProveedor->insertar([
                'nombre' => $nombre,
                'rtn' => $rtn,
                'tipo' => $tipo,
                'telefono' => $datos['telefono'] ?? '',
                'direccion' => $datos['direccion'] ?? '',
                'contacto' => $datos['contacto'] ?? '',
                'correo' => $datos['correo'] ?? '',
            ]);
            if ($proveedorId === false) {
                echo json_encode(['exito' => false, 'mensaje' => 'No se pudo crear el proveedor.']);
                return;
            }
            echo json_encode([
                'exito' => true,
                'mensaje' => 'Proveedor creado correctamente.',
                'proveedor' => $this->modeloProveedor->obtenerPorId($proveedorId),
            ]);
        } catch (Throwable $e) {
            echo json_encode(['exito' => false, 'mensaje' => 'No se pudo guardar el proveedor: ' . $e->getMessage()]);
        }
    }

    public function guardar()
    {
        $this->requerirAdministrador();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['exito' => false, 'mensaje' => 'Método HTTP no permitido.']);
            return;
        }

        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        if (!is_array($datos)) {
            $datos = $_POST;
        }

        try {
            $resultado = $this->modeloCompra->registrarCompra($datos);
            echo json_encode([
                'exito' => true,
                'mensaje' => 'Compra ' . $resultado['folio'] . ' registrada. Inventario y costos actualizados.',
                'compra_id' => $resultado['compra_id'],
                'folio' => $resultado['folio'],
                'total' => $resultado['total'],
                'isv_acreditable' => $resultado['isv_acreditable'],
            ]);
        } catch (Throwable $e) {
            echo json_encode(['exito' => false, 'mensaje' => 'Error al registrar la compra: ' . $e->getMessage()]);
        }
    }
}