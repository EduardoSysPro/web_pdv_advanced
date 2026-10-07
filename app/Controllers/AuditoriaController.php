<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Auditoria.php';

class AuditoriaController extends Controller
{
    private $modelo;

    public function __construct()
    {
        parent::__construct();
        $this->modelo = new Auditoria();
    }

    public function index()
    {
        $this->requerirAdministrador();

        $filtros = $this->leerFiltros();
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = in_array((int)($_GET['por_pagina'] ?? 0), [20, 50, 100], true)
            ? (int)$_GET['por_pagina'] : 50;

        $resultado = $this->modelo->obtenerLogs($filtros, $pagina, $porPagina);
        $logs = $resultado['logs'];
        $total = $resultado['total'];
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $usuarios = $this->modelo->obtenerUsuariosConActividad();
        $modulos = Auditoria::$modulosValidos;
        $acciones = ['crear', 'actualizar', 'eliminar', 'anular', 'abono', 'pago', 'apertura', 'cierre', 'ingreso', 'egreso', 'ajuste', 'login', 'logout', 'login_fallido', 'configurar', 'facturar', 'cancelar'];
        $tituloPagina = 'Auditoría del sistema';

        require APP_PATH . 'Views/auditoria/index.php';
    }

    public function exportar()
    {
        $this->requerirAdministrador();
        $filtros = $this->leerFiltros();
        $logs = $this->modelo->obtenerParaExportar($filtros);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria-' . date('Ymd_His') . '.csv"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        $delimitador = ';';
        fputcsv($salida, ['Fecha', 'Hora', 'Usuario', 'Rol', 'Módulo', 'Acción', 'Entidad', 'ID/Folio', 'Descripción', 'Monto (L)', 'IP'], $delimitador);
        foreach ($logs as $log) {
            $ts = strtotime($log['creado_en']);
            fputcsv($salida, [
                date('d/m/Y', $ts),
                date('H:i:s', $ts),
                $log['usuario_mostrar'] ?? ($log['usuario_nombre'] ?? 'Sistema'),
                $log['rol'] ?? '',
                $log['modulo'],
                $log['accion'],
                $log['entidad_tipo'] ?? '',
                $log['entidad_id'] ?? '',
                $log['descripcion'] ?? '',
                $log['monto'] !== null ? number_format((float)$log['monto'], 2, '.', '') : '',
                $log['ip'] ?? '',
            ], $delimitador);
        }
        fclose($salida);
        exit;
    }

    private function leerFiltros()
    {
        $fechaDesde = trim($_GET['fecha_desde'] ?? '');
        $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $fechaDesde = date('Y-m-d', strtotime('-30 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $fechaHasta = date('Y-m-d');
        }
        return [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'modulo' => trim($_GET['modulo'] ?? ''),
            'accion' => trim($_GET['accion'] ?? ''),
            'usuario_id' => (int)($_GET['usuario_id'] ?? 0),
            'busqueda' => trim($_GET['busqueda'] ?? ''),
        ];
    }
}
