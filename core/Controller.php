<?php

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_verificar')) {
    function csrf_verificar($presentado)
    {
        return is_string($presentado)
            && $presentado !== ''
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $presentado);
    }
}

class Controller
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function estaAutenticado()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

   protected function requerirAutenticacion()
{
    if (!$this->estaAutenticado()) {
        $this->redirigir('login');
        exit;
    }

    $rol = $_SESSION['rol'] ?? '';

    // Fail-closed: sesión sin rol válido no puede operar.
    if (!in_array($rol, ['admin', 'administrador', 'cajero', 'vendedor', 'cajero_movil'], true)) {
        $this->redirigir('login');
        exit;
    }

    // Vendedor (y Cajero Móvil, que ahora Funciona como vendedor):
    // acceso limitado al módulo de cotizaciones, la terminal móvil y endpoints necesarios
    if (in_array($rol, ['vendedor', 'cajero_movil'], true)) {
        $url = trim($_GET['url'] ?? '', '/');
        $rutasPermitidasMovil = [
            'movil',
            'login',
            'logout',
            'cotizaciones',
            'cotizaciones/crear',
            'cotizaciones/ver',
            'cotizaciones/imprimir',
            'cotizaciones/editar',
            'cotizaciones/cancelar',
            'cotizaciones/guardar',
            'cotizaciones/actualizar',
            'cotizaciones/buscar-productos',
            'cotizaciones/buscar-por-codigo',
            'ventas/buscar-producto',
            'ventas/buscar-productos',
            'ventas/buscarClientePorRtn',
            'ventas/buscar-cliente-por-rtn',
            'clientes/buscar',
            'clientes/guardar-ajax'
        ];
        $permisoBase = function ($base, $ruta) {
            return $ruta === $base || strpos($ruta, $base . '/') === 0;
        };
        $permitido = false;
        foreach ($rutasPermitidasMovil as $base) {
            if ($permisoBase($base, $url)) {
                $permitido = true;
                break;
            }
        }
        if (!$permitido) {
            $this->redirigir('cotizaciones');
            exit;
        }
        return;
    }

    if ($rol === 'cajero') {
        $url = trim($_GET['url'] ?? '', '/');

        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Caja.php';
        $modeloCaja = new Caja();
        $turno = $modeloCaja->obtenerTurnoAbierto((int)$_SESSION['id']);

        // CASO 1: NO tiene turno abierto y quiere navegar a un módulo que NO es caja o logout -> Muestra advertencia y manda a Caja
        $rutasPermitidasSinTurno = ['caja', 'caja/abrir', 'logout'];
        // Permitir acceso a impresión de comprobantes de corte cerrados
        $esImpresionCorte = strpos($url, 'caja/ticket/') === 0;
        if (!$turno && !in_array($url, $rutasPermitidasSinTurno, true) && !$esImpresionCorte) {
            $_SESSION['error_caja'] = 'Debes abrir caja antes de realizar cualquier otra acción.';
            $this->redirigir('caja');
            exit;
        }

        // CASO 2: Intentar procesar de nuevo la acción de abrir teniendo ya un turno activo -> Redirigir a Ventas
        if ($turno && $url === 'caja/abrir') {
            $this->redirigir('ventas');
            exit;
        }
    }
}

    protected function vista($ruta, $datos = [])
    {
        extract($datos);
        $archivoVista = APP_PATH . 'Views' . DIRECTORY_SEPARATOR . $ruta . '.php';
        if (file_exists($archivoVista)) {
            require $archivoVista;
        } else {
            die('Vista no encontrada: ' . $ruta . ' (ruta absoluta: ' . $archivoVista . ')');
        }
    }

    protected function redirigir($url)
    {
        header('Location: ' . URL_BASE . ltrim($url, '/'));
        exit;
    }

    protected function esAdmin()
    {
        $rol = strtolower((string)($_SESSION['rol'] ?? ''));
        // Fuente de verdad: nombre del rol. rol_id se conserva por compatibilidad.
        return in_array($rol, ['admin', 'administrador'], true) || (int)($_SESSION['rol_id'] ?? 0) === 1;
    }

    protected function requerirAdministrador()
    {
        $this->requerirAutenticacion();
        if (!$this->esAdmin()) {
            $_SESSION['error_usuarios'] = 'No tienes permisos para acceder a esta sección.';
            $this->redirigir('ventas');
            exit;
        }
    }
}
