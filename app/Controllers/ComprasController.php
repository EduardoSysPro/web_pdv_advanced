<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Producto.php';

// Controlador solo de vista previa (maqueta): aún no persiste compras en base de datos.
class ComprasController extends Controller
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
        $productos = $busqueda === '' ? [] : $this->modeloProducto->buscarPorNombreOCodigo($busqueda, 20);

        // Historial de ejemplo únicamente para maquetar la tabla mientras no existe el backend.
        $historial = [
            ['id' => 1042, 'fecha' => '2026-09-05', 'proveedor' => 'Distribuidora La Colonia', 'numero_factura' => '000-001-01-00012345', 'tipo_documento' => 'Factura CAI', 'total' => 15840.50, 'estado' => 'Recibida'],
            ['id' => 1041, 'fecha' => '2026-09-03', 'proveedor' => 'Alimentos del Valle S.A.', 'numero_factura' => '000-002-01-00004821', 'tipo_documento' => 'Factura CAI', 'total' => 8320.00, 'estado' => 'Recibida'],
            ['id' => 1040, 'fecha' => '2026-08-29', 'proveedor' => 'Proveedor Local (Recibo)', 'numero_factura' => 'R-00981', 'tipo_documento' => 'Recibo simple', 'total' => 1250.75, 'estado' => 'Pendiente de pago'],
        ];

        require APP_PATH . 'Views/compras/index.php';
    }

    // Endpoint de apoyo para el buscador de productos existentes (solo lectura).
    public function buscarProducto()
    {
        $this->requerirAdministrador();
        $termino = trim($_GET['termino'] ?? '');
        header('Content-Type: application/json');
        echo json_encode($termino === '' ? [] : $this->modeloProducto->buscarPorNombreOCodigo($termino, 15));
    }
}
