<?php
// Estado de cuenta imprimible (punto 13): usa datos y mensaje del negocio.
$cliente = $cuenta['cliente'];
$resumen = $cuenta['resumen'];
$configuracion = $configuracion ?? [];
$cubetas = $resumen['cubetas'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de cuenta - <?php echo htmlspecialchars($cliente['nombre']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 0; padding: 16px; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 14px; margin: 16px 0 6px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        .encabezado { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .resumen { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
        .resumen div { border: 1px solid #000; padding: 6px 10px; min-width: 140px; }
        .resumen span { display: block; font-size: 11px; }
        .resumen strong { font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #666; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        td.num, th.num { text-align: right; }
        .firma { margin-top: 40px; display: flex; justify-content: space-between; }
        .firma div { width: 40%; border-top: 1px solid #000; text-align: center; padding-top: 4px; }
        .no-imprimir { margin-bottom: 12px; }
        .no-imprimir button { padding: 8px 18px; font-size: 14px; cursor: pointer; }
        @media print {
            .no-imprimir { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
<div class="no-imprimir">
    <button type="button" onclick="window.print()">Imprimir</button>
    <button type="button" onclick="window.close()">Cerrar</button>
</div>

<div class="encabezado">
    <div>
        <h1><?php echo htmlspecialchars($configuracion['nombre_negocio'] ?? 'MI TIENDA'); ?></h1>
        <div>RTN: <?php echo htmlspecialchars($configuracion['rtn'] ?? ''); ?> · Tel: <?php echo htmlspecialchars($configuracion['telefono'] ?? ''); ?></div>
        <div><?php echo htmlspecialchars($configuracion['direccion'] ?? ''); ?></div>
    </div>
    <div style="text-align: right;">
        <h1>Estado de cuenta</h1>
        <div>Fecha: <?php echo date('d/m/Y H:i'); ?></div>
    </div>
</div>

<h2>Cliente</h2>
<div><strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong> (<?php echo (($cliente['tipo'] ?? 'minorista') === 'mayorista') ? 'Mayorista' : 'Minorista'; ?>)</div>
<div>RTN/Identidad: <?php echo htmlspecialchars($cliente['rtn_identidad'] ?? 'S/N'); ?> · Tel: <?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?></div>
<div>Dirección: <?php echo htmlspecialchars($cliente['direccion'] ?? ''); ?></div>
<div>Crédito a <?php echo (int)($resumen['dias_credito'] ?? 30); ?> días · Límite: <?php echo formatearMoneda($cliente['limite_credito']); ?></div>

<h2>Resumen</h2>
<div class="resumen">
    <div><span>Cargos</span><strong><?php echo formatearMoneda($resumen['cargos']); ?></strong></div>
    <div><span>Abonos</span><strong><?php echo formatearMoneda($resumen['abonos']); ?></strong></div>
    <div><span>Saldo pendiente</span><strong><?php echo formatearMoneda($resumen['saldo']); ?></strong></div>
    <div><span>Vencido</span><strong><?php echo formatearMoneda($resumen['vencido']); ?></strong></div>
    <div><span>Por vencer</span><strong><?php echo formatearMoneda($resumen['por_vencer']); ?></strong></div>
    <div><span>Mora máxima</span><strong><?php echo (int)$resumen['mora_max_dias']; ?> días</strong></div>
</div>
<div>Vigente: <?php echo formatearMoneda($cubetas['al_dia']); ?> · 1-30: <?php echo formatearMoneda($cubetas['m1_30']); ?> · 31-60: <?php echo formatearMoneda($cubetas['m31_60']); ?> · 61-90: <?php echo formatearMoneda($cubetas['m61_90']); ?> · +90: <?php echo formatearMoneda($cubetas['m90']); ?></div>

<h2>Facturas a crédito</h2>
<table>
    <thead><tr><th>Fecha</th><th>Folio</th><th class="num">Total</th><th class="num">Abonado</th><th class="num">Saldo</th><th>Vence</th><th>Mora</th></tr></thead>
    <tbody>
        <?php if (!$cuenta['facturas']): ?><tr><td colspan="7">No hay compras a crédito.</td></tr><?php endif; ?>
        <?php foreach ($cuenta['facturas'] as $factura): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($factura['fecha'])); ?></td>
                <td><?php echo htmlspecialchars($factura['folio']); ?></td>
                <td class="num"><?php echo formatearMoneda($factura['total']); ?></td>
                <td class="num"><?php echo formatearMoneda($factura['aplicado']); ?></td>
                <td class="num"><?php echo formatearMoneda($factura['saldo']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($factura['vencimiento'])); ?></td>
                <td><?php echo $factura['saldo'] > 0.005 ? htmlspecialchars($factura['bucket']['etiqueta']) : 'Saldada'; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>Movimientos</h2>
<table>
    <thead><tr><th>Fecha</th><th>Movimiento</th><th class="num">Cargo</th><th class="num">Abono</th><th class="num">Saldo</th></tr></thead>
    <tbody>
        <?php if (!$cuenta['movimientos']): ?><tr><td colspan="5">Sin movimientos.</td></tr><?php endif; ?>
        <?php foreach ($cuenta['movimientos'] as $mov): ?>
            <tr>
                <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?></td>
                <td>
                    <?php if ($mov['tipo'] === 'cargo'): ?>
                        Compra <?php echo htmlspecialchars($mov['folio']); ?>
                    <?php else: ?>
                        Abono (<?php echo htmlspecialchars($mov['forma_pago']); ?>)<?php if (!empty($mov['observacion'])): ?> — <?php echo htmlspecialchars($mov['observacion']); ?><?php endif; ?>
                    <?php endif; ?>
                </td>
                <td class="num"><?php echo $mov['cargo'] > 0 ? formatearMoneda($mov['cargo']) : '—'; ?></td>
                <td class="num"><?php echo $mov['abono'] > 0 ? formatearMoneda($mov['abono']) : '—'; ?></td>
                <td class="num"><?php echo formatearMoneda($mov['saldo_corrido']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><?php echo htmlspecialchars($configuracion['mensaje_ticket'] ?? ''); ?></p>

<div class="firma">
    <div>Entregado por (negocio)</div>
    <div>Recibido conforme (cliente)</div>
</div>
</body>
</html>
