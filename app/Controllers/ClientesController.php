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
        Cliente::asegurarEsquema();
    }

    public function index()
    {
        $this->requerirAutenticacion();
        $busqueda = trim($_GET['busqueda'] ?? '');
        // Filtro por categoría: todos | minorista | mayorista.
        $tipo = trim($_GET['tipo'] ?? '');
        if ($tipo !== 'minorista' && $tipo !== 'mayorista') {
            $tipo = '';
        }
        $porPagina = 20;
        $total = $this->modelo->contarTodos($busqueda, $tipo);
        $paginas = max(1, (int)ceil($total / $porPagina));
        $pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
        $clientes = $this->modelo->obtenerTodos($busqueda, $porPagina, $pagina, $tipo);
        $vencidos = $this->modelo->mapaVencidos();
        $paginacion = ['total' => $total, 'pagina' => $pagina, 'paginas' => $paginas, 'por_pagina' => $porPagina];
        $mensaje = $_SESSION['mensaje_clientes'] ?? null;
        unset($_SESSION['mensaje_clientes']);
        require APP_PATH . 'Views/clientes/index.php';
    }

    /** P5: reporte de cartera de cuentas por cobrar. */
    public function cartera()
    {
        $this->requerirAutenticacion();
        $filtro = trim($_GET['filtro'] ?? 'todos');
        if (!in_array($filtro, ['todos', 'vencidos', 'vigentes', 'sobregirados'], true)) {
            $filtro = 'todos';
        }
        $cartera = $this->modelo->obtenerCartera($filtro);
        $tituloPagina = 'Cartera de cuentas por cobrar';
        require APP_PATH . 'Views/clientes/cartera.php';
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
            'dias_credito' => max(0, (int)($_POST['dias_credito'] ?? Cliente::DIAS_CREDITO_DEFECTO)),
            'tipo' => Cliente::normalizarTipo($_POST['tipo'] ?? 'minorista'),
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
        try {
            $anterior = $this->modelo->obtenerPorId($id);
            $this->modelo->actualizar($id, $datos);
            // P4: audita cambios al límite de crédito.
            if ($anterior && (float)($anterior['limite_credito'] ?? 0) !== (float)$datos['limite_credito']) {
                $this->modelo->auditar($id, (int)$_SESSION['id'], 'limite_modificado', 'cliente', $id, 'Límite de L ' . number_format((float)$anterior['limite_credito'], 2) . ' a L ' . number_format((float)$datos['limite_credito'], 2));
            }
            $_SESSION['mensaje_clientes'] = 'Cliente actualizado correctamente.';
        } catch (Throwable $e) { $_SESSION['mensaje_clientes'] = 'No se pudo actualizar: ' . $e->getMessage(); }
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('clientes');
        }
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $monto = (float)($_POST['monto'] ?? 0);
        $formaPago = trim((string)($_POST['forma_pago'] ?? ''));
        if ($clienteId <= 0) {
            $_SESSION['mensaje_clientes'] = 'Cliente inválido.';
            $this->redirigir('clientes');
        }
        if ($monto <= 0) {
            $_SESSION['mensaje_clientes'] = 'El monto del abono debe ser mayor a cero.';
            $this->redirigir('clientes/estado-cuenta/' . $clienteId);
        }
        if (!in_array($formaPago, ['efectivo', 'tarjeta', 'transferencia'], true)) {
            $_SESSION['mensaje_clientes'] = 'Forma de pago inválida.';
            $this->redirigir('clientes/estado-cuenta/' . $clienteId);
        }
        try {
            // El modelo registra abono + aplicación FIFO + movimiento de caja en una sola transacción.
            $pagoId = $this->modelo->registrarAbono($clienteId, $monto, $formaPago, trim($_POST['observacion'] ?? ''), (int)$_SESSION['id'], (int)($_SESSION['caja_id'] ?? 0));
            $_SESSION['mensaje_clientes'] = 'Abono registrado correctamente (aplicado a las facturas más antiguas).';
            $_SESSION['ultimo_abono_id'] = $pagoId;
        } catch (Throwable $e) { $_SESSION['mensaje_clientes'] = 'No se pudo registrar el abono: ' . $e->getMessage(); }
        $this->redirigir('clientes/estado-cuenta/' . $clienteId);
    }

    /** P3: anula un abono con motivo; revierte saldo, aplicaciones y caja. Solo admin. */
    public function anularAbono()
    {
        $this->requerirAutenticacion();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('clientes');
        }
        $pagoId = (int)($_POST['pago_id'] ?? 0);
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        if (!$this->esAdmin()) {
            $_SESSION['mensaje_clientes'] = 'Solo un administrador puede anular abonos.';
            $this->redirigir($clienteId > 0 ? 'clientes/estado-cuenta/' . $clienteId : 'clientes');
        }
        if ($pagoId <= 0 || $motivo === '') {
            $_SESSION['mensaje_clientes'] = 'Debes indicar el motivo de la anulación.';
            $this->redirigir($clienteId > 0 ? 'clientes/estado-cuenta/' . $clienteId : 'clientes');
        }
        try {
            $this->modelo->anularAbono($pagoId, $motivo, (int)$_SESSION['id'], (int)($_SESSION['caja_id'] ?? 0));
            $_SESSION['mensaje_clientes'] = 'Abono anulado y saldo revertido correctamente.';
        } catch (Throwable $e) { $_SESSION['mensaje_clientes'] = 'No se pudo anular el abono: ' . $e->getMessage(); }
        $this->redirigir($clienteId > 0 ? 'clientes/estado-cuenta/' . $clienteId : 'clientes');
    }

    public function imprimirAbono($parametros)
    {
        $this->requerirAutenticacion();
        // Reimpresión: el admin puede reimprimir abonos de cualquier cajero;
        // el resto solo los propios.
        $esAdmin = $this->esAdmin();
        $stmt = Database::getInstancia()->getConexion()->prepare('SELECT p.*, c.nombre AS cliente_nombre, c.saldo_pendiente AS cliente_saldo, u.nombre AS usuario_nombre FROM pagos_clientes p INNER JOIN clientes c ON c.id=p.cliente_id INNER JOIN usuarios u ON u.id=p.usuario_id WHERE p.id=:id LIMIT 1');
        $stmt->execute([':id' => (int)($parametros['id'] ?? 0)]);
        $abono = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$abono || (!$esAdmin && (int)$abono['usuario_id'] !== (int)$_SESSION['id'])) {
            http_response_code(404);
            echo 'Abono no encontrado.';
            return;
        }
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';
        $configuracion = (new Configuracion())->obtenerMapa();
        $esReimpresion = !empty($_GET['reimpresion']) || (isset($_SESSION['ultimo_abono_id']) && (int)$_SESSION['ultimo_abono_id'] !== (int)$abono['id']);
        require APP_PATH . 'Views/clientes/ticket_abono.php';
    }

    /** Vista imprimible del estado de cuenta (punto 13). */
    public function imprimirEstadoCuenta($parametros)
    {
        $this->requerirAutenticacion();
        $cuenta = $this->modelo->obtenerEstadoCuenta((int)($parametros['id'] ?? 0));
        if (!$cuenta['cliente']) {
            $this->redirigir('clientes');
        }
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';
        $configuracion = (new Configuracion())->obtenerMapa();
        $tituloPagina = 'Estado de cuenta';
        require APP_PATH . 'Views/clientes/imprimir_estado_cuenta.php';
    }

    public function buscar()
    {
        $this->requerirAutenticacion();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->modelo->obtenerTodos(trim($_GET['busqueda'] ?? '')));
    }

    private function leerDatos()
    {
        return ['rtn_identidad'=>trim($_POST['rtn_identidad'] ?? ''), 'nombre'=>trim($_POST['nombre'] ?? ''), 'telefono'=>trim($_POST['telefono'] ?? ''), 'direccion'=>trim($_POST['direccion'] ?? ''), 'limite_credito'=>max(0,(float)($_POST['limite_credito'] ?? 0)), 'dias_credito'=>max(0,(int)($_POST['dias_credito'] ?? Cliente::DIAS_CREDITO_DEFECTO)), 'tipo'=>Cliente::normalizarTipo($_POST['tipo'] ?? 'minorista')];
    }
}
