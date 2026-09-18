<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Impresora.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';

class ImpresoraController extends Controller
{
    private $modeloImpresora;
    private $modeloConfiguracion;

    public function __construct()
    {
        parent::__construct();
        $this->modeloImpresora = new Impresora();
        $this->modeloConfiguracion = new Configuracion();
    }

    /**
     * Imprime una venta en la impresora LAN. GET/POST, devuelve JSON.
     */
    public function imprimirVenta($parametros)
    {
        $this->requerirAutenticacion();
        $id = (int)($parametros['id'] ?? 0);
        $configuracion = $this->modeloConfiguracion->obtenerMapa();
        $copias = (int)($_GET['copias'] ?? 2);
        $resultado = $this->modeloImpresora->imprimirVenta($id, $configuracion, $copias);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado);
    }

    /**
     * Envía un ticket de prueba de la impresora LAN. Solo administradores.
     */
    public function probar()
    {
        $this->requerirAdministrador();
        $datos = json_decode(file_get_contents('php://input'), true) ?: [];
        $ip = trim((string)($datos['ip'] ?? ($_POST['ip'] ?? '')));
        $puerto = (int)($datos['puerto'] ?? ($_POST['puerto'] ?? 9100));
        $configuracion = $this->modeloConfiguracion->obtenerMapa();

        $resultado = $this->modeloImpresora->probar($ip, $puerto, $configuracion);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado);
    }

    /**
     * Analiza la red local y devuelve las impresoras detectadas. Solo administradores.
     */
    public function escaneoLan()
    {
        $this->requerirAdministrador();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['exito' => false, 'mensaje' => 'Método HTTP no permitido.']);
            return;
        }

        $resultado = $this->modeloImpresora->escanearLan();

        echo json_encode($resultado);
    }
}