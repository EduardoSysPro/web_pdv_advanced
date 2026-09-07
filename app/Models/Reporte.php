<?php

require_once CORE_PATH . 'Controller.php';

class Reporte extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function obtenerResumenGeneral($fechaInicio, $fechaFin)
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(v.total),0) AS total_ventas,
                COALESCE(SUM(d.costo_total),0) AS total_costo, COUNT(DISTINCT v.id) AS transacciones
                FROM ventas v LEFT JOIN (SELECT venta_id, SUM(cantidad * COALESCE(factor_unidades, 1.000) * p.precio_costo) AS costo_total FROM detalle_ventas d INNER JOIN productos p ON p.id=d.producto_id GROUP BY venta_id) d ON d.venta_id=v.id
                WHERE v.fecha_venta BETWEEN :inicio AND :fin');
        $stmt->execute([':inicio'=>$fechaInicio, ':fin'=>$fechaFin]);
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
        $resumen['ganancia_neta'] = (float)$resumen['total_ventas'] - (float)$resumen['total_costo'];
        $resumen['promedio_ticket'] = $resumen['transacciones'] ? (float)$resumen['total_ventas'] / $resumen['transacciones'] : 0;
        return $resumen;
    }

    public function obtenerTopProductos($fechaInicio, $fechaFin, $limite = 10)
    {
        $stmt = $this->pdo->prepare('SELECT p.codigo_barras, p.nombre, SUM(d.cantidad * COALESCE(d.factor_unidades, 1.000)) AS unidades_vendidas, SUM(d.subtotal) AS ingreso_total
                FROM detalle_ventas d INNER JOIN ventas v ON v.id=d.venta_id INNER JOIN productos p ON p.id=d.producto_id
                WHERE v.fecha_venta BETWEEN :inicio AND :fin GROUP BY p.id,p.codigo_barras,p.nombre ORDER BY unidades_vendidas DESC, ingreso_total DESC LIMIT :limite');
        $stmt->bindValue(':inicio',$fechaInicio); $stmt->bindValue(':fin',$fechaFin); $stmt->bindValue(':limite',(int)$limite,PDO::PARAM_INT); $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerVentasPorMetodoPago($fechaInicio, $fechaFin)
    {
        $stmt = $this->pdo->prepare('SELECT metodo_pago, COALESCE(SUM(total),0) AS total FROM ventas WHERE fecha_venta BETWEEN :inicio AND :fin GROUP BY metodo_pago');
        $stmt->execute([':inicio'=>$fechaInicio, ':fin'=>$fechaFin]);
        $totales = ['efectivo'=>0,'tarjeta'=>0,'transferencia'=>0,'credito'=>0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) $totales[$fila['metodo_pago']] = (float)$fila['total'];
        return $totales;
    }

    public function obtenerVentas($fechaInicio, $fechaFin)
    {
        $stmt = $this->pdo->prepare('SELECT v.id,v.folio,v.fecha_venta,v.total,v.metodo_pago,u.nombre AS vendedor,COALESCE(c.nombre,\'Consumidor final\') AS cliente FROM ventas v INNER JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN clientes c ON c.id=v.cliente_id WHERE v.fecha_venta BETWEEN :inicio AND :fin ORDER BY v.fecha_venta DESC');
        $stmt->execute([':inicio'=>$fechaInicio, ':fin'=>$fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
