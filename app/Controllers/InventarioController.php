<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Producto.php';

class InventarioController extends Controller
{
    private $modeloProducto;

    public function __construct()
    {
        parent::__construct();
        $this->modeloProducto = new Producto();
    }

    public function index()
    {
        $this->requerirAdministrador();
        $busqueda = trim($_GET['busqueda'] ?? '');
        $productos = $busqueda === '' ? [] : $this->modeloProducto->buscarPorNombreOCodigo($busqueda, 50);
        $bajoStock = $this->modeloProducto->obtenerProductosBajoStock();
        $productoSeleccionado = (int)($_GET['producto_id'] ?? 0);
        $productoElegido = $productoSeleccionado > 0 ? $this->modeloProducto->obtenerPorId($productoSeleccionado) : null;
        $mensaje = $_SESSION['mensaje_inventario'] ?? null;
        $error = $_SESSION['error_inventario'] ?? null;
        unset($_SESSION['mensaje_inventario'], $_SESSION['error_inventario']);
        require APP_PATH . 'Views/inventario/index.php';
    }

    public function bajoStock()
    {
        $this->requerirAdministrador();
        $this->redirigir('inventario#bajo-stock');
    }

    public function ajustar()
    {
        $this->requerirAdministrador();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('inventario');
        }

        $productoId = (int)($_POST['producto_id'] ?? 0);
        $tipo = $_POST['tipo_movimiento'] ?? '';
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        if ($productoId <= 0 || !in_array($tipo, ['entrada', 'salida'], true) || $cantidad <= 0 || $motivo === '') {
            $_SESSION['error_inventario'] = 'Completa el producto, tipo, cantidad y motivo del ajuste.';
            $this->redirigir('inventario');
        }

        $pdo = Database::getInstancia()->getConexion();
        try {
            $pdo->beginTransaction();
            if (!$this->modeloProducto->actualizarStockManual($productoId, $cantidad, $tipo)) {
                throw new Exception('El producto no existe o no tiene stock suficiente para la salida.');
            }
            $stmt = $pdo->prepare('INSERT INTO inventario_movimientos (producto_id, usuario_id, tipo_movimiento, cantidad, motivo)
                                   VALUES (:producto_id, :usuario_id, :tipo, :cantidad, :motivo)');
            $stmt->execute([
                ':producto_id' => $productoId,
                ':usuario_id' => (int)$_SESSION['id'],
                ':tipo' => $tipo,
                ':cantidad' => $cantidad,
                ':motivo' => $motivo
            ]);
            $pdo->commit();
            $_SESSION['mensaje_inventario'] = 'Ajuste de stock registrado correctamente.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error_inventario'] = $e->getMessage();
        }
        $this->redirigir('inventario');
    }
}