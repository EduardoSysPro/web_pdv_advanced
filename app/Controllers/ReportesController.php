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
        list($fechaInicio, $fechaFin, $periodo) = $this->rangoFechas();
        $vendedorId = max(0, (int)($_GET['vendedor_id'] ?? 0));
        $cajaId = max(0, (int)($_GET['caja_id'] ?? 0));
        $categoriaId = max(0, (int)($_GET['categoria_id'] ?? 0));
        list($antInicio, $antFin) = $this->periodoAnterior($fechaInicio, $fechaFin);
        $resumen = $this->modelo->obtenerResumenGeneral($fechaInicio, $fechaFin, $vendedorId, $cajaId);
        $resumenAnt = $this->modelo->obtenerResumenGeneral($antInicio, $antFin, $vendedorId, $cajaId);
        return [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'periodo' => $periodo,
            'vendedorId' => $vendedorId,
            'cajaId' => $cajaId,
            'categoriaId' => $categoriaId,
            'resumen' => $resumen,
            'variacion' => $this->variacion($resumen, $resumenAnt),
            'topProductos' => $this->modelo->obtenerTopProductos($fechaInicio, $fechaFin, 10, $categoriaId, $vendedorId, $cajaId),
            'ventas' => $this->modelo->obtenerVentas($fechaInicio, $fechaFin, 500, $vendedorId, $cajaId),
            'ventasPorDia' => $this->modelo->obtenerVentasPorDia($fechaInicio, $fechaFin, $vendedorId, $cajaId),
            'metodosPago' => $this->modelo->obtenerVentasPorMetodoPago($fechaInicio, $fechaFin, $vendedorId, $cajaId),
            'reportesCajas' => $this->modelo->obtenerReporteCajas($fechaInicio, $fechaFin),
            'vendedores' => $this->listaVendedores(),
            'cajas' => $this->listaCajas(),
            'categorias' => $this->listaCategorias(),
        ];
    }

    public function exportar()
    {
        $this->requerirAdministrador();
        list($fechaInicio, $fechaFin) = $this->rangoFechas();
        $vendedorId = max(0, (int)($_GET['vendedor_id'] ?? 0));
        $cajaId = max(0, (int)($_GET['caja_id'] ?? 0));
        $ventas = $this->modelo->obtenerVentas($fechaInicio, $fechaFin, 0, $vendedorId, $cajaId);
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
        if ($tipo === 'ayer') {
            $ayer = date('Y-m-d', strtotime('-1 day'));
            return [$ayer . ' 00:00:00', $ayer . ' 23:59:59', 'ayer'];
        }
        if ($tipo === 'semana') {
            $inicio = date('Y-m-d', strtotime('monday this week'));
        } elseif ($tipo === 'ultimos7') {
            $inicio = date('Y-m-d', strtotime('-6 days'));
        } elseif ($tipo === 'mes') {
            $inicio = date('Y-m-01');
        } elseif ($tipo === 'ultimos30') {
            $inicio = date('Y-m-d', strtotime('-29 days'));
        } elseif ($tipo === 'mes_anterior') {
            $inicio = date('Y-m-01', strtotime('first day of last month'));
            $fin = date('Y-m-t', strtotime('last month'));
            return [$inicio . ' 00:00:00', $fin . ' 23:59:59', 'mes_anterior'];
        } elseif ($tipo === 'personalizado') {
            $inicio = $this->fechaValida($_GET['fecha_inicio'] ?? '') ?: $hoy;
            $fin = $this->fechaValida($_GET['fecha_fin'] ?? '') ?: $hoy;
            if ($fin < $inicio) {
                $fin = $inicio;
            }
            return [$inicio . ' 00:00:00', $fin . ' 23:59:59', 'personalizado'];
        } else {
            $tipo = 'hoy';
            $inicio = $hoy;
        }
        return [$inicio . ' 00:00:00', $hoy . ' 23:59:59', $tipo];
    }

    private function periodoAnterior($fechaInicio, $fechaFin)
    {
        $dias = max(1, (int)round((strtotime(substr($fechaFin, 0, 10)) - strtotime(substr($fechaInicio, 0, 10))) / 86400) + 1);
        $finAnt = date('Y-m-d', strtotime(substr($fechaInicio, 0, 10) . ' -1 day'));
        $inicioAnt = date('Y-m-d', strtotime($finAnt . ' -' . ($dias - 1) . ' days'));
        return [$inicioAnt . ' 00:00:00', $finAnt . ' 23:59:59'];
    }

    private function variacion($actual, $anterior)
    {
        $pct = function ($a, $b) {
            $a = (float)$a;
            $b = (float)$b;
            if ($b <= 0) {
                return $a > 0 ? 100.0 : 0.0;
            }
            return round(($a - $b) / $b * 100, 1);
        };
        return [
            'ventas' => $pct($actual['total_ventas'] ?? 0, $anterior['total_ventas'] ?? 0),
            'ganancia' => $pct($actual['ganancia_neta'] ?? 0, $anterior['ganancia_neta'] ?? 0),
            'tickets' => $pct($actual['transacciones'] ?? 0, $anterior['transacciones'] ?? 0),
            'ticket_prom' => $pct($actual['promedio_ticket'] ?? 0, $anterior['promedio_ticket'] ?? 0),
        ];
    }

    private function listaVendedores()
    {
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Usuario.php';
        $todos = (new Usuario())->obtenerTodos();
        return array_values(array_filter($todos, static function ($u) {
            return in_array(strtolower((string)($u['rol'] ?? '')), ['admin', 'administrador', 'cajero', 'vendedor', 'cajero_movil'], true);
        }));
    }

    private function listaCajas()
    {
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Caja.php';
        try {
            return (new Caja())->obtenerCajasDisponibles();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function listaCategorias()
    {
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Categoria.php';
        try {
            return (new Categoria())->obtenerTodas();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function fechaValida($fecha)
    {
        $fechaObjeto = DateTime::createFromFormat('Y-m-d', $fecha);
        return $fechaObjeto && $fechaObjeto->format('Y-m-d') === $fecha ? $fecha : null;
    }
}
