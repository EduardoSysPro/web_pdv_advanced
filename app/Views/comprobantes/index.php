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
        <div class="campo campo-ancho">
            <label for="q">Folio, cliente, RTN o #ID</label>
            <input id="q" name="q" value="<?php echo htmlspecialchars($filtrosVista['q'] ?? ''); ?>" placeholder="Ej. REC-00000012, Juan, 0801... o 15" autofocus>
        </div>

        <div class="campo">
            <label for="desde">Desde</label>
            <input id="desde" type="date" name="desde" value="<?php echo htmlspecialchars($filtrosVista['desde'] ?? ''); ?>">
        </div>

        <div class="campo">
            <label for="hasta">Hasta</label>
            <input id="hasta" type="date" name="hasta" value="<?php echo htmlspecialchars($filtrosVista['hasta'] ?? ''); ?>">
        </div>

        <div class="campo">
            <label for="tipo_comprobante">Tipo de comprobante</label>
            <select id="tipo_comprobante" name="tipo_comprobante">
                <option value="all" <?php echo ($filtrosVista['tipo_comprobante'] ?? 'all') === 'all' ? 'selected' : ''; ?>>Todos</option>
                <option value="factura" <?php echo ($filtrosVista['tipo_comprobante'] ?? '') === 'factura' ? 'selected' : ''; ?>>Solo facturas</option>
                <option value="recibo" <?php echo ($filtrosVista['tipo_comprobante'] ?? '') === 'recibo' ? 'selected' : ''; ?>>Solo recibos</option>
            </select>
        </div>

        <div class="campo">
            <label for="metodo_pago">Método de pago</label>
            <select id="metodo_pago" name="metodo_pago">
                <?php $mp = $filtrosVista['metodo_pago'] ?? 'all'; ?>
                <option value="all" <?php echo $mp === 'all' ? 'selected' : ''; ?>>Todos</option>
                <option value="efectivo" <?php echo $mp === 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                <option value="tarjeta" <?php echo $mp === 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                <option value="transferencia" <?php echo $mp === 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                <option value="credito" <?php echo $mp === 'credito' ? 'selected' : ''; ?>>Crédito</option>
                <option value="mixto" <?php echo $mp === 'mixto' ? 'selected' : ''; ?>>Mixto</option>
            </select>
        </div>

        <div style="grid-column: 1 / -1; display: flex; gap: 8px;">
            <button class="btn-pos btn-primary" type="submit">Buscar</button>
            <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>comprobantes">Limpiar</a>
        </div>
    </form>
</section>

<?php if (!empty($comprobantes)): ?>
    <section class="tarjeta usuarios-lista">
        <h2 class="tarjeta-titulo">Resultados (<?php echo (int)$total; ?> comprobante<?php echo (int)$total === 1 ? '' : 's'; ?><?php if (($paginas ?? 1) > 1): ?> · página <?php echo (int)($filtrosVista['pagina'] ?? 1); ?> de <?php echo (int)$paginas; ?><?php endif; ?>)</h2>
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
                                    <?php echo htmlspecialchars(ucfirst((string)$comprobante['tipo_comprobante'])); ?>
                                </span>
                            </td>
                            <td><?php echo formatearMoneda($comprobante['total']); ?></td>
                            <td>
                                <a href="<?php echo URL_BASE; ?>comprobantes/imprimir/<?php echo (int)$comprobante['id']; ?>?copias=1" class="btn-pos btn-primary" target="_blank">Imprimir</a>
                                <button type="button" class="btn-pos btn-secondary" data-comprobante-lan="<?php echo (int)$comprobante['id']; ?>"><i class="fa-solid fa-wifi"></i> Imprimir LAN</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (($paginas ?? 1) > 1): ?>
        <?php
            $basePag = $filtrosVista;
            unset($basePag['pagina']);
            $qs = http_build_query($basePag);
            $pagActual = (int)($filtrosVista['pagina'] ?? 1);
        ?>
        <nav class="paginacion" style="margin-top:12px;display:flex;gap:8px;align-items:center;justify-content:center;">
            <?php if ($pagActual > 1): ?><a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>comprobantes?<?php echo htmlspecialchars($qs . '&pagina=' . ($pagActual - 1)); ?>">← Anterior</a><?php endif; ?>
            <span>Página <?php echo $pagActual; ?> de <?php echo (int)$paginas; ?></span>
            <?php if ($pagActual < (int)$paginas): ?><a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>comprobantes?<?php echo htmlspecialchars($qs . '&pagina=' . ($pagActual + 1)); ?>">Siguiente →</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

<script>
(function () {
    var URL_BASE = '<?php echo URL_BASE; ?>';
    var metaCsrf = document.querySelector('meta[name="csrf-token"]');
    var CSRF = metaCsrf ? metaCsrf.getAttribute('content') : '';
    var alerta = document.createElement('div');
    alerta.id = 'alerta-impresion-lan';
    alerta.style.margin = '12px 0';
    document.querySelector('main')?.insertBefore(alerta, document.querySelector('main')?.firstChild);

    document.querySelectorAll('[data-comprobante-lan]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-comprobante-lan');
            btn.disabled = true;
            alerta.className = 'alerta alerta-info';
            alerta.textContent = 'Enviando a la impresora LAN...';
            fetch(URL_BASE + 'impresora/imprimir-venta/' + encodeURIComponent(id) + '?copias=1', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: CSRF }) })
                .then(function (r) { return r.json(); })
                .then(function (datos) {
                    alerta.textContent = datos.mensaje || 'Sin respuesta del servidor.';
                    alerta.className = datos.exito ? 'alerta alerta-exito' : 'alerta alerta-error';
                })
                .catch(function () {
                    alerta.textContent = 'No se pudo conectar con el servidor para imprimir por LAN.';
                    alerta.className = 'alerta alerta-error';
                })
                .finally(function () { btn.disabled = false; });
        });
    });
})();
</script>
