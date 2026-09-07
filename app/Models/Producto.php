<?php

require_once CORE_PATH . 'Controller.php';

class Producto extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    public function buscarPorCodigoBarras($codigoBarras)
    {
        $sql = 'SELECT
                    p.id,
                    p.codigo_barras,
                    p.nombre,
                    p.precio_venta,
                    p.precio_costo,
                    p.stock,
                    p.stock_minimo,
                    p.unidad_medida,
                    p.permite_decimales,
                    p.tipo_venta,
                    p.nombre_empaque,
                    p.unidades_por_empaque,
                    p.precio_empaque,
                    p.codigo_barras_empaque,
                    p.tipo_impuesto,
                    p.porcentaje_isv,
                    p.categoria_id,
                    c.nombre AS categoria_nombre
                FROM productos p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE (p.codigo_barras = :codigo_barras OR p.codigo_barras_empaque = :codigo_barras_emp)
                  AND (p.codigo_barras <> "" OR p.codigo_barras_empaque <> "")
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':codigo_barras', $codigoBarras, PDO::PARAM_STR);
        $stmt->bindParam(':codigo_barras_emp', $codigoBarras, PDO::PARAM_STR);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            $producto['tipo_coincidencia'] = ($producto['codigo_barras_empaque'] === $codigoBarras && $codigoBarras !== '')
                ? 'empaque'
                : 'unidad';
        }

        return $producto;
    }

    public function buscarPorId($id)
    {
        return $this->obtenerPorId($id);
    }

    public function obtenerTodos($busqueda = '', $categoriaId = null, $pagina = 1, $porPagina = 20)
    {
        $pagina = max(1, (int)$pagina);
        $porPagina = max(1, min(100, (int)$porPagina));
        $offset = ($pagina - 1) * $porPagina;
        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(p.nombre LIKE :busqueda OR p.codigo_barras LIKE :busqueda_codigo OR p.codigo_barras_empaque LIKE :busqueda_empaque)';
            $parametros[':busqueda'] = '%' . $busqueda . '%';
            $parametros[':busqueda_codigo'] = '%' . $busqueda . '%';
            $parametros[':busqueda_empaque'] = '%' . $busqueda . '%';
        }
        if ($categoriaId !== null && $categoriaId !== '') {
            $condiciones[] = 'p.categoria_id = :categoria_id';
            $parametros[':categoria_id'] = (int)$categoriaId;
        }

        $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM productos p' . $where);
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        $sql = 'SELECT p.id, p.codigo_barras, p.nombre, p.precio_costo, p.precio_venta,
                       p.stock, p.stock_minimo, p.categoria_id, c.nombre AS categoria_nombre,
                       p.tipo_venta, p.nombre_empaque, p.unidades_por_empaque, p.precio_empaque, p.codigo_barras_empaque,
                       p.tipo_impuesto, p.porcentaje_isv
                FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id' . $where .
                ' ORDER BY p.nombre ASC LIMIT :limite OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['productos' => $stmt->fetchAll(), 'total' => $total, 'pagina' => $pagina, 'porPagina' => $porPagina];
    }

    public function obtenerPorId($id)
    {
        $sql = 'SELECT p.id, p.codigo_barras, p.nombre, p.precio_venta, p.precio_costo,
                       p.stock, p.stock_minimo, p.categoria_id, c.nombre AS categoria_nombre,
                       p.unidad_medida, p.permite_decimales,
                       p.tipo_venta, p.nombre_empaque, p.unidades_por_empaque, p.precio_empaque, p.codigo_barras_empaque,
                       p.tipo_impuesto, p.porcentaje_isv
                FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function codigoBarrasExiste($codigoBarras, $idExcluir = null)
    {
        if (trim((string)$codigoBarras) === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) FROM productos WHERE (codigo_barras = :codigo_barras OR codigo_barras_empaque = :codigo_barras_emp)';
        if ($idExcluir !== null) {
            $sql .= ' AND id <> :id_excluir';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':codigo_barras', $codigoBarras, PDO::PARAM_STR);
        $stmt->bindValue(':codigo_barras_emp', $codigoBarras, PDO::PARAM_STR);
        if ($idExcluir !== null) {
            $stmt->bindValue(':id_excluir', (int)$idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    public function codigoEmpaqueExiste($codigoBarrasEmpaque, $idExcluir = null)
    {
        if (trim((string)$codigoBarrasEmpaque) === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) FROM productos WHERE (codigo_barras = :codigo_barras OR codigo_barras_empaque = :codigo_barras_emp)';
        if ($idExcluir !== null) {
            $sql .= ' AND id <> :id_excluir';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':codigo_barras', $codigoBarrasEmpaque, PDO::PARAM_STR);
        $stmt->bindValue(':codigo_barras_emp', $codigoBarrasEmpaque, PDO::PARAM_STR);
        if ($idExcluir !== null) {
            $stmt->bindValue(':id_excluir', (int)$idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    public function insertar($datos)
    {
        $stmt = $this->pdo->prepare('INSERT INTO productos (codigo_barras, nombre, precio_costo, precio_venta, stock, stock_minimo, unidad_medida, permite_decimales, categoria_id, tipo_venta, nombre_empaque, unidades_por_empaque, precio_empaque, codigo_barras_empaque, tipo_impuesto, porcentaje_isv)
                VALUES (:codigo_barras, :nombre, :precio_costo, :precio_venta, :stock, :stock_minimo, :unidad_medida, :permite_decimales, :categoria_id, :tipo_venta, :nombre_empaque, :unidades_por_empaque, :precio_empaque, :codigo_barras_empaque, :tipo_impuesto, :porcentaje_isv)');
        $this->vincularDatos($stmt, $datos);
        return $stmt->execute();
    }

    public function actualizar($id, $datos)
    {
        $stmt = $this->pdo->prepare('UPDATE productos SET codigo_barras = :codigo_barras, nombre = :nombre,
                precio_costo = :precio_costo, precio_venta = :precio_venta, stock = :stock,
                stock_minimo = :stock_minimo, unidad_medida = :unidad_medida, permite_decimales = :permite_decimales, categoria_id = :categoria_id,
                tipo_venta = :tipo_venta, nombre_empaque = :nombre_empaque, unidades_por_empaque = :unidades_por_empaque,
                precio_empaque = :precio_empaque, codigo_barras_empaque = :codigo_barras_empaque,
                tipo_impuesto = :tipo_impuesto, porcentaje_isv = :porcentaje_isv WHERE id = :id');
        $this->vincularDatos($stmt, $datos);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM productos WHERE id = :id');
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function vincularDatos($stmt, $datos)
    {
        $unidadMedida = isset($datos['unidad_medida']) ? trim((string)$datos['unidad_medida']) : 'unidad';
        $permiteDecimales = !empty($datos['permite_decimales']) ? 1 : 0;
        $tipoVenta = in_array($datos['tipo_venta'] ?? '', ['solo_unidad', 'solo_empaque', 'ambos'], true) ? $datos['tipo_venta'] : 'solo_unidad';
        $nombreEmpaque = trim((string)($datos['nombre_empaque'] ?? 'Caja'));
        if ($nombreEmpaque === '') $nombreEmpaque = 'Caja';
        $unidadesPorEmpaque = max(1.0, (float)($datos['unidades_por_empaque'] ?? 1.0));
        $precioEmpaque = max(0.0, (float)($datos['precio_empaque'] ?? 0.0));
        $codigoBarrasEmpaque = trim((string)($datos['codigo_barras_empaque'] ?? ''));
        $tiposImpuestoValidos = ['exento' => 0.00, 'gravado_15' => 15.00, 'gravado_18' => 18.00, 'exonerado' => 0.00];
        $tipoImpuesto = array_key_exists($datos['tipo_impuesto'] ?? '', $tiposImpuestoValidos) ? $datos['tipo_impuesto'] : 'gravado_15';
        $porcentajeIsv = $tiposImpuestoValidos[$tipoImpuesto];

        $stmt->bindValue(':codigo_barras', $datos['codigo_barras'], PDO::PARAM_STR);
        $stmt->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindValue(':precio_costo', $datos['precio_costo']);
        $stmt->bindValue(':precio_venta', $datos['precio_venta']);
        $stmt->bindValue(':stock', (float)$datos['stock'], PDO::PARAM_STR);
        $stmt->bindValue(':stock_minimo', (float)$datos['stock_minimo'], PDO::PARAM_STR);
        $stmt->bindValue(':unidad_medida', $unidadMedida !== '' ? $unidadMedida : 'unidad', PDO::PARAM_STR);
        $stmt->bindValue(':permite_decimales', $permiteDecimales, PDO::PARAM_INT);
        $stmt->bindValue(':categoria_id', $datos['categoria_id'] === null ? null : (int)$datos['categoria_id'], $datos['categoria_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':tipo_venta', $tipoVenta, PDO::PARAM_STR);
        $stmt->bindValue(':nombre_empaque', $nombreEmpaque, PDO::PARAM_STR);
        $stmt->bindValue(':unidades_por_empaque', $unidadesPorEmpaque, PDO::PARAM_STR);
        $stmt->bindValue(':precio_empaque', $precioEmpaque, PDO::PARAM_STR);
        $stmt->bindValue(':codigo_barras_empaque', $codigoBarrasEmpaque !== '' ? $codigoBarrasEmpaque : null, $codigoBarrasEmpaque !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo_impuesto', $tipoImpuesto, PDO::PARAM_STR);
        $stmt->bindValue(':porcentaje_isv', $porcentajeIsv, PDO::PARAM_STR);
    }

    public function buscarPorNombreOCodigo($termino, $limite = 20)
    {
        $terminoLike = '%' . $termino . '%';
        $sql = 'SELECT id, codigo_barras, nombre, precio_venta, stock,
                       tipo_venta, nombre_empaque, unidades_por_empaque, precio_empaque, codigo_barras_empaque
                FROM productos
                WHERE codigo_barras LIKE :termino
                   OR codigo_barras_empaque LIKE :termino_emp
                   OR nombre LIKE :termino2
                ORDER BY nombre ASC
                LIMIT :limite';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':termino', $terminoLike, PDO::PARAM_STR);
        $stmt->bindValue(':termino_emp', $terminoLike, PDO::PARAM_STR);
        $stmt->bindValue(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarProductosAjax($termino, $limite = 15)
    {
        $terminoLike = '%' . trim($termino) . '%';
        $stmt = $this->pdo->prepare('SELECT id, codigo_barras, nombre, precio_venta, stock, stock_minimo,
                                            unidad_medida, permite_decimales,
                                            tipo_venta, nombre_empaque, unidades_por_empaque, precio_empaque, codigo_barras_empaque
                                     FROM productos
                                     WHERE nombre LIKE :nombre
                                        OR codigo_barras LIKE :codigo
                                        OR codigo_barras_empaque LIKE :codigo_emp
                                     ORDER BY nombre ASC LIMIT :limite');
        $stmt->bindValue(':nombre', $terminoLike, PDO::PARAM_STR);
        $stmt->bindValue(':codigo', $terminoLike, PDO::PARAM_STR);
        $stmt->bindValue(':codigo_emp', $terminoLike, PDO::PARAM_STR);
        $stmt->bindValue(':limite', min(30, max(1, (int)$limite)), PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultados = [];
        foreach ($filas as $p) {
            $tipoVenta = $p['tipo_venta'] ?? 'solo_unidad';
            $factor = max(1.0, (float)($p['unidades_por_empaque'] ?? 1.0));
            $nombreEmpaque = !empty($p['nombre_empaque']) ? $p['nombre_empaque'] : 'Caja';
            $precioEmpaque = (float)($p['precio_empaque'] ?? 0);
            $stockBase = (float)($p['stock'] ?? 0);
            $factorTexto = rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.');

            // 1. Opción individual/unidad si tipo_venta es 'solo_unidad' o 'ambos'
            if ($tipoVenta === 'solo_unidad' || $tipoVenta === 'ambos') {
                $resultados[] = [
                    'id'                => (int)$p['id'],
                    'item_key'          => $p['id'] . '_unidad',
                    'codigo_barras'     => $p['codigo_barras'],
                    'nombre'            => ($tipoVenta === 'ambos' ? '[Unidad] ' : '') . $p['nombre'],
                    'nombre_original'   => $p['nombre'],
                    'precio_venta'      => (float)$p['precio_venta'],
                    'stock'             => $stockBase,
                    'stock_minimo'      => (float)$p['stock_minimo'],
                    'unidad_medida'     => $p['unidad_medida'] ?? 'unidad',
                    'permite_decimales' => !empty($p['permite_decimales']),
                    'tipo_presentacion' => 'unidad',
                    'nombre_presentacion' => 'Unidad',
                    'factor_unidades'   => 1.0
                ];
            }

            // 2. Opción empaque si tipo_venta es 'solo_empaque' o 'ambos'
            if (($tipoVenta === 'solo_empaque' || $tipoVenta === 'ambos') && $precioEmpaque > 0) {
                // Stock disponible expresado en cajas/empaques completos
                $stockEmpaques = $factor > 0 ? floor($stockBase / $factor) : 0;
                $resultados[] = [
                    'id'                => (int)$p['id'],
                    'item_key'          => $p['id'] . '_empaque',
                    'codigo_barras'     => $p['codigo_barras_empaque'] ?: $p['codigo_barras'],
                    'nombre'            => '[' . $nombreEmpaque . ' x' . $factorTexto . '] ' . $p['nombre'],
                    'nombre_original'   => $p['nombre'],
                    'precio_venta'      => $precioEmpaque,
                    'stock'             => (float)$stockEmpaques,
                    'stock_minimo'      => 0,
                    'unidad_medida'     => strtolower($nombreEmpaque),
                    'permite_decimales' => false,
                    'tipo_presentacion' => 'empaque',
                    'nombre_presentacion' => $nombreEmpaque,
                    'factor_unidades'   => $factor
                ];
            }
        }

        return $resultados;
    }

    public function obtenerProductosBajoStock()
    {
        $stmt = $this->pdo->query('SELECT p.id, p.codigo_barras, p.nombre, p.stock, p.stock_minimo,
                                          (p.stock_minimo - p.stock) AS diferencia
                                   FROM productos p
                                   WHERE p.stock <= p.stock_minimo
                                   ORDER BY diferencia DESC, p.nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarStockManual($productoId, $cantidad, $operacion)
    {
        $cantidad = (int)$cantidad;
        if ($cantidad <= 0 || !in_array($operacion, ['entrada', 'salida'], true)) {
            return false;
        }

        $signo = $operacion === 'entrada' ? '+' : '-';
        $condicion = $operacion === 'salida' ? ' AND stock >= :cantidad_condicion' : '';
        $sql = 'UPDATE productos SET stock = stock ' . $signo . ' :cantidad WHERE id = :producto_id' . $condicion;
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindValue(':producto_id', (int)$productoId, PDO::PARAM_INT);
        if ($operacion === 'salida') {
            $stmt->bindValue(':cantidad_condicion', $cantidad, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }
}
