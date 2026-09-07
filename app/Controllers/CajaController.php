<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Caja.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Venta.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';

class CajaController extends Controller
{
    private $modeloCaja;
    private $modeloVenta;

    public function __construct()
    {
        parent::__construct();
        $this->modeloCaja = new Caja();
        $this->modeloVenta = new Venta();
    }

    public function index()
    {
        $this->requerirAutenticacion();
        $usuarioId = (int)$_SESSION['id'];
        $cajasDisponibles = $this->modeloCaja->obtenerCajasDisponibles();
        $cajaSinAsignar = (int)($_SESSION['caja_id'] ?? 0) <= 0;
        $turno = $this->modeloCaja->obtenerTurnoAbierto($usuarioId);
        $resumen = $turno ? $this->modeloCaja->obtenerResumen($usuarioId, $turno, $this->modeloVenta) : null;
        $movimientos = $turno ? $this->modeloCaja->obtenerMovimientosTurno($turno['id'] ?? 0, $turno) : [];
        $ultimosCortes = $this->modeloCaja->obtenerUltimosCortesCerrados($usuarioId, 10);
        $mensaje = $_SESSION['mensaje_caja'] ?? null;
        $error = $_SESSION['error_caja'] ?? null;
        unset($_SESSION['mensaje_caja'], $_SESSION['error_caja']);

        require APP_PATH . 'Views/caja/index.php';
    }

    public function abrir()
    {
        $this->requerirAutenticacion();
        $usuarioId = (int)$_SESSION['id'];
        $monto = (float)($_POST['fondo_inicial'] ?? -1);

        if ($monto < 0) {
            $_SESSION['error_caja'] = 'El fondo inicial no puede ser negativo.';
            $this->redirigir('caja');
        }

        $cajaId = (int)($_SESSION['caja_id'] ?? 0);
        if (isset($_POST['caja_id']) && (int)$_POST['caja_id'] > 0) {
            $cajaId = (int)$_POST['caja_id'];
        }
        $caja = $this->modeloCaja->obtenerCajaActiva($cajaId);
        if (!$caja) {
            $caja = $this->modeloCaja->obtenerPrimeraCajaDisponible();
        }
        if (!$caja) {
            $_SESSION['error_caja'] = 'No hay cajas activas disponibles para abrir el turno.';
            $this->redirigir('caja');
        }
        $_SESSION['caja_id'] = (int)$caja['id'];
        $_SESSION['caja_nombre'] = $caja['nombre'];

        if ($this->modeloCaja->obtenerTurnoAbierto($usuarioId)) {
            $_SESSION['error_caja'] = 'Ya existe un turno abierto para este usuario.';
            $this->redirigir('caja');
        }

        try {
            $turnoId = $this->modeloCaja->registrarApertura($usuarioId, $monto);
            $_SESSION['mensaje_caja'] = 'Caja abierta correctamente.';
            $_SESSION['ultimo_corte_id'] = $turnoId;
        } catch (Throwable $e) {
            $_SESSION['error_caja'] = 'No se pudo abrir la caja: ' . $e->getMessage();
        }

        $this->redirigir('caja');
    }

    public function registrarIngreso()
    {
        $this->registrarMovimiento('ingreso');
    }

    public function registrarEgreso()
    {
        $this->registrarMovimiento('egreso');
    }

    public function movimiento()
    {
        $tipo = $_POST['tipo'] ?? '';
        if ($tipo === 'ingreso') {
            $this->registrarIngreso();
        } elseif ($tipo === 'egreso') {
            $this->registrarEgreso();
        } else {
            $_SESSION['error_caja'] = 'El tipo de movimiento no es válido.';
            $this->redirigir('caja');
        }
    }

    private function registrarMovimiento($tipo)
    {
        $this->requerirAutenticacion();
        $usuarioId = (int)$_SESSION['id'];
        $turno = $this->modeloCaja->obtenerTurnoAbierto($usuarioId);
        $monto = (float)($_POST['monto'] ?? 0);
        $concepto = trim((string)($_POST['concepto'] ?? ''));

        if (!in_array($tipo, ['ingreso', 'egreso'], true) || $monto <= 0 || $concepto === '') {
            $_SESSION['error_caja'] = 'Indica un monto mayor a cero y un concepto válido.';
            $this->redirigir('caja');
        }

        if (!$turno) {
            $_SESSION['error_caja'] = 'Debes abrir la caja antes de registrar movimientos.';
            $this->redirigir('caja');
        }

        $turnoId = (int)($turno['id'] ?? 0);
        $guardado = $this->modeloCaja->registrarMovimientoDuranteTurno($usuarioId, $turnoId, $tipo, $monto, $concepto);

        if ($guardado) {
            $_SESSION['mensaje_caja'] = $tipo === 'ingreso' ? 'Ingreso registrado correctamente.' : 'Egreso registrado correctamente.';
        } else {
            $_SESSION['error_caja'] = 'No se pudo registrar el movimiento.';
        }

        $this->redirigir('caja');
    }

    public function corte()
    {
        $this->index();
    }

    public function cerrar()
    {
        $this->requerirAutenticacion();
        $usuarioId = (int)$_SESSION['id'];
        $montoDeclarado = (float)($_POST['monto_declarado'] ?? -1);
        $turno = $this->modeloCaja->obtenerTurnoAbierto($usuarioId);

        if (!$turno || $montoDeclarado < 0) {
            $_SESSION['error_caja'] = 'La caja no está abierta o el monto declarado no es válido.';
            $this->redirigir('caja');
        }

        $resumen = $this->modeloCaja->obtenerResumen($usuarioId, $turno, $this->modeloVenta);
        $diferencia = round($montoDeclarado - $resumen['efectivo_esperado'], 2);
        $turnoId = (int)($turno['id'] ?? 0);

        try {
            $cerrado = $this->modeloCaja->cerrarTurno($usuarioId, $turnoId, $montoDeclarado, $diferencia);
            if (!$cerrado) {
                throw new Exception('No fue posible cerrar el turno actual.');
            }

            $this->modeloCaja->registrarMovimientoUsuario($usuarioId, $turnoId, 'cierre', $montoDeclarado, 'Cierre de caja');
            $_SESSION['mensaje_caja'] = 'Cierre de caja realizado correctamente.';
            $_SESSION['ultimo_corte_id'] = $turnoId;
        } catch (Throwable $e) {
            $_SESSION['error_caja'] = 'No se pudo cerrar la caja: ' . $e->getMessage();
        }

        $this->redirigir('caja');
    }

    public function obtenerResumenCaja()
    {
        $this->requerirAutenticacion();
        $usuarioId = (int)$_SESSION['id'];
        $turno = $this->modeloCaja->obtenerTurnoAbierto($usuarioId);

        if (!$turno) {
            return ['efectivo_esperado' => 0.00, 'movimientos' => ['ingreso' => 0.00, 'egreso' => 0.00], 'ventas' => ['efectivo' => 0.00, 'tarjeta' => 0.00, 'transferencia' => 0.00]];
        }

        return $this->modeloCaja->obtenerResumen($usuarioId, $turno, $this->modeloVenta);
    }

    public function imprimirCorte($parametros)
    {
        $this->requerirAutenticacion();
        $id = (int)($parametros['id'] ?? 0);
        $usuarioId = (int)$_SESSION['id'];

        $tablaTurnos = $this->modeloCaja->obtenerNombreTablaTurnos();
        $campos = $tablaTurnos === 'cajas_turnos'
            ? 'c.*, u.nombre AS cajero, c.monto_apertura AS fondo_inicial'
            : 'c.*, u.nombre AS cajero';

        $sql = "SELECT {$campos}
                FROM {$tablaTurnos} c
                INNER JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.id = :id AND c.usuario_id = :usuario_id LIMIT 1";

        $stmt = Database::getInstancia()->getConexion()->prepare($sql);
        $stmt->execute([':id' => $id, ':usuario_id' => $usuarioId]);
        $corte = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$corte) {
            http_response_code(404);
            echo 'Corte no encontrado.';
            return;
        }

        $ventas = $this->modeloVenta->sumarPorMetodoPago($usuarioId, $corte['fecha_apertura'], $corte['fecha_cierre'] ?? date('Y-m-d H:i:s'));
        $movimientos = $this->modeloCaja->obtenerMovimientos($usuarioId, $corte['fecha_apertura'], $corte['fecha_cierre'] ?? date('Y-m-d H:i:s'));
        $fondoInicial = isset($corte['monto_apertura']) ? (float)$corte['monto_apertura'] : (float)($corte['fondo_inicial'] ?? 0.0);
        $esperado = $fondoInicial + $ventas['efectivo'] + $movimientos['ingreso'] - $movimientos['egreso'];
        $configuracion = (new Configuracion())->obtenerTodas();

        require APP_PATH . 'Views/caja/ticket.php';
    }
}
