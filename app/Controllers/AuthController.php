<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Usuario.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'LoginIntento.php';

class AuthController extends Controller
{
    private $modeloUsuario;
    private $modeloLoginIntento;

    public function __construct()
    {
        parent::__construct();
        $this->modeloUsuario = new Usuario();
        $this->modeloLoginIntento = new LoginIntento();
    }

    public function login()
    {
        if ($this->estaAutenticado()) {
            
            $this->redirigir('ventas');
        }

        $error = isset($_SESSION['error_login']) ? $_SESSION['error_login'] : null;
        unset($_SESSION['error_login']);

        $usuarioGuardado = isset($_SESSION['usuario_intento']) ? $_SESSION['usuario_intento'] : '';
        unset($_SESSION['usuario_intento']);

        $this->vista('auth/login', [
            'error' => $error,
            'usuarioGuardado' => $usuarioGuardado
        ]);
    }

    public function autenticar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('login');
        }

        if (!csrf_verificar($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_login'] = 'La sesión expiró. Recarga la página e intenta de nuevo.';
            $this->redirigir('login');
        }

        $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $ipCliente = $this->ipCliente();

        $_SESSION['usuario_intento'] = $usuario;

        if (empty($usuario) || empty($password)) {
            $_SESSION['error_login'] = 'Por favor, completa todos los campos.';
            $this->redirigir('login');
        }

        $minutosBloqueo = $this->modeloLoginIntento->minutosBloqueado($usuario, $ipCliente);
        if ($minutosBloqueo > 0) {
            $_SESSION['error_login'] = 'Demasiados intentos fallidos. Intenta de nuevo en ' . $minutosBloqueo . ' minuto(s).';
            $this->redirigir('login');
        }

        $usuarioValidado = $this->modeloUsuario->validarCredenciales($usuario, $password);

        if (!$usuarioValidado) {
            $this->modeloLoginIntento->registrar($usuario, $ipCliente);
            $_SESSION['error_login'] = 'Usuario o contraseña incorrectos.';
            $this->redirigir('login');
        }

        $this->modeloLoginIntento->limpiar($usuario, $ipCliente);

        session_regenerate_id(true);

        $_SESSION['id'] = $usuarioValidado['id'];
        $_SESSION['nombre'] = $usuarioValidado['nombre'];
        $_SESSION['usuario'] = $usuarioValidado['usuario'];
        $_SESSION['rol'] = $usuarioValidado['rol'];
        $_SESSION['rol_id'] = $usuarioValidado['rol'] === 'admin' ? 1 : 2;
        $_SESSION['caja_id'] = !empty($usuarioValidado['caja_id']) ? (int)$usuarioValidado['caja_id'] : 0;
        $_SESSION['sucursal_nombre'] = $usuarioValidado['sucursal'] ?? 'Abarrotes Central';
        $_SESSION['caja_nombre'] = $_SESSION['caja_id'] > 0
            ? ($this->modeloUsuario->obtenerNombreCaja($_SESSION['caja_id']) ?: 'Caja no disponible')
            : 'Sin caja asignada';
        $_SESSION['logged_in'] = true;
        $_SESSION['ultimo_acceso'] = time();

        unset($_SESSION['usuario_intento']);

        // Si el usuario es de tipo Móvil -> va directo a la interfaz táctil /movil
        if ($_SESSION['rol'] === 'cajero_movil') {
            $this->redirigir('movil');
            return;
        }

        // Importamos y consultamos el modelo de caja si el usuario es cajero estándar
        if ($_SESSION['rol'] === 'cajero') {
            require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Caja.php';
            $modeloCaja = new Caja();
            $turnoAbierto = $modeloCaja->obtenerTurnoAbierto((int)$_SESSION['id']);

            // Si NO tiene turno abierto -> va a abrir caja
            if (!$turnoAbierto) {
                $this->redirigir('caja');
                return;
            }
            // Si YA TIENE turno abierto -> va directo a ventas
            $this->redirigir('ventas');
            return;
        }
        $this->redirigir('ventas');
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->redirigir('login');
    }

    private function ipCliente()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
