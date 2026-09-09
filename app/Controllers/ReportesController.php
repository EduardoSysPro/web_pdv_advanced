<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Reporte.php';

class ReportesController extends Controller
{
    private $modelo;

    public function __construct()
    {
        parent::__construct();
        $this->modelo = new Reporte();
    }

    public function index()
    {
        $this->requerirAdministrador();
        extract($this->datosReporte());
        require APP_PATH . 'Views/reportes/index.php';
    }

    public function imprimir()
    {
        $this->requerirAdministrador();
        extract($this->datosReporte());
        require APP_PATH . 'Views/reportes/imprimir.php';
    }

    public function ventasPorFecha()
    {
        $this->index();
    }

    public function productosMasVendidos()
    {
        $this->index();
    }

    public function ganancias()
    {
        $this->index();
    }

    private function datosReporte()
    {
        list($fechaInicio, $fechaFin) = $this->rangoFechas();
        return [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'resumen' => $this->modelo->obtenerResumenGeneral($fechaInicio, $fechaFin),
            'topProductos' => $this->modelo->obtenerTopProductos($fechaInicio, $fechaFin),
            'ventas' => $this->modelo->obtenerVentas($fechaInicio, $fechaFin),
            'metodosPago' => $this->modelo->obtenerVentasPorMetodoPago($fechaInicio, $fechaFin),
            'reportesCajas' => $this->modelo->obtenerReporteCajas($fechaInicio, $fechaFin),
        ];
    }

    public function exportar()
    {
        $this->requerirAdministrador();
        list($fechaInicio, $fechaFin) = $this->rangoFechas();
        $ventas = $this->modelo->obtenerVentas($fechaInicio, $fechaFin);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte-ventas-' . date('Ymd') . '.csv"');
        $salida = fopen('php://output', 'w');
        fputcsv($salida, ['Folio', 'Fecha', 'Cliente', 'Vendedor', 'Método de pago', 'Total (L)']);
        foreach ($ventas as $venta) fputcsv($salida, [$venta['folio'], $venta['fecha_venta'], $venta['cliente'], $venta['vendedor'], $venta['metodo_pago'], number_format((float)$venta['total'], 2, '.', '')]);
        fclose($salida);
    }

    private function rangoFechas()
    {
        $tipo = $_GET['periodo'] ?? 'hoy';
        $hoy = date('Y-m-d');
        if ($tipo === 'semana') {
            $inicio = date('Y-m-d', strtotime('monday this week'));
        } elseif ($tipo === 'mes') {
            $inicio = date('Y-m-01');
        } elseif ($tipo === 'personalizado') {
            $inicio = $this->fechaValida($_GET['fecha_inicio'] ?? '') ?: $hoy;
            $fin = $this->fechaValida($_GET['fecha_fin'] ?? '') ?: $hoy;
            return [$inicio . ' 00:00:00', $fin . ' 23:59:59'];
        } else {
            $inicio = $hoy;
        }
        return [$inicio . ' 00:00:00', $hoy . ' 23:59:59'];
    }

    private function fechaValida($fecha)
    {
        $fechaObjeto = DateTime::createFromFormat('Y-m-d', $fecha);
        return $fechaObjeto && $fechaObjeto->format('Y-m-d') === $fecha ? $fecha : null;
    }
}
