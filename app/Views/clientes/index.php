<?php $tituloPagina = 'Clientes'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>

<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">F2 / Clientes</span>
        <h1>Clientes y crédito</h1>
        <p>Consulta saldos, límites y estados de cuenta.</p>
    </div>
    <button class="btn-pos btn-success" type="button" onclick="document.getElementById('nuevo-cliente').hidden=false">+ Nuevo Cliente</button>
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
        <button class="btn btn-primario">Buscar</button>
        <a class="btn btn-ligero" href="<?php echo URL_BASE; ?>clientes">Limpiar</a>
    </form>

    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead>
                <tr>
                    <th>Nombre / Negocio</th>
                    <th>RTN / Identidad</th>
                    <th>Teléfono</th>
                    <th>Límite crédito</th>
                    <th>Saldo pendiente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$clientes): ?>
                    <tr>
                        <td colspan="6" class="tabla-vacia">No hay clientes registrados.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cliente['rtn_identidad'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?></td>
                        <td><?php echo formatearMoneda($cliente['limite_credito']); ?></td>
                        <td class="<?php echo $cliente['saldo_pendiente'] > 0 ? 'stock-bajo' : ''; ?>">
                            <?php echo formatearMoneda($cliente['saldo_pendiente']); ?>
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
</section>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>