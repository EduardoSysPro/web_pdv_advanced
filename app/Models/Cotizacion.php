<?php

require_once CORE_PATH . 'Controller.php';

class Cotizacion extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function generarFolio()
    {
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Secuencia.php';
        return (new Secuencia())->siguiente('cotizaciones', 'COT-', 8);
    }

    public function insertarEncabezado($datos)
    {
        $sql = 'INSERT INTO cotizaciones
                    (folio, vendedor_id, cliente_id, cliente_nombre, cliente_rtn, cliente_telefono, cliente_direccion,
                     estado, importe_exento, importe_exonerado, importe_gravado_15, isv_15, importe_gravado_18, isv_18,
                     subtotal, descuento_total, total, observaciones, fecha_validez)
                VALUES
                    (:folio, :vendedor_id, :cliente_id, :cliente_nombre, :cliente_rtn, :cliente_telefono, :cliente_direccion,
                     :estado, :importe_exento, :importe_exonerado, :importe_gravado_15, :isv_15, :importe_gravado_18, :isv_18,
                     :subtotal, :descuento_total, :total, :observaciones, :fecha_validez)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':folio'               => $datos['folio'],
            ':vendedor_id'         => (int)$datos['vendedor_id'],
            ':cliente_id'          => !empty($datos['cliente_id']) ? (int)$datos['cliente_id'] : null,
            ':cliente_nombre'      => $datos['cliente_nombre'] !== '' ? $datos['cliente_nombre'] : null,
            ':cliente_rtn'         => $datos['cliente_rtn'] !== '' ? $datos['cliente_rtn'] : null,
            ':cliente_telefono'    => $datos['cliente_telefono'] !== '' ? $datos['cliente_telefono'] : null,
            ':cliente_direccion'   => $datos['cliente_direccion'] !== '' ? $datos['cliente_direccion'] : null,
            ':estado'              => 'pendiente',
            ':importe_exento'      => $datos['importe_exento'],
            ':importe_exonerado'   => $datos['importe_exonerado'],
            ':importe_gravado_15'  => $datos['importe_gravado_15'],
            ':isv_15'              => $datos['isv_15'],
            ':importe_gravado_18'  => $datos['importe_gravado_18'],
            ':isv_18'              => $datos['isv_18'],
            ':subtotal'            => $datos['subtotal'],
            ':descuento_total'     => $datos['descuento_total'],
            ':total'               => $datos['total'],
            ':observaciones'       => $datos['observaciones'] !== '' ? $datos['observaciones'] : null,
            ':fecha_validez'       => $datos['fecha_validez'] !== '' ? $datos['fecha_validez'] : null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizarEncabezado($id, $datos)
    {
        $stmt = $this->pdo->prepare('UPDATE cotizaciones SET
                    cliente_id = :cliente_id, cliente_nombre = :cliente_nombre, cliente_rtn = :cliente_rtn,
                    cliente_telefono = :cliente_telefono, cliente_direccion = :cliente_direccion,
                    importe_exento = :importe_exento, importe_exonerado = :importe_exonerado,
                    importe_gravado_15 = :importe_gravado_15, isv_15 = :isv_15,
                    importe_gravado_18 = :importe_gravado_18, isv_18 = :isv_18,
                    subtotal = :subtotal, descuento_total = :descuento_total, total = :total,
                    observaciones = :observaciones, fecha_validez = :fecha_validez
                 WHERE id = :id AND estado = :estado');
        return $stmt->execute([
            ':cliente_id'          => !empty($datos['cliente_id']) ? (int)$datos['cliente_id'] : null,
            ':cliente_nombre'      => $datos['cliente_nombre'] !== '' ? $datos['cliente_nombre'] : null,
            ':cliente_rtn'         => $datos['cliente_rtn'] !== '' ? $datos['cliente_rtn'] : null,
            ':cliente_telefono'    => $datos['cliente_telefono'] !== '' ? $datos['cliente_telefono'] : null,
            ':cliente_direccion'   => $datos['cliente_direccion'] !== '' ? $datos['cliente_direccion'] : null,
            ':importe_exento'      => $datos['importe_exento'],
            ':importe_exonerado'   => $datos['importe_exonerado'],
            ':importe_gravado_15'  => $datos['importe_gravado_15'],
            ':isv_15'              => $datos['isv_15'],
            ':importe_gravado_18'  => $datos['importe_gravado_18'],
            ':isv_18'              => $datos['isv_18'],
            ':subtotal'            => $datos['subtotal'],
            ':descuento_total'     => $datos['descuento_total'],
            ':total'               => $datos['total'],
            ':observaciones'       => $datos['observaciones'] !== '' ? $datos['observaciones'] : null,
            ':fecha_validez'       => $datos['fecha_validez'] !== '' ? $datos['fecha_validez'] : null,
            ':id'                  => (int)$id,
            ':estado'              => 'pendiente'
        ]);
    }

    public function insertarDetalle($cotizacionId, $item)
    {
        $stmt = $this->pdo->prepare('INSERT INTO detalle_cotizaciones
                (cotizacion_id, producto_id, nombre_producto, cantidad, precio_lista, precio_unitario,
                 descuento_unitario, subtotal, tipo_presentacion, nombre_presentacion, factor_unidades,
                 porcentaje_isv, monto_isv, es_exento, es_exonerado)
                VALUES
                (:cotizacion_id, :producto_id, :nombre_producto, :cantidad, :precio_lista, :precio_unitario,
                 :descuento_unitario, :subtotal, :tipo_presentacion, :nombre_presentacion, :factor_unidades,
                 :porcentaje_isv, :monto_isv, :es_exento, :es_exonerado)');
        return $stmt->execute([
            ':cotizacion_id'       => (int)$cotizacionId,
            ':producto_id'         => !empty($item['producto_id']) ? (int)$item['producto_id'] : null,
            ':nombre_producto'     => $item['nombre_producto'],
            ':cantidad'            => $item['cantidad'],
            ':precio_lista'        => $item['precio_lista'],
            ':precio_unitario'     => $item['precio_unitario'],
            ':descuento_unitario'  => $item['descuento_unitario'],
            ':subtotal'            => $item['subtotal'],
            ':tipo_presentacion'   => $item['tipo_presentacion'],
            ':nombre_presentacion' => $item['nombre_presentacion'],
            ':factor_unidades'     => $item['factor_unidades'],
            ':porcentaje_isv'      => $item['porcentaje_isv'],
            ':monto_isv'           => $item['monto_isv'],
            ':es_exento'           => $item['es_exento'],
            ':es_exonerado'        => $item['es_exonerado']
        ]);
    }

    public function reemplazarDetalle($cotizacionId, $items)
    {
        $this->pdo->prepare('DELETE FROM detalle_cotizaciones WHERE cotizacion_id = :id')->execute([':id' => (int)$cotizacionId]);
        foreach ($items as $item) {
            if (!$this->insertarDetalle($cotizacionId, $item)) {
                throw new RuntimeException('No se pudieron registrar los artículos de la cotización.');
            }
        }
    }

    public function obtenerConteos($vendedorId = null)
    {
        $condicion = '';
        $params = [];
        if ($vendedorId !== null && (int)$vendedorId > 0) {
            $condicion = ' WHERE vendedor_id = :vendedor_id';
            $params[':vendedor_id'] = (int)$vendedorId;
        }
        $stmt = $this->pdo->prepare('SELECT estado, COUNT(*) AS total FROM cotizaciones' . $condicion . ' GROUP BY estado');
        $stmt->execute($params);
        $conteos = ['pendiente' => 0, 'facturada' => 0, 'cancelada' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $conteos[$fila['estado']] = (int)$fila['total'];
        }
        return $conteos;
    }

    public function obtenerTodas($estado = null, $vendedorId = null, $limite = 200)
    {
        $condiciones = [];
        $params = [];
        if ($estado !== null && $estado !== '') {
            $condiciones[] = 'c.estado = :estado';
            $params[':estado'] = $estado;
        }
        if ($vendedorId !== null && (int)$vendedorId > 0) {
            $condiciones[] = 'c.vendedor_id = :vendedor_id';
            $params[':vendedor_id'] = (int)$vendedorId;
        }
        $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
        $limite = max(1, min(1000, (int)$limite));
        $sql = 'SELECT c.id, c.folio, c.estado, c.total, c.cliente_nombre, c.cliente_rtn,
                       c.observaciones, c.fecha_validez, c.venta_id, c.creada_en,
                       u.nombre AS vendedor
                FROM cotizaciones c
                INNER JOIN usuarios u ON u.id = c.vendedor_id' . $where . '
                ORDER BY c.creada_en DESC LIMIT ' . $limite;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT c.*, u.nombre AS vendedor
                                     FROM cotizaciones c
                                     INNER JOIN usuarios u ON u.id = c.vendedor_id
                                     WHERE c.id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalles($id)
    {
        $selMay = $this->columnaExiste('productos', 'precio_mayorista') ? ', p.precio_mayorista' : '';
        $stmt = $this->pdo->prepare('SELECT dc.*, p.nombre AS producto_actual, p.imagen AS producto_imagen,
                                            p.codigo_barras AS codigo_barras, p.stock AS stock_actual' . $selMay . '
                                     FROM detalle_cotizaciones dc
                                     LEFT JOIN productos p ON p.id = dc.producto_id
                                     WHERE dc.cotizacion_id = :id
                                     ORDER BY dc.id ASC');
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function columnaExiste($tabla, $columna)
    {
        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
        $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
        if ($tabla === '' || $columna === '') {
            return false;
        }
        static $cache = [];
        $clave = $tabla . '.' . $columna;
        if (array_key_exists($clave, $cache)) {
            return $cache[$clave];
        }
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
            return $cache[$clave] = ($stmt !== false && $stmt->rowCount() > 0);
        } catch (Throwable $e) {
            return $cache[$clave] = false;
        }
    }

    public function marcarFacturada($id, $ventaId)
    {
        $stmt = $this->pdo->prepare('UPDATE cotizaciones SET estado = :facturada, venta_id = :venta_id WHERE id = :id AND estado = :pendiente');
        return $stmt->execute([':facturada' => 'facturada', ':venta_id' => (int)$ventaId, ':id' => (int)$id, ':pendiente' => 'pendiente']);
    }

    public function cancelar($id)
    {
        $stmt = $this->pdo->prepare('UPDATE cotizaciones SET estado = :cancelada WHERE id = :id AND estado = :pendiente');
        return $stmt->execute([':cancelada' => 'cancelada', ':id' => (int)$id, ':pendiente' => 'pendiente']);
    }
}