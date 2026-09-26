<?php
$filas = $cartera['filas'];
$totales = $cartera['totales'];
$filtro = $cartera['filtro'];
$tituloPagina = 'Cartera de cuentas por cobrar';
require APP_PATH . 'Views/layouts/pos_header.php';
?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Cobranza / CxC</span>
        <h1>Cartera de cuentas por cobrar</h1>
        <p>Saldos abiertos por cliente con mora calculada al día de hoy.</p>
    </div>
    <div>
        <a class="btn-pos btn-pos-secondary" href="<?php echo URL_BASE; ?>clientes">Clientes</a>
        <button class="btn-pos btn-pos-primary" type="button" onclick="window.print()">Imprimir</button>
    </div>
</section>

<section class="resumen-caja">
    <div><span>Clientes con saldo</span><strong><?php echo (int)$totales['clientes']; ?></strong></div>
    <div class="esperado"><span>Cartera total</span><strong><?php echo formatearMoneda($totales['cartera']); ?></strong></div>
    <div><span>Vencido</span><strong><?php echo formatearMoneda($totales['vencido']); ?></strong></div>
    <div><span>Por vencer</span><strong><?php echo formatearMoneda($totales['vigente']); ?></strong></div>
</section>

<section class="tarjeta catalogo-panel">
    <form class="filtros-productos" method="GET" action="<?php echo URL_BASE; ?>clientes/cartera">
        <select name="filtro" onchange="this.form.submit()">
            <option value="todos" <?php echo $filtro === 'todos' ? 'selected' : ''; ?>>Todos con saldo</option>
            <option value="vencidos" <?php echo $filtro === 'vencidos' ? 'selected' : ''; ?>>Solo vencidos</option>
            <option value="vigentes" <?php echo $filtro === 'vigentes' ? 'selected' : ''; ?>>Solo vigentes</option>
            <option value="sobregirados" <?php echo $filtro === 'sobregirados' ? 'selected' : ''; ?>>Sobregirados (sin disponible)</option>
        </select>
    </form>

    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Límite</th>
                    <th>Saldo</th>
                    <th>Vencido</th>
                    <th>Vigente</th>
                    <th>Mora máx.</th>
                    <th>Última compra</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$filas): ?>
                    <tr><td colspan="9" class="tabla-vacia">Sin cuentas por cobrar para este filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($f['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($f['telefono'] ?? ''); ?></td>
                        <td><?php echo formatearMoneda($f['limite_credito']); ?></td>
                        <td><?php echo formatearMoneda($f['saldo']); ?></td>
                        <td class="<?php echo $f['vencido'] > 0.005 ? 'stock-bajo' : ''; ?>">
                            <?php echo $f['vencido'] > 0.005 ? formatearMoneda($f['vencido']) : '—'; ?>
                        </td>
                        <td><?php echo formatearMoneda($f['vigente']); ?></td>
                        <td><?php echo $f['vencido'] > 0.005 ? (int)$f['mora_max'] . ' días (' . htmlspecialchars($f['bucket']['etiqueta']) . ')' : '—'; ?></td>
                        <td><?php echo !empty($f['ultima_compra']) ? date('d/m/Y', strtotime($f['ultima_compra'])) : '—'; ?></td>
                        <td class="acciones">
                            <a class="btn btn-pequeno btn-exito" href="<?php echo URL_BASE; ?>clientes/estado-cuenta/<?php echo (int)$f['id']; ?>">Estado de cuenta</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
