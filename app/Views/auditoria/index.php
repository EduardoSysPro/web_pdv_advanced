<?php
$tituloPagina = 'Auditoría del sistema';
require APP_PATH . 'Views/layouts/pos_header.php';

$queryBase = function ($sobrescribir = []) use ($filtros, $porPagina) {
    $params = array_merge([
        'fecha_desde' => $filtros['fecha_desde'],
        'fecha_hasta' => $filtros['fecha_hasta'],
        'modulo' => $filtros['modulo'],
        'accion' => $filtros['accion'],
        'usuario_id' => $filtros['usuario_id'],
        'busqueda' => $filtros['busqueda'],
        'por_pagina' => $porPagina,
    ], $sobrescribir);
    return http_build_query(array_filter($params, function ($v) {
        return $v !== '' && $v !== 0 && $v !== null;
    }));
};
?>

<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Administración / Control</span>
        <h1>Auditoría del sistema</h1>
        <p>Quién hizo qué, cuándo y por cuánto: facturas, recibos, clientes, inventario y caja.</p>
    </div>
    <div class="reporte-acciones">
        <a class="btn-pos btn-pos-primary" href="<?php echo URL_BASE; ?>auditoria/exportar?<?php echo $queryBase(); ?>">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
    </div>
</section>

<section class="tarjeta filtros-reporte">
    <form method="GET" action="<?php echo URL_BASE; ?>auditoria">
        <div class="grupo-filtro">
            <label for="f_desde">Desde</label>
            <input type="date" id="f_desde" name="fecha_desde" value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>">
        </div>
        <div class="grupo-filtro">
            <label for="f_hasta">Hasta</label>
            <input type="date" id="f_hasta" name="fecha_hasta" value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>">
        </div>
        <div class="grupo-filtro">
            <label for="f_modulo">Módulo</label>
            <select id="f_modulo" name="modulo">
                <option value="">Todos</option>
                <?php foreach ($modulos as $m): ?>
                    <option value="<?php echo $m; ?>" <?php echo $filtros['modulo'] === $m ? 'selected' : ''; ?>><?php echo ucfirst($m); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grupo-filtro">
            <label for="f_accion">Acción</label>
            <select id="f_accion" name="accion">
                <option value="">Todas</option>
                <?php foreach ($acciones as $a): ?>
                    <option value="<?php echo $a; ?>" <?php echo $filtros['accion'] === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grupo-filtro">
            <label for="f_usuario">Usuario</label>
            <select id="f_usuario" name="usuario_id">
                <option value="0">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?php echo (int)$u['usuario_id']; ?>" <?php echo (int)$filtros['usuario_id'] === (int)$u['usuario_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grupo-filtro">
            <label for="f_q">Buscar</label>
            <input type="text" id="f_q" name="busqueda" placeholder="Folio, cliente, descripción..." value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
        </div>
        <button type="submit" class="btn-pos btn-pos-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
        <a class="btn-pos btn-pos-secondary" href="<?php echo URL_BASE; ?>auditoria">Limpiar</a>
    </form>
</section>

<section class="tarjeta">
    <h2 class="tarjeta-titulo">Movimientos (<?php echo number_format($total); ?> registros)</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Usuario</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>Detalle</th>
                    <th>Monto</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$logs): ?>
                    <tr><td colspan="7" class="tabla-vacia">No hay movimientos para los filtros seleccionados. Si acabas de instalar, ejecuta <code>sql/migracion_auditoria_logs.sql</code>.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['creado_en'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($log['usuario_mostrar'] ?? 'Sistema'); ?></strong><?php if (!empty($log['rol'])): ?><br><small><?php echo htmlspecialchars($log['rol']); ?></small><?php endif; ?></td>
                        <td><span class="badge-metodo badge-<?php echo htmlspecialchars($log['modulo']); ?>"><?php echo htmlspecialchars($log['modulo']); ?></span></td>
                        <td><?php echo htmlspecialchars($log['accion']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($log['descripcion'] ?? ($log['entidad_tipo'] . ' ' . $log['entidad_id'])); ?></strong>
                            <?php if (!empty($log['entidad_id'])): ?><br><small><?php echo htmlspecialchars(($log['entidad_tipo'] ?? '') . ' #' . $log['entidad_id']); ?></small><?php endif; ?>
                        </td>
                        <td><?php echo $log['monto'] !== null ? 'L ' . number_format((float)$log['monto'], 2) : '-'; ?></td>
                        <td><small><?php echo htmlspecialchars($log['ip'] ?? '-'); ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <div class="paginacion" style="display:flex;gap:8px;align-items:center;margin-top:12px;">
            <?php if ($pagina > 1): ?>
                <a class="btn-pos btn-pos-secondary btn-pequeno" href="<?php echo URL_BASE; ?>auditoria?<?php echo $queryBase(['pagina' => $pagina - 1]); ?>">« Anterior</a>
            <?php endif; ?>
            <span>Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></span>
            <?php if ($pagina < $totalPaginas): ?>
                <a class="btn-pos btn-pos-secondary btn-pequeno" href="<?php echo URL_BASE; ?>auditoria?<?php echo $queryBase(['pagina' => $pagina + 1]); ?>">Siguiente »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
