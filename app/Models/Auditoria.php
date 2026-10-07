<?php

/**
 * Modelo Auditoria — bitácora central del sistema.
 *
 * Uso:
 *   Auditoria::registrar('ventas', 'crear', [
 *     'entidad_tipo' => 'venta', 'entidad_id' => $folio,
 *     'descripcion'  => "Venta $folio por L 1,250.00",
 *     'monto'        => $total, 'datos' => ['metodo_pago' => $metodoPago],
 *   ]);
 *
 * Nunca lanza excepciones: si la tabla no existe o falla el INSERT,
 * se ignora silenciosamente para no romper la operación principal.
 */
class Auditoria
{
    public static $modulosValidos = [
        'auth', 'ventas', 'compras', 'clientes', 'productos', 'proveedores',
        'categorias', 'inventario', 'caja', 'usuarios', 'configuracion',
        'comprobantes', 'reportes', 'sistema', 'cotizaciones', 'cocina',
    ];

    /**
     * Registra un evento. $pdoOpcional permite reutilizar la transacción activa.
     */
    public static function registrar($modulo, $accion, array $datos = [], $pdoOpcional = null)
    {
        try {
            $pdo = $pdoOpcional instanceof PDO
                ? $pdoOpcional
                : Database::getInstancia()->getConexion();

            // Si la tabla aún no fue migrada, no hacer nada.
            static $tablaExiste = null;
            if ($tablaExiste === null) {
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'auditoria_logs'");
                    $tablaExiste = $stmt !== false && $stmt->rowCount() > 0;
                } catch (Throwable $e) {
                    return false;
                }
            }
            if (!$tablaExiste) {
                return false;
            }

            $usuarioId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
            if ($usuarioId <= 0) {
                $usuarioId = isset($datos['usuario_id']) ? (int)$datos['usuario_id'] : null;
                if ($usuarioId <= 0) {
                    $usuarioId = null;
                }
            }

            $modulo = substr(trim((string)$modulo), 0, 30);
            $accion = substr(trim((string)$accion), 0, 30);
            if ($modulo === '' || $accion === '') {
                return false;
            }

            $usuarioNombre = $_SESSION['nombre'] ?? ($datos['usuario_nombre'] ?? null);
            $rol = $_SESSION['rol'] ?? ($datos['rol'] ?? null);
            $entidadTipo = isset($datos['entidad_tipo']) ? substr((string)$datos['entidad_tipo'], 0, 30) : null;
            $entidadId = isset($datos['entidad_id']) ? substr((string)$datos['entidad_id'], 0, 50) : null;
            $descripcion = isset($datos['descripcion']) ? substr((string)$datos['descripcion'], 0, 255) : null;
            $monto = isset($datos['monto']) && is_numeric($datos['monto']) ? round((float)$datos['monto'], 2) : null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? ($datos['ip'] ?? null);
            $ip = $ip !== null ? substr((string)$ip, 0, 45) : null;

            $detalle = $datos['datos'] ?? null;
            if (is_array($detalle)) {
                $detalle = json_encode($detalle, JSON_UNESCAPED_UNICODE);
            } elseif ($detalle !== null) {
                $detalle = substr((string)$detalle, 0, 65535);
            }

            $stmt = $pdo->prepare('INSERT INTO auditoria_logs
                (usuario_id, usuario_nombre, rol, modulo, accion, entidad_tipo, entidad_id, descripcion, monto, ip, datos)
                VALUES (:usuario_id, :usuario_nombre, :rol, :modulo, :accion, :entidad_tipo, :entidad_id, :descripcion, :monto, :ip, :datos)');
            $stmt->bindValue(':usuario_id', $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':usuario_nombre', $usuarioNombre !== null ? (string)$usuarioNombre : null, $usuarioNombre === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':rol', $rol !== null ? (string)$rol : null, $rol === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':modulo', $modulo, PDO::PARAM_STR);
            $stmt->bindValue(':accion', $accion, PDO::PARAM_STR);
            $stmt->bindValue(':entidad_tipo', $entidadTipo, $entidadTipo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':entidad_id', $entidadId, $entidadId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $descripcion, $descripcion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':monto', $monto, $monto === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':ip', $ip, $ip === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':datos', $detalle, $detalle === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function tablaExiste($pdo)
    {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'auditoria_logs'");
            return $stmt !== false && $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Consulta paginada con filtros para la vista de auditoría.
     */
    public function obtenerLogs(array $filtros = [], $pagina = 1, $porPagina = 50)
    {
        $pdo = Database::getInstancia()->getConexion();
        if (!self::tablaExiste($pdo)) {
            return ['logs' => [], 'total' => 0];
        }

        $condiciones = ['1=1'];
        $params = [];

        if (!empty($filtros['fecha_desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['fecha_desde'])) {
            $condiciones[] = 'a.creado_en >= :desde';
            $params[':desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['fecha_hasta'])) {
            $condiciones[] = 'a.creado_en <= :hasta';
            $params[':hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }
        if (!empty($filtros['modulo'])) {
            $condiciones[] = 'a.modulo = :modulo';
            $params[':modulo'] = substr((string)$filtros['modulo'], 0, 30);
        }
        if (!empty($filtros['accion'])) {
            $condiciones[] = 'a.accion = :accion';
            $params[':accion'] = substr((string)$filtros['accion'], 0, 30);
        }
        if (!empty($filtros['usuario_id'])) {
            $condiciones[] = 'a.usuario_id = :usuario_id';
            $params[':usuario_id'] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['busqueda'])) {
            $condiciones[] = '(a.descripcion LIKE :q OR a.entidad_id LIKE :q OR a.usuario_nombre LIKE :q)';
            $params[':q'] = '%' . trim((string)$filtros['busqueda']) . '%';
        }

        $where = implode(' AND ', $condiciones);

        $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM auditoria_logs a WHERE {$where}");
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        $pagina = max(1, (int)$pagina);
        $porPagina = min(200, max(10, (int)$porPagina));
        $offset = ($pagina - 1) * $porPagina;

        $stmt = $pdo->prepare("SELECT a.*, COALESCE(u.nombre, a.usuario_nombre, 'Sistema') AS usuario_mostrar
            FROM auditoria_logs a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE {$where}
            ORDER BY a.creado_en DESC, a.id DESC
            LIMIT {$porPagina} OFFSET {$offset}");
        $stmt->execute($params);

        return ['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total];
    }

    public function obtenerParaExportar(array $filtros = [], $limite = 5000)
    {
        $resultado = $this->obtenerLogs($filtros, 1, min(5000, max(1, (int)$limite)));
        return $resultado['logs'];
    }

    public function obtenerUsuariosConActividad()
    {
        $pdo = Database::getInstancia()->getConexion();
        if (!self::tablaExiste($pdo)) {
            return [];
        }
        try {
            return $pdo->query("SELECT DISTINCT a.usuario_id, COALESCE(u.nombre, a.usuario_nombre, 'Sistema') AS nombre
                FROM auditoria_logs a LEFT JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.usuario_id IS NOT NULL ORDER BY nombre ASC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}
