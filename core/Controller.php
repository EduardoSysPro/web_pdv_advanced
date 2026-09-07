<?php

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

    $rol = $_SESSION['rol'] ?? 'cajero';

    // Si es cajero móvil, limitar su acceso exclusivamente al módulo móvil y sus endpoints necesarios
    if ($rol === 'cajero_movil') {
        $url = trim($_GET['url'] ?? '', '/');
        $rutasPermitidasMovil = [
            'movil',
            'logout',
            'ventas/buscar-producto',
            'ventas/buscar-productos',
            'ventas/buscarClientePorRtn',
            'ventas/buscar-cliente-por-rtn',
            'clientes/buscar',
            'clientes/guardar-ajax',
            'ventas/guardar'
        ];
        $esTicketVenta = strpos($url, 'ventas/ticket/') === 0;

        if (!in_array($url, $rutasPermitidasMovil, true) && !$esTicketVenta) {
            $this->redirigir('movil');
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

    protected function requerirAdministrador()
    {
        $this->requerirAutenticacion();
        $rol = strtolower((string)($_SESSION['rol'] ?? ''));
        $rolId = (int)($_SESSION['rol_id'] ?? 0);
        if ($rolId !== 1 && !in_array($rol, ['admin', 'administrador'], true)) {
            $_SESSION['error_usuarios'] = 'No tienes permisos para acceder a esta sección.';
            $this->redirigir('ventas');
            exit;
        }
    }
}
