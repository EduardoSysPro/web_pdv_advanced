<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Cliente.php';

class ClientesController extends Controller
{
    private $modelo;

    public function __construct()
    {
        parent::__construct();
        $this->modelo = new Cliente();
    }

    public function index()
    {
        $this->requerirAutenticacion();
        $busqueda = trim($_GET['busqueda'] ?? '');
        $clientes = $this->modelo->obtenerTodos($busqueda);
        $mensaje = $_SESSION['mensaje_clientes'] ?? null;
        unset($_SESSION['mensaje_clientes']);
        require APP_PATH . 'Views/clientes/index.php';
    }

    public function guardar()
    {
        $this->requerirAutenticacion();
        $datos = $this->leerDatos();
        if ($datos['nombre'] === '') { $_SESSION['mensaje_clientes'] = 'El nombre es obligatorio.'; $this->redirigir('clientes'); }
        try { $this->modelo->insertar($datos); $_SESSION['mensaje_clientes'] = 'Cliente creado correctamente.'; } catch (Throwable $e) { $_SESSION['mensaje_clientes'] = 'No se pudo guardar el cliente: ' . $e->getMessage(); }
        $this->redirigir('clientes');
    }

    public function guardarAjax()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');

        $datos = [
            'rtn_identidad' => trim($_POST['rtn_identidad'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'limite_credito' => max(0, (float)($_POST['limite_credito'] ?? 0)),
        ];

        if ($datos['nombre'] === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'El nombre del cliente es obligatorio.']);
            return;
        }

        try {
            $clienteId = $this->modelo->insertar($datos);
            if ($clienteId === false) {
                echo json_encode(['exito' => false, 'mensaje' => 'No se pudo crear el cliente.']);
                return;
            }

            $cliente = $this->modelo->obtenerPorId($clienteId);
            echo json_encode([
                'exito' => true,
                'mensaje' => 'Cliente registrado correctamente.',
                'cliente' => $cliente,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['exito' => false, 'mensaje' => 'No se pudo guardar el cliente: ' . $e->getMessage()]);
        }
    }

    public function editar($parametros)
    {
        $this->requerirAutenticacion();
        $cliente = $this->modelo->obtenerPorId((int)($parametros['id'] ?? 0));
        if (!$cliente) $this->redirigir('clientes');
        $tituloPagina = 'Editar cliente';
        require APP_PATH . 'Views/clientes/editar.php';
    }

    public function actualizar($parametros)
    {
        $this->requerirAutenticacion();
        $id = (int)($parametros['id'] ?? 0);
        $datos = $this->leerDatos();
        if ($datos['nombre'] === '') { $_SESSION['mensaje_clientes'] = 'El nombre es obligatorio.'; $this->redirigir('clientes'); }
        try { $this->modelo->actualizar($id, $datos); $_SESSION['mensaje_clientes'] = 'Cliente actualizado correctamente.'; } catch (Throwable $e) { $_SESSION['mensaje_clientes'] = 'No se pudo actualizar: ' . $e->getMessage(); }
        $this->redirigir('clientes');
    }

    public function estadoCuenta($parametros)
    {
        $this->requerirAutenticacion();
        $cuenta = $this->modelo->obtenerEstadoCuenta((int)($parametros['id'] ?? 0));
        if (!$cuenta['cliente']) $this->redirigir('clientes');
        require APP_PATH . 'Views/clientes/estado_cuenta.php';
    }

    public function abonar()
    {
        $this->requerirAutenticacion();
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        try {
            $pagoId = $this->modelo->registrarAbono($clienteId, (float)($_POST['monto'] ?? 0), $_POST['forma_pago'] ?? '', trim($_POST['observacion'] ?? ''), (int)$_SESSION['id']);
            $pdo = Database::getInstancia()->getConexion();
            $cajaId = (int)($_SESSION['caja_id'] ?? 0);
            $tieneCaja = $pdo->query("SHOW COLUMNS FROM caja_movimientos LIKE 'caja_id'")->rowCount() > 0;
            $stmt = $pdo->prepare("INSERT INTO caja_movimientos (usuario_id" . ($tieneCaja ? ',caja_id' : '') . ",tipo,monto,concepto) VALUES (:usuario" . ($tieneCaja ? ',:caja_id' : '') . ",'ingreso_abono',:monto,:concepto)");
            $parametros = [':usuario'=>(int)$_SESSION['id'], ':monto'=>(float)$_POST['monto'], ':concepto'=>'Abono cliente ID ' . $clienteId];
            if ($tieneCaja) $parametros[':caja_id'] = $cajaId > 0 ? $cajaId : null;
            $stmt->execute($parametros);
            $_SESSION['mensaje_clientes'] = 'Abono registrado correctamente.';
            $_SESSION['ultimo_abono_id'] = $pagoId;
        } catch (Throwable $e) { $_SESSION['mensaje_clientes'] = 'No se pudo registrar el abono: ' . $e->getMessage(); }
        $this->redirigir('clientes/estado-cuenta/' . $clienteId);
    }

    public function imprimirAbono($parametros)
    {
        $this->requerirAutenticacion();
        $stmt = Database::getInstancia()->getConexion()->prepare('SELECT p.*, c.nombre AS cliente_nombre, u.nombre AS usuario_nombre FROM pagos_clientes p INNER JOIN clientes c ON c.id=p.cliente_id INNER JOIN usuarios u ON u.id=p.usuario_id WHERE p.id=:id AND p.usuario_id=:usuario LIMIT 1');
        $stmt->execute([':id'=>(int)($parametros['id'] ?? 0), ':usuario'=>(int)$_SESSION['id']]);
        $abono = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$abono) { http_response_code(404); echo 'Abono no encontrado.'; return; }
        require APP_PATH . 'Views/clientes/ticket_abono.php';
    }

    public function buscar()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->modelo->obtenerTodos(trim($_GET['busqueda'] ?? '')));
    }

    private function leerDatos()
    {
        return ['rtn_identidad'=>trim($_POST['rtn_identidad'] ?? ''), 'nombre'=>trim($_POST['nombre'] ?? ''), 'telefono'=>trim($_POST['telefono'] ?? ''), 'direccion'=>trim($_POST['direccion'] ?? ''), 'limite_credito'=>max(0,(float)($_POST['limite_credito'] ?? 0))];
    }
}
