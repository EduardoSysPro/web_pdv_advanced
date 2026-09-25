<?php
/**
 * Impresión de cotización en tamaño carta (letter).
 */
if (!function_exists('numeroALetrasCotizacion')) {
    function numeroALetrasCotizacion($numero) {
        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas  = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $dieces   = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $entero   = floor($numero);
        $centavos = str_pad((string)round(($numero - $entero) * 100), 2, '0', STR_PAD_LEFT);

        if ($entero == 0) return "CERO LEMPIRAS CON " . $centavos . "/100";
        if ($entero == 100) return "CIEN LEMPIRAS CON " . $centavos . "/100";

        $convertirTres = function ($n) use ($unidades, $decenas, $dieces, $centenas) {
            $c = floor($n / 100); $d = floor(($n % 100) / 10); $u = $n % 10;
            $texto = '';
            if ($c > 0) $texto .= ($c == 1 && $d == 0 && $u == 0) ? 'CIEN ' : $centenas[$c] . ' ';
            if ($d == 1) $texto .= $dieces[$u] . ' ';
            else if ($d == 2 && $u > 0) $texto .= 'VEINTI' . $unidades[$u] . ' ';
            else {
                if ($d > 0) $texto .= $decenas[$d] . ($u > 0 ? ' Y ' : ' ');
                if ($u > 0 && $d != 2) $texto .= $unidades[$u] . ' ';
            }
            return trim($texto);
        };
        $final = '';
        if ($entero >= 1000) {
            $miles = floor($entero / 1000); $resto = $entero % 1000;
            $final .= ($miles == 1) ? 'MIL ' : $convertirTres($miles) . ' MIL ';
            if ($resto > 0) $final .= $convertirTres($resto) . ' ';
        } else {
            $final .= $convertirTres($entero) . ' ';
        }
        return trim($final) . " LEMPIRAS CON " . $centavos . "/100";
    }
}

$mostrarPrecios = !isset($configuracion['cotizacion_mostrar_precios']) || (string)$configuracion['cotizacion_mostrar_precios'] !== '0';
$empresaNombre  = $configuracion['empresa_nombre'] ?? 'MI NEGOCIO';
$empresaRtn     = $configuracion['empresa_rtn'] ?? '';
$empresaDir     = $configuracion['empresa_direccion'] ?? '';
$empresaTel     = $configuracion['empresa_telefono'] ?? '';
$empresaEmail   = $configuracion['empresa_email'] ?? '';
$logoArchivo = $configuracion['logo'] ?? '';
$rutaLogo = !empty($logoArchivo) && file_exists(PUBLIC_PATH . 'uploads/' . $logoArchivo) ? URL_BASE . 'uploads/' . $logoArchivo : '';
$nombreCliente = $cotizacion['cliente_nombre'] ?: 'CONSUMIDOR FINAL';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cotización <?php echo htmlspecialchars($cotizacion['folio']); ?></title>
<style>
    @page { size: Letter; margin: 15mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 0; font-size: 12px; line-height: 1.45; }
    .encabezado { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
    .empresa { display: flex; gap: 10px; align-items: center; }
    .empresa img { max-height: 64px; max-width: 64px; object-fit: contain; }
    .empresa-nombre { font-size: 20px; font-weight: 800; letter-spacing: .5px; }
    .empresa-datos { font-size: 11px; color: #333; margin-top: 2px; }
    .titulo-doc { text-align: right; }
    .titulo-doc h1 { margin: 0; font-size: 22px; letter-spacing: 2px; border: 2px solid #111; padding: 4px 12px; display: inline-block; }
    .titulo-doc .folio { font-size: 14px; font-weight: 700; margin-top: 6px; }
    .titulo-doc .fechas { font-size: 11px; color: #333; margin-top: 4px; }
    .cols { display: flex; gap: 18px; margin-bottom: 14px; }
    .caja { flex: 1; border: 1px solid #ccc; padding: 8px 10px; min-height: 86px; }
    .caja h3 { margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #555; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
    .caja p { margin: 2px 0; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.items th { background: #111; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; padding: 5px 6px; text-align: left; }
    table.items th.der, table.items td.der { text-align: right; }
    table.items td { border-bottom: 1px solid #ddd; padding: 5px 6px; vertical-align: top; }
    table.items tr:nth-child(even) td { background: #f7f7f7; }
    .pie-totales { display: flex; justify-content: space-between; gap: 18px; margin-top: 6px; }
    .notas { flex: 1; font-size: 11px; color: #444; }
    .notas .titulo { font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: 1px; margin-bottom: 3px; color: #555; }
    .resumen { width: 280px; border: 1px solid #ccc; }
    .resumen .fila { display: flex; justify-content: space-between; padding: 4px 8px; font-size: 11px; }
    .resumen .fila:nth-child(even) { background: #f7f7f7; }
    .resumen .total { background: #111 !important; color: #fff; font-weight: 700; font-size: 13px; }
    .monto-letras { margin-top: 10px; font-size: 11px; border: 1px dashed #999; padding: 6px 8px; }
    .firmas { display: flex; justify-content: space-between; margin-top: 34px; gap: 30px; }
    .firma { text-align: center; flex: 1; }
    .firma .linea { border-top: 1px solid #333; padding-top: 4px; margin-top: 42px; font-size: 11px; }
    .aviso { margin-top: 14px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
    <div class="encabezado">
        <div class="empresa">
            <?php if ($rutaLogo): ?><img src="<?php echo htmlspecialchars($rutaLogo); ?>" alt="Logo"><?php endif; ?>
            <div>
                <div class="empresa-nombre"><?php echo htmlspecialchars($empresaNombre); ?></div>
                <div class="empresa-datos">
                    <?php if ($empresaRtn): ?><div>RTN: <?php echo htmlspecialchars($empresaRtn); ?></div><?php endif; ?>
                    <?php if ($empresaDir): ?><div><?php echo htmlspecialchars($empresaDir); ?></div><?php endif; ?>
                    <?php if ($empresaTel): ?><div>Tel: <?php echo htmlspecialchars($empresaTel); ?></div><?php endif; ?>
                    <?php if ($empresaEmail): ?><div><?php echo htmlspecialchars($empresaEmail); ?></div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="titulo-doc">
            <h1>COTIZACIÓN</h1>
            <div class="folio">No. <?php echo htmlspecialchars($cotizacion['folio']); ?></div>
            <div class="fechas">Fecha: <?php echo date('d/m/Y H:i', strtotime($cotizacion['creada_en'])); ?><?php if ($cotizacion['fecha_validez']): ?> · Válida hasta: <?php echo date('d/m/Y', strtotime($cotizacion['fecha_validez'])); ?><?php endif; ?></div>
        </div>
    </div>

    <div class="cols">
        <div class="caja">
            <h3>Vendedor</h3>
            <p><strong><?php echo htmlspecialchars($cotizacion['vendedor']); ?></strong></p>
        </div>
        <div class="caja">
            <h3>Cliente</h3>
            <p><strong><?php echo htmlspecialchars($nombreCliente); ?></strong></p>
            <?php if ($cotizacion['cliente_rtn']): ?><p>RTN: <?php echo htmlspecialchars($cotizacion['cliente_rtn']); ?></p><?php endif; ?>
            <?php if ($cotizacion['cliente_telefono']): ?><p>Tel: <?php echo htmlspecialchars($cotizacion['cliente_telefono']); ?></p><?php endif; ?>
            <?php if ($cotizacion['cliente_direccion']): ?><p><?php echo htmlspecialchars($cotizacion['cliente_direccion']); ?></p><?php endif; ?>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th>Descripción</th>
                <?php if ($mostrarPrecios): ?><th style="width:14%;" class="der">P. Unitario</th><?php endif; ?>
                <th style="width:9%;" class="der">Cant.</th>
                <?php if ($mostrarPrecios): ?><th style="width:15%;" class="der">Importe</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php $contador = 0; ?>
            <?php foreach ($detalles as $detalle): ?>
                <?php $contador++; ?>
                <?php
                $nombreLin = $detalle['nombre_producto'];
                if (($detalle['tipo_presentacion'] ?? 'unidad') === 'empaque' && !empty($detalle['nombre_presentacion'])) {
                    $factorTexto = rtrim(rtrim(number_format((float)$detalle['factor_unidades'], 2, '.', ''), '0'), '.');
                    $nombreLin = '[' . $detalle['nombre_presentacion'] . ' x' . $factorTexto . '] ' . $nombreLin;
                }
                ?>
                <tr>
                    <td><?php echo $contador; ?></td>
                    <td><?php echo htmlspecialchars($nombreLin); ?></td>
                    <?php if ($mostrarPrecios): ?><td class="der"><?php echo number_format((float)$detalle['precio_unitario'], 2); ?></td><?php endif; ?>
                    <td class="der"><?php echo rtrim(rtrim(number_format((float)$detalle['cantidad'], 3, '.', ''), '0'), '.'); ?></td>
                    <?php if ($mostrarPrecios): ?><td class="der"><?php echo number_format((float)$detalle['subtotal'], 2); ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($mostrarPrecios): ?>
    <div class="pie-totales">
        <div class="notas">
            <?php if ($cotizacion['observaciones']): ?>
                <div class="titulo">Observaciones</div>
                <div><?php echo nl2br(htmlspecialchars($cotizacion['observaciones'])); ?></div>
            <?php endif; ?>
            <div class="titulo" style="margin-top:8px;">Condiciones de la cotización</div>
            <div>• Esta cotización es una oferta comercial y no constituye factura ni comprobante fiscal.</div>
            <div>• Precios sujetos a cambio por variaciones del mercado y disponibilidad de producto.</div>
            <?php if ($cotizacion['fecha_validez']): ?><div>• Válida hasta el <?php echo date('d/m/Y', strtotime($cotizacion['fecha_validez'])); ?>.</div><?php endif; ?>
            <?php if (!empty($configuracion['cotizacion_mensaje'])): ?><div>• <?php echo htmlspecialchars($configuracion['cotizacion_mensaje']); ?></div><?php endif; ?>
        </div>
        <div class="resumen">
            <div class="fila"><span>Subtotal (a lista)</span><span><?php echo number_format((float)($cotizacion['subtotal'] + $cotizacion['descuento_total']), 2); ?></span></div>
            <div class="fila"><span>Descuentos</span><span>-<?php echo number_format((float)$cotizacion['descuento_total'], 2); ?></span></div>
            <div class="fila"><span>Base gravada 15%</span><span><?php echo number_format((float)$cotizacion['importe_gravado_15'], 2); ?></span></div>
            <div class="fila"><span>ISV 15%</span><span><?php echo number_format((float)$cotizacion['isv_15'], 2); ?></span></div>
            <div class="fila"><span>Base gravada 18%</span><span><?php echo number_format((float)$cotizacion['importe_gravado_18'], 2); ?></span></div>
            <div class="fila"><span>ISV 18%</span><span><?php echo number_format((float)$cotizacion['isv_18'], 2); ?></span></div>
            <div class="fila"><span>Exento / Exonerado</span><span><?php echo number_format((float)($cotizacion['importe_exento'] + $cotizacion['importe_exonerado']), 2); ?></span></div>
            <div class="fila total"><span>TOTAL A PAGAR</span><span><?php echo number_format((float)$cotizacion['total'], 2); ?></span></div>
        </div>
    </div>
    <div class="monto-letras"><strong>SON: </strong><?php echo numeroALetrasCotizacion((float)$cotizacion['total']); ?></div>
    <?php endif; ?>

    <div class="firmas">
        <div class="firma"><div class="linea">Vendedor</div></div>
        <div class="firma"><div class="linea">Cliente</div></div>
    </div>

    <div class="aviso">
        <?php echo htmlspecialchars($empresaNombre); ?> · RTN <?php echo htmlspecialchars($empresaRtn); ?> · Esta cotización deberá ser pagada y facturada en caja para su validez.
    </div>

    <div class="no-print" style="margin-top:14px; text-align:center;">
        <button type="button" onclick="window.print();" style="font-size:14px; padding:8px 22px; cursor:pointer;">Imprimir</button>
    </div>
    <script>
        // Cuando esta vista se carga en el iframe oculto de la WebApp instalada
        // (marcador en window.name), imprime automáticamente sin abrir pestañas.
        if (window.name === '__pdv_autoprint__') {
            window.addEventListener('load', function () { window.print(); });
        }
    </script>
</body>
</html>