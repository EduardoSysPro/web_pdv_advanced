<?php
// Recibo de abono con la configuración de ticket del negocio
// (fuente, tamaño, ancho, logo, datos y mensaje). Respeta lo mismo
// que el ticket de venta: $configuracion, $abono, $esReimpresion.
$configuracion = $configuracion ?? [];
$ticketFuente = $configuracion['ticket_fuente'] ?? 'Courier New';
$ticketTamanoFuente = $configuracion['ticket_tamano_fuente'] ?? '11px';
$ticketMostrarLogo = isset($configuracion['ticket_mostrar_logo']) ? (string)$configuracion['ticket_mostrar_logo'] === '1' : true;
$anchoTicket = $configuracion['ancho_ticket'] ?? '80mm';
$esReimpresion = !empty($esReimpresion);
$estaAnulado = (($abono['estado'] ?? 'activo') !== 'activo');
$etiquetasMetodo = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];
$etiquetaMetodo = $etiquetasMetodo[$abono['forma_pago']] ?? ucfirst((string)$abono['forma_pago']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de abono #<?php echo (int)$abono['id']; ?></title>
    <style>
        body {
            font-family: "<?php echo htmlspecialchars($ticketFuente); ?>", Courier, monospace;
            font-size: <?php echo htmlspecialchars($ticketTamanoFuente); ?>;
            margin: 0;
            padding: 0;
            width: <?php echo htmlspecialchars($anchoTicket); ?>;
        }
        .ticket-copia { padding: 8px; page-break-after: always; }
        .ticket-copia:last-child { page-break-after: auto; }
        .text-center { text-align: center; }
        .linea { border-bottom: 1px dashed #000; margin: 4px 0; }
        .fila { display: flex; justify-content: space-between; margin: 3px 0; }
        .monto { font-size: 1.4em; font-weight: bold; border-top: 1px solid #000; border-bottom: 3px double #000; padding: 6px 0; }
        .sello { border: 2px solid #000; display: inline-block; padding: 2px 10px; font-weight: bold; margin: 4px 0; }
        @media print {
            @page { margin: 0; }
            body { width: 100%; }
        }
        @media screen {
            body { margin: 12px auto; box-shadow: 0 0 0 1px #ddd; }
        }
    </style>
</head>
<body onload="window.print();">

<?php
$copiasAbono = ['Original: Cliente', 'Copia: Emisor'];
foreach ($copiasAbono as $etiquetaCopia):
?>
<div class="ticket-copia">
    <div class="text-center">
        <?php if ($ticketMostrarLogo && !empty($configuracion['logotipo_path'])): ?>
            <div style="margin-bottom: 8px;">
                <img src="<?php echo URL_BASE . htmlspecialchars($configuracion['logotipo_path']); ?>" alt="Logo" style="max-width: 160px; max-height: 90px; display: block; margin: 0 auto;">
            </div>
        <?php endif; ?>
        <h2 style="margin: 0; font-size: 14px;"><?php echo htmlspecialchars($configuracion['nombre_negocio'] ?? 'MI TIENDA'); ?></h2>
        <div>RTN: <?php echo htmlspecialchars($configuracion['rtn'] ?? ''); ?></div>
        <div><?php echo htmlspecialchars($configuracion['direccion'] ?? ''); ?></div>
        <div>Tel: <?php echo htmlspecialchars($configuracion['telefono'] ?? ''); ?></div>
        <div><strong>RECIBO DE ABONO #<?php echo (int)$abono['id']; ?></strong></div>
        <div><?php echo htmlspecialchars($etiquetaCopia); ?><?php echo $esReimpresion ? ' · REIMPRESIÓN' : ''; ?></div>
        <?php if ($estaAnulado): ?><div><span class="sello">ANULADO</span></div><?php endif; ?>
    </div>

    <div class="linea"></div>

    <div class="fila"><span>Cliente:</span><span><strong><?php echo htmlspecialchars($abono['cliente_nombre']); ?></strong></span></div>
    <div class="fila"><span>Fecha:</span><span><?php echo date('d/m/Y H:i', strtotime($abono['fecha'])); ?></span></div>
    <div class="fila"><span>Forma de pago:</span><span><?php echo htmlspecialchars($etiquetaMetodo); ?></span></div>
    <?php if (!empty($abono['observacion'])): ?>
        <div class="fila"><span>Observación:</span><span><?php echo htmlspecialchars($abono['observacion']); ?></span></div>
    <?php endif; ?>
    <div class="fila"><span>Cajero:</span><span><?php echo htmlspecialchars($abono['usuario_nombre']); ?></span></div>

    <div class="fila monto"><span>ABONO</span><span><?php echo formatearMoneda($abono['monto']); ?></span></div>

    <div class="fila"><span>Saldo restante:</span><span><strong><?php echo formatearMoneda($abono['cliente_saldo'] ?? 0); ?></strong></span></div>

    <div class="linea"></div>

    <div class="text-center">
        <div><?php echo htmlspecialchars($configuracion['mensaje_ticket'] ?? '¡Gracias por su pago!'); ?></div>
        <br>
        <div>_________________________</div>
        <div>Firma cliente</div>
    </div>
</div>
<?php endforeach; ?>

</body>
</html>
