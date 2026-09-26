<?php $tituloPagina = 'Clientes'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>

<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">F2 / Clientes</span>
        <h1>Clientes y crédito</h1>
        <p>Consulta saldos, límites y estados de cuenta.</p>
    </div>
    <div>
        <a class="btn-pos btn-pos-primary" href="<?php echo URL_BASE; ?>clientes/cartera">Cartera CxC</a>
        <button class="btn-pos btn-success" type="button" onclick="document.getElementById('nuevo-cliente').hidden=false">+ Nuevo Cliente</button>
    </div>
</section>

<?php if ($mensaje): ?>
    <div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>

<section class="tarjeta card-form" id="nuevo-cliente" hidden>
    <h2 class="tarjeta-titulo">
        <span class="formulario-icono" aria-hidden="true">+</span>Nuevo Cliente
    </h2>
    <?php require APP_PATH . 'Views/clientes/formulario.php'; ?>
</section>

<section class="tarjeta catalogo-panel">
    <form class="filtros-productos" method="GET">
        <input type="search" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar nombre, RTN o identidad...">
        <select name="tipo" onchange="this.form.submit()">
            <option value="" <?php echo $tipo === '' ? 'selected' : ''; ?>>Todas las categorías</option>
            <option value="minorista" <?php echo $tipo === 'minorista' ? 'selected' : ''; ?>>Minoristas</option>
            <option value="mayorista" <?php echo $tipo === 'mayorista' ? 'selected' : ''; ?>>Mayoristas</option>
        </select>
        <button class="btn btn-primario">Buscar</button>
        <a class="btn btn-ligero" href="<?php echo URL_BASE; ?>clientes">Limpiar</a>
    </form>
    <p><small><?php echo (int)$paginacion['total']; ?> cliente(s) · página <?php echo (int)$paginacion['pagina']; ?> de <?php echo (int)$paginacion['paginas']; ?></small></p>

    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead>
                <tr>
                    <th>Nombre / Negocio</th>
                    <th>RTN / Identidad</th>
                    <th>Teléfono</th>
                    <th>Categoría</th>
                    <th>Límite crédito</th>
                    <th>Saldo pendiente</th>
                    <th>Vencido</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$clientes): ?>
                    <tr>
                        <td colspan="8" class="tabla-vacia">No hay clientes registrados.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($clientes as $cliente): ?>
                    <?php $moraCliente = isset($vencidos[(int)$cliente['id']]) ? (float)$vencidos[(int)$cliente['id']] : 0.0; ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cliente['rtn_identidad'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?></td>
                        <td><?php echo (($cliente['tipo'] ?? 'minorista') === 'mayorista') ? 'Mayorista' : 'Minorista'; ?></td>
                        <td><?php echo formatearMoneda($cliente['limite_credito']); ?></td>
                        <td class="<?php echo $cliente['saldo_pendiente'] > 0 ? 'stock-bajo' : ''; ?>">
                            <?php echo formatearMoneda($cliente['saldo_pendiente']); ?>
                        </td>
                        <td class="<?php echo $moraCliente > 0.005 ? 'stock-bajo' : ''; ?>">
                            <?php echo $moraCliente > 0.005 ? formatearMoneda($moraCliente) : '—'; ?>
                        </td>
                        <td class="acciones">
                            <a class="btn btn-pequeno btn-primario" href="<?php echo URL_BASE; ?>clientes/editar/<?php echo (int)$cliente['id']; ?>">Editar</a>
                            <a class="btn btn-pequeno btn-exito" href="<?php echo URL_BASE; ?>clientes/estado-cuenta/<?php echo (int)$cliente['id']; ?>">Estado de cuenta</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paginacion['paginas'] > 1): ?>
        <?php
        $basePag = URL_BASE . 'clientes?busqueda=' . urlencode($busqueda) . '&tipo=' . urlencode($tipo) . '&pagina=';
        $desde = max(1, $paginacion['pagina'] - 2);
        $hasta = min($paginacion['paginas'], $paginacion['pagina'] + 2);
        ?>
        <div class="paginacion">
            <?php if ($paginacion['pagina'] > 1): ?>
                <a class="btn btn-pequeno btn-ligero" href="<?php echo $basePag . ($paginacion['pagina'] - 1); ?>">« Anterior</a>
            <?php endif; ?>
            <?php for ($p = $desde; $p <= $hasta; $p++): ?>
                <?php if ($p === $paginacion['pagina']): ?>
                    <span class="btn btn-pequeno btn-primario"><?php echo $p; ?></span>
                <?php else: ?>
                    <a class="btn btn-pequeno btn-ligero" href="<?php echo $basePag . $p; ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($paginacion['pagina'] < $paginacion['paginas']): ?>
                <a class="btn btn-pequeno btn-ligero" href="<?php echo $basePag . ($paginacion['pagina'] + 1); ?>">Siguiente »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>