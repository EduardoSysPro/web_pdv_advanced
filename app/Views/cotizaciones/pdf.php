<?php
/**
 * Cotización en PDF (dompdf) — formato carta, diseño moderno.
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

// Logo como data URI (evita dependencia del filesystem en el PDF)
$logoDataUri = '';
$logoArchivo = $configuracion['logo'] ?? '';
if (!empty($logoArchivo)) {
    $rutaLogoFisica = PUBLIC_PATH . 'uploads' . DIRECTORY_SEPARATOR . $logoArchivo;
    if (file_exists($rutaLogoFisica)) {
        $mime = function_exists('mime_content_type') ? (mime_content_type($rutaLogoFisica) ?: 'image/png') : 'image/png';
        $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($rutaLogoFisica));
    }
}

$nombreCliente  = $cotizacion['cliente_nombre'] ?: 'CONSUMIDOR FINAL';
$moneda = static function ($n) {
    return 'L ' . number_format((float)$n, 2);
};
$fechaCreada = date('d/m/Y H:i', strtotime($cotizacion['creada_en']));
$fechaValidez = !empty($cotizacion['fecha_validez']) ? date('d/m/Y', strtotime($cotizacion['fecha_validez'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { size: letter portrait; margin: 11mm 11mm 20mm; }
    * { box-sizing: border-box; }
    body { font-family: 'dejavu sans', sans-serif; font-size: 10px; color: #1e293b; line-height: 1.5; margin: 0; }

    /* ---- Encabezado ---- */
    table.encabezado { width: 100%; border-collapse: separate; border-spacing: 0; }
    table.encabezado td { vertical-align: middle; background: #0f172a; color: #ffffff; }
    table.encabezado td.empresa { border-radius: 12px 0 0 0; padding: 13px 16px; }
    table.encabezado td.titulo { border-radius: 0 0 12px 0; padding: 13px 16px; text-align: right; width: 230px; }
    .logo { max-height: 54px; max-width: 54px; vertical-align: middle; border-radius: 8px; }
    .casilla-logo { padding-right: 10px; width: 1%; }
    .empresa-nombre { font-size: 16px; font-weight: bold; letter-spacing: .4px; }
    .empresa-datos { font-size: 8.5px; color: #9fb0c8; margin-top: 3px; }
    .titulo-doc { font-size: 20px; font-weight: bold; letter-spacing: 3px; color: #34d399; }
    .folio-caja { display: inline-block; border: 1.5px solid #059669; border-radius: 6px; font-weight: bold; font-size: 12px; padding: 2px 10px; margin-top: 4px; }
    .fechas { font-size: 8px; color: #9fb0c8; margin-top: 4px; }

    .barra-acento { height: 4px; background: #059669; border-radius: 2px; margin: 7px 0 13px; }

    /* ---- Bloques de información ---- */
    table.bloques { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin: 0 0 14px; }
    table.bloques td { vertical-align: top; }
    table.bloques td.vacio { width: 0; border: none; }
    .caja-info { border: 1px solid #e2e8f0; border-left: 3px solid #059669; background: #f8fafc; border-radius: 6px; padding: 9px 12px 11px; }
    .caja-info .titulo { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 5px; }
    .caja-info .nombre { font-size: 12px; font-weight: bold; color: #0f172a; }
    .caja-info .linea { font-size: 9.5px; color: #475569; margin-top: 1px; }

    /* ---- Tabla de artículos ---- */
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.items th { background: #0f172a; color: #ffffff; font-size: 8px; text-transform: uppercase; letter-spacing: .6px; padding: 6px 9px; text-align: left; }
    table.items th.der, table.items td.der { text-align: right; }
    table.items td { border-bottom: 1px solid #eef2f7; padding: 6px 9px; vertical-align: top; font-size: 10px; }
    table.items tbody tr:nth-child(even) td { background: #f8fafc; }

    /* ---- Total / notas ---- */
    table.pie-doc { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin: 4px 0 10px; }
    table.pie-doc td { vertical-align: top; }
    .notas { border: 1px solid #e2e8f0; border-radius: 6px; padding: 9px 12px; background: #fff; }
    .notas .titulo { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 3px; font-weight: bold; }
    .notas p { font-size: 9px; color: #475569; margin: 2px 0; }
    .resumen { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
    .resumen .fila { padding: 3.5px 10px; font-size: 9.5px; color: #334155; }
    .resumen .fila.par { background: #f8fafc; }
    .resumen .fila .etiqueta { }
    .resumen .fila .valor { float: right; font-weight: bold; }
    .resumen .fila.total { background: #0f172a !important; color: #ffffff; font-weight: bold; font-size: 11px; padding: 6px 10px; }
    .resumen .fila.total .valor { color: #34d399; font-size: 12px; }
    .monto-letras { border: 1px dashed #94a3b8; border-radius: 6px; padding: 7px 11px; font-size: 9.5px; color: #334155; margin-bottom: 16px; }

    /* ---- Firmas ---- */
    table.firmas { width: 100%; border-collapse: separate; border-spacing: 24px 0; margin: 42px 0 0; }
    table.firmas td { width: 50%; text-align: center; font-size: 10px; color: #334155; border-top: 1px solid #64748b; padding-top: 5px; }

    /* ---- Pie de página (repetido por página) ---- */
    .pie { position: fixed; bottom: 0; left: 11mm; right: 11mm; border-top: 1px solid #e2e8f0; padding-top: 4px; font-size: 7.5px; color: #64748b; }
    .pie .izq { float: left; }
    .pie .der { float: right; }
    .pag-actual::after { content: counter(page); }
    .pag-total::after { content: counter(pages); }
</style>
</head>
<body>

    <table class="encabezado">
        <tr>
            <td class="empresa">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <?php if ($logoDataUri): ?>
                        <td class="casilla-logo"><img class="logo" src="<?php echo $logoDataUri; ?>" alt="Logo"></td>
                        <?php endif; ?>
                        <td>
                            <div class="empresa-nombre"><?php echo htmlspecialchars($empresaNombre); ?></div>
                            <div class="empresa-datos">
                                <?php if ($empresaRtn): ?><div>RTN: <?php echo htmlspecialchars($empresaRtn); ?></div><?php endif; ?>
                                <div>
                                    <?php
                                    $linea = [];
                                    if ($empresaDir) $linea[] = htmlspecialchars($empresaDir);
                                    if ($empresaTel) $linea[] = 'Tel: ' . htmlspecialchars($empresaTel);
                                    if ($empresaEmail) $linea[] = htmlspecialchars($empresaEmail);
                                    echo isset($linea[0]) ? implode(' &nbsp;|&nbsp; ', $linea) : '';
                                    ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="titulo">
                <div class="titulo-doc">COTIZACIÓN</div>
                <div class="folio-caja">No. <?php echo htmlspecialchars($cotizacion['folio']); ?></div>
                <div class="fechas">
                    Fecha: <?php echo $fechaCreada; ?>
                    <?php if ($fechaValidez): ?><br>Válida hasta: <?php echo $fechaValidez; ?><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
    <div class="barra-acento"></div>

    <table class="bloques">
        <tr>
            <td>
                <div class="caja-info">
                    <div class="titulo">Elaborada por</div>
                    <div class="nombre"><?php echo htmlspecialchars($cotizacion['vendedor']); ?></div>
                    <?php if ($cotizacion['cliente_telefono'] || $cotizacion['cliente_direccion']): ?>
                        <div class="linea"><?php echo htmlspecialchars(($cotizacion['cliente_telefono'] ? 'Tel: ' . $cotizacion['cliente_telefono'] : '')) . ($cotizacion['cliente_direccion'] && $cotizacion['cliente_telefono'] ? ' &nbsp;|&nbsp; ' : '') . htmlspecialchars(($cotizacion['cliente_direccion'] ?? '')); ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="vacio" style="width:1%; border:none;"></td>
            <td>
                <div class="caja-info">
                    <div class="titulo">Cliente</div>
                    <div class="nombre"><?php echo htmlspecialchars($nombreCliente); ?></div>
                    <?php if ($cotizacion['cliente_rtn']): ?><div class="linea"><b>RTN / Identidad:</b> <?php echo htmlspecialchars($cotizacion['cliente_rtn']); ?></div><?php endif; ?>
                    <?php if ($cotizacion['cliente_telefono']): ?><div class="linea"><b>Tel:</b> <?php echo htmlspecialchars($cotizacion['cliente_telefono']); ?></div><?php endif; ?>
                    <?php if ($cotizacion['cliente_direccion']): ?><div class="linea"><b>Dir:</b> <?php echo htmlspecialchars($cotizacion['cliente_direccion']); ?></div><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th>Descripción</th>
                <?php if ($mostrarPrecios): ?><th class="der" style="width:16%;">P. Unitario</th><?php endif; ?>
                <th class="der" style="width:11%;">Cantidad</th>
                <?php if ($mostrarPrecios): ?><th class="der" style="width:18%;">Importe</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php $contador = 0; ?>
            <?php foreach ($detalles as $detalle): ?>
                <?php
                $contador++;
                $nombreLin = $detalle['nombre_producto'];
                if (($detalle['tipo_presentacion'] ?? 'unidad') === 'empaque' && !empty($detalle['nombre_presentacion'])) {
                    $factorTexto = rtrim(rtrim(number_format((float)$detalle['factor_unidades'], 2, '.', ''), '0'), '.');
                    $nombreLin = '[' . $detalle['nombre_presentacion'] . ' x' . $factorTexto . '] ' . $nombreLin;
                }
                ?>
                <tr>
                    <td><?php echo $contador; ?></td>
                    <td><?php echo htmlspecialchars($nombreLin); ?></td>
                    <?php if ($mostrarPrecios): ?><td class="der"><?php echo $moneda($detalle['precio_unitario']); ?></td><?php endif; ?>
                    <td class="der"><?php echo rtrim(rtrim(number_format((float)$detalle['cantidad'], 3, '.', ''), '0'), '.'); ?></td>
                    <?php if ($mostrarPrecios): ?><td class="der"><?php echo $moneda($detalle['subtotal']); ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($mostrarPrecios): ?>
    <table class="pie-doc">
        <tr>
            <td style="width:56%;">
                <div class="notas">
                    <?php if ($cotizacion['observaciones']): ?>
                        <div class="titulo">Observaciones</div>
                        <p><?php echo nl2br(htmlspecialchars($cotizacion['observaciones'])); ?></p>
                    <?php endif; ?>
                    <div class="titulo">Condiciones de la cotización</div>
                    <p>&bull; Esta cotización es una oferta comercial y no constituye factura ni comprobante fiscal.</p>
                    <p>&bull; Precios sujetos a cambio por variaciones del mercado y disponibilidad de producto.</p>
                    <?php if ($fechaValidez): ?><p>&bull; Válida hasta el <?php echo $fechaValidez; ?>.</p><?php endif; ?>
                    <?php if (!empty($configuracion['cotizacion_mensaje'])): ?><p>&bull; <?php echo htmlspecialchars($configuracion['cotizacion_mensaje']); ?></p><?php endif; ?>
                </div>
            </td>
            <td style="width:44%;">
                <div class="resumen">
                    <div class="fila"><span class="etiqueta">Subtotal (lista)</span><span class="valor"><?php echo $moneda($cotizacion['subtotal'] + $cotizacion['descuento_total']); ?></span></div>
                    <div class="fila par"><span class="etiqueta">Descuentos</span><span class="valor">-<?php echo $moneda($cotizacion['descuento_total']); ?></span></div>
                    <?php if ((float)$cotizacion['importe_gravado_15'] > 0): ?>
                    <div class="fila"><span class="etiqueta">Base gravada 15%</span><span class="valor"><?php echo $moneda($cotizacion['importe_gravado_15']); ?></span></div>
                    <div class="fila par"><span class="etiqueta">ISV 15%</span><span class="valor"><?php echo $moneda($cotizacion['isv_15']); ?></span></div>
                    <?php endif; ?>
                    <?php if ((float)$cotizacion['importe_gravado_18'] > 0): ?>
                    <div class="fila"><span class="etiqueta">Base gravada 18%</span><span class="valor"><?php echo $moneda($cotizacion['importe_gravado_18']); ?></span></div>
                    <div class="fila par"><span class="etiqueta">ISV 18%</span><span class="valor"><?php echo $moneda($cotizacion['isv_18']); ?></span></div>
                    <?php endif; ?>
                    <?php if ((float)$cotizacion['importe_exento'] + (float)$cotizacion['importe_exonerado'] > 0): ?>
                    <div class="fila par"><span class="etiqueta">Exento / Exonerado</span><span class="valor"><?php echo $moneda($cotizacion['importe_exento'] + $cotizacion['importe_exonerado']); ?></span></div>
                    <?php endif; ?>
                    <div class="fila total"><span class="etiqueta">TOTAL A PAGAR</span><span class="valor"><?php echo $moneda($cotizacion['total']); ?></span></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="monto-letras"><b>SON: </b><?php echo numeroALetrasCotizacion((float)$cotizacion['total']); ?></div>
    <?php endif; ?>

    <table class="firmas">
        <tr>
            <td>Vendedor</td>
            <td>Cliente</td>
        </tr>
    </table>

    <div class="pie">
        <span class="izq"><?php echo htmlspecialchars($empresaNombre); ?><?php if ($empresaRtn): ?> &nbsp;&middot;&nbsp; RTN <?php echo htmlspecialchars($empresaRtn); ?><?php endif; ?><?php if ($empresaTel): ?> &nbsp;&middot;&nbsp; <?php echo htmlspecialchars($empresaTel); ?><?php endif; ?></span>
        <span class="der">Página <span class="pag-actual"></span> de <span class="pag-total"></span></span>
    </div>

</body>
</html>