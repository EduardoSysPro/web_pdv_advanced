<?php $tituloPagina = 'Reimpresión de Comprobantes'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Ventas</span>
        <h1>Reimpresión de Comprobantes</h1>
        <p>Busca y reimprimi facturas, recibos y comprobantes de tus ventas anteriores.</p>
    </div>
    <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>ventas">Volver a ventas</a>
</section>

<?php if ($error): ?>
    <div class="alerta alerta-info"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<section class="tarjeta formulario-producto">
    <h2 class="tarjeta-titulo">Buscar comprobante</h2>
    <form method="GET" action="<?php echo URL_BASE; ?>comprobantes" class="form-grid">
        <div class="campo">
            <label for="tipo">Buscar por</label>
            <select id="tipo" name="tipo" required>
                <option value="folio" <?php echo ($_GET['tipo'] ?? 'folio') === 'folio' ? 'selected' : ''; ?>>Folio/Número</option>
                <option value="cliente" <?php echo ($_GET['tipo'] ?? 'folio') === 'cliente' ? 'selected' : ''; ?>>Nombre del cliente</option>
                <option value="fecha" <?php echo ($_GET['tipo'] ?? 'folio') === 'fecha' ? 'selected' : ''; ?>>Fecha (dd/mm/yyyy o yyyy-mm-dd)</option>
            </select>
        </div>

        <div class="campo campo-ancho">
            <label for="busqueda">Término de búsqueda</label>
            <input id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" placeholder="Ingresa el folio, cliente o fecha..." required>
        </div>

        <div style="grid-column: 1 / -1; display: flex; gap: 8px;">
            <button class="btn-pos btn-primary" type="submit">Buscar</button>
            <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>comprobantes">Limpiar</a>
        </div>
    </form>
</section>

<?php if (!empty($comprobantes)): ?>
    <section class="tarjeta usuarios-lista">
        <h2 class="tarjeta-titulo">Resultados (<?php echo count($comprobantes); ?> comprobantes)</h2>
        <div class="tabla-responsive">
            <table class="tabla-catalogo">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comprobantes as $comprobante): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($comprobante['folio']); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($comprobante['fecha_venta'])); ?></td>
                            <td><?php echo htmlspecialchars($comprobante['cliente'] ?? 'Cliente eventual'); ?></td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; 
                                    <?php echo $comprobante['tipo_comprobante'] === 'factura' ? 'background-color: #dbeafe; color: #0c4a6e;' : 'background-color: #f0fdf4; color: #166534;'; ?>">
                                    <?php echo ucfirst($comprobante['tipo_comprobante']); ?>
                                </span>
                            </td>
                            <td><?php echo formatearMoneda($comprobante['total']); ?></td>
                            <td>
                                <a href="<?php echo URL_BASE; ?>comprobantes/imprimir/<?php echo (int)$comprobante['id']; ?>" class="btn-pos btn-primary" target="_blank">Imprimir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
