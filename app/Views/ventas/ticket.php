<?php
/**
 * Función helper para convertir números a letras (Lempiras)
 */
function numeroALetras($numero) {
    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas  = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $dieces   = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    $entero   = floor($numero);
    $centavos = str_pad(round(($numero - $entero) * 100), 2, '0', STR_PAD_LEFT);

    if ($entero == 0) return "CERO LEMPIRAS CON " . $centavos . "/100 CENTAVOS";
    if ($entero == 100) return "CIEN LEMPIRAS CON " . $centavos . "/100 CENTAVOS";

    $convertirTresCifras = function($n) use ($unidades, $decenas, $dieces, $centenas) {
        $c = floor($n / 100);
        $d = floor(($n % 100) / 10);
        $u = $n % 10;
        $texto = '';

        if ($c > 0) {
            $texto .= ($c == 1 && $d == 0 && $u == 0) ? 'CIEN ' : $centenas[$c] . ' ';
        }

        if ($d == 1) {
            $texto .= $dieces[$u] . ' ';
        } else if ($d == 2 && $u > 0) {
            $texto .= 'VEINTI' . mb_strtolower($unidades[$u]) . ' ';
        } else {
            if ($d > 0) $texto .= $decenas[$d] . ($u > 0 ? ' Y ' : ' ');
            if ($u > 0 && $d != 2) $texto .= $unidades[$u] . ' ';
        }

        return trim($texto);
    };

    $textoFinal = '';

    if ($entero >= 1000) {
        $miles = floor($entero / 1000);
        $resto = $entero % 1000;
        $textoFinal .= ($miles == 1) ? 'MIL ' : $convertirTresCifras($miles) . ' MIL ';
        if ($resto > 0) $textoFinal .= $convertirTresCifras($resto) . ' ';
    } else {
        $textoFinal .= $convertirTresCifras($entero) . ' ';
    }

    return trim($textoFinal) . " LEMPIRAS CON " . $centavos . "/100 CENTAVOS";
}

$configuracion = $configuracion ?? [];
$ticketFuente = $configuracion['ticket_fuente'] ?? 'Courier New';
$ticketTamanoFuente = $configuracion['ticket_tamano_fuente'] ?? '11px';
$ticketMostrarLogo = isset($configuracion['ticket_mostrar_logo']) ? (string)$configuracion['ticket_mostrar_logo'] === '1' : true;
$ticketMostrarSar = isset($configuracion['ticket_mostrar_sar']) ? (string)$configuracion['ticket_mostrar_sar'] === '1' : true;
$anchoTicket = $configuracion['ancho_ticket'] ?? '80mm';
$tipoComprobante = $tipoComprobante ?? ($configuracion['tipo_comprobante_default'] ?? 'recibo');
$esFactura = $tipoComprobante === 'factura';
$totalVenta = (float)($venta['total'] ?? 0);
$descuentoRebaja = (float)($venta['descuento_total'] ?? $venta['descuento'] ?? 0);

// Fallback para tickets emitidos antes de la migración de desglose ISV por venta
$tieneDesgloseGuardado = isset($venta['importe_gravado_15']) || isset($venta['importe_gravado_18']) || isset($venta['importe_exento']);
if ($tieneDesgloseGuardado) {
    $importeExento = (float)($venta['importe_exento'] ?? 0);
    $importeExonerado = (float)($venta['importe_exonerado'] ?? 0);
    $gravado15 = (float)($venta['importe_gravado_15'] ?? 0);
    $isv15 = (float)($venta['isv_15'] ?? 0);
    $gravado18 = (float)($venta['importe_gravado_18'] ?? 0);
    $isv18 = (float)($venta['isv_18'] ?? 0);
} else {
    $importeExento = 0;
    $importeExonerado = 0;
    $gravado15 = $totalVenta > 0 ? $totalVenta / 1.15 : 0;
    $isv15 = $totalVenta - $gravado15;
    $gravado18 = 0;
    $isv18 = 0;
}
$montoEnLetras = numeroALetras($totalVenta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= htmlspecialchars($venta['folio'] ?? $venta['id']) ?></title>
    <style>
        body {
            font-family: "<?php echo htmlspecialchars($ticketFuente); ?>", Courier, monospace;
            font-size: <?php echo htmlspecialchars($ticketTamanoFuente); ?>;
            margin: 0;
            padding: 0;
            width: <?php echo htmlspecialchars($anchoTicket); ?>;
        }
        .ticket-copia {
            padding: 8px;
            page-break-after: always;
        }
        .ticket-copia:last-child {
            page-break-after: auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .linea { border-bottom: 1px dashed #000; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; vertical-align: top; }
        @media print {
            @page { margin: 0; }
            body { width: 100%; }
        }
    </style>
</head>
<body onload="window.print();">

<?php 
$etiquetasCopias = ['Original: Cliente', 'Copia: Emisor'];
foreach ($etiquetasCopias as $etiquetaCopia): 
?>
<div class="ticket-copia">
    <div class="text-center">
        <?php if ($ticketMostrarLogo && !empty($configuracion['logotipo_path'])): ?>
            <div style="margin-bottom: 8px;">
                <img src="<?php echo URL_BASE . htmlspecialchars($configuracion['logotipo_path']); ?>" alt="Logo" style="max-width: 160px; max-height: 90px; display: block; margin: 0 auto;">
            </div>
        <?php endif; ?>
        <h2 style="margin: 0; font-size: 14px;"><?= htmlspecialchars($configuracion['nombre_negocio'] ?? 'MI TIENDA') ?></h2>
        <div>RTN: <?= htmlspecialchars($configuracion['rtn'] ?? '') ?></div>
        <div><?= htmlspecialchars($configuracion['direccion'] ?? '') ?></div>
        <div>Tel: <?= htmlspecialchars($configuracion['telefono'] ?? '') ?></div>
        <div>Email: <?= htmlspecialchars($configuracion['email'] ?? '') ?></div>
    </div>

    <div class="linea"></div>

    <div>
        <div><strong><?= $esFactura ? 'FACTURA' : 'DOCUMENTO NO FISCAL / RECIBO INTERNO'; ?>:</strong> <?= htmlspecialchars($venta['folio'] ?? $venta['id']) ?></div>
        <?php if ($esFactura): ?>
            <div><strong>CAI:</strong> <?= htmlspecialchars($venta['cai'] ?? $configuracion['sar_cai'] ?? '') ?></div>
            <div><strong>Rango Autorizado:</strong></div>
            <div><?= htmlspecialchars($venta['rango_autorizado'] ?? (($configuracion['sar_rango_inicial'] ?? '') . ' al ' . ($configuracion['sar_rango_final'] ?? ''))) ?></div>
            <div><strong>Fecha Límite Emisión:</strong> <?= htmlspecialchars($venta['fecha_limite_emision'] ?? $configuracion['sar_fecha_limite'] ?? '') ?></div>
        <?php endif; ?>
        <div><strong>Fecha Emisión:</strong> <?= htmlspecialchars($venta['fecha_venta'] ?? date('Y-m-d H:i:s')) ?></div>
    </div>

    <div class="linea"></div>

    <div>
        <div style="text-align: center; font-weight: bold; margin-bottom: 3px;">DATOS DE CLIENTE</div>
        <div><strong>Nombre:</strong> <?= htmlspecialchars(!empty($venta['cliente_nombre']) ? $venta['cliente_nombre'] : 'Consumidor Final') ?></div>
        <div><strong>RTN/ID:</strong> <?= htmlspecialchars(!empty($venta['cliente_rtn']) ? $venta['cliente_rtn'] : 'S/N') ?></div>
        <?php if (!empty($venta['cliente_direccion'])): ?>
            <div><strong>Dirección:</strong> <?= htmlspecialchars($venta['cliente_direccion']) ?></div>
        <?php endif; ?>
        <div><strong>Cajero:</strong> <?= htmlspecialchars($venta['cajero'] ?? 'Cajero') ?></div>
    </div>

    <?php if ($esFactura): ?>
        <div class="linea"></div>

        <div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 3px;">DATOS DE ADQUIRIENTE EXONERADO</div>
            <div><strong>Orden Compra Exenta:</strong> _________________</div>
            <div><strong>Constancia Registro:</strong> _________________</div>
            <div><strong>Registro SAG:</strong> _________________</div>
        </div>
    <?php endif; ?>

    <div class="linea"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Cant/Descripción</th>
                <th class="text-right">P.U.</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $item): ?>
            <?php
                $cantidadItem = (float)($item['cantidad'] ?? 0);
                $cantidadTexto = rtrim(rtrim(number_format($cantidadItem, 3, '.', ''), '0'), '.');
                $nombreProducto = $item['nombre'] ?? ($item['producto_nombre'] ?? 'Producto');
                $precioUnitario = isset($item['precio_unitario']) ? (float)$item['precio_unitario'] : 0;
                $precioLista = isset($item['precio_lista']) ? (float)$item['precio_lista'] : $precioUnitario;
                $descuentoUnitario = isset($item['descuento_unitario'])
                    ? (float)$item['descuento_unitario']
                    : max(0, $precioLista - $precioUnitario);
                $subtotal = isset($item['subtotal']) ? (float)$item['subtotal'] : 0;
                $tipoPres = $item['tipo_presentacion'] ?? 'unidad';
                $nomPres  = !empty($item['nombre_presentacion']) ? $item['nombre_presentacion'] : ($tipoPres === 'empaque' ? 'Caja' : 'Unidad');
                $factor   = (float)($item['factor_unidades'] ?? 1);
                $factorTexto = rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.');

                $etiquetaPres = '';
                if ($tipoPres === 'empaque') {
                    $etiquetaPres = ' [' . $nomPres . ' x' . $factorTexto . ']';
                }
            ?>
            <tr>
                <td colspan="3"><?= htmlspecialchars($nombreProducto . $etiquetaPres) ?></td>
            </tr>
            <tr>
                <td><?= htmlspecialchars($cantidadTexto . ($tipoPres === 'empaque' ? ' ' . strtolower($nomPres) : '')) ?> x</td>
                <td class="text-right">
                    <?= number_format($precioUnitario, 2) ?>
                    <?php if ($descuentoUnitario > 0.001): ?>
                        <br><small>Lista <?= number_format($precioLista, 2) ?> - Desc. <?= number_format($descuentoUnitario, 2) ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-right"><?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="linea"></div>

    <?php if ($esFactura): ?>
        <table>
            <tr>
                <td>Importe Exento:</td>
                <td class="text-right">L <?= number_format($importeExento, 2) ?></td>
            </tr>
            <tr>
                <td>Importe Exonerado:</td>
                <td class="text-right">L <?= number_format($importeExonerado, 2) ?></td>
            </tr>
            <tr>
                <td>Importe Gravado 15%:</td>
                <td class="text-right">L <?= number_format($gravado15, 2) ?></td>
            </tr>
            <tr>
                <td>ISV 15%:</td>
                <td class="text-right">L <?= number_format($isv15, 2) ?></td>
            </tr>
            <tr>
                <td>Importe Gravado 18%:</td>
                <td class="text-right">L <?= number_format($gravado18, 2) ?></td>
            </tr>
            <tr>
                <td>ISV 18%:</td>
                <td class="text-right">L <?= number_format($isv18, 2) ?></td>
            </tr>
            <tr>
                <td>Descuentos y Rebajas Otorgadas:</td>
                <td class="text-right">L <?= number_format($descuentoRebaja, 2) ?></td>
            </tr>
            <tr>
                <td><strong>Total a Pagar:</strong></td>
                <td class="text-right"><strong>L <?= number_format($totalVenta, 2) ?></strong></td>
            </tr>
            <tr>
                <td>Efectivo / Recibido:</td>
                <td class="text-right">L <?= number_format($venta['pagado_con'] ?? $venta['efectivo'] ?? 0, 2) ?></td>
            </tr>
            <tr>
                <td>Cambio:</td>
                <td class="text-right">L <?= number_format($venta['cambio'] ?? 0, 2) ?></td>
            </tr>
        </table>
    <?php else: ?>
        <table>
            <tr>
                <td>Descuentos y Rebajas Otorgadas:</td>
                <td class="text-right">L <?= number_format($descuentoRebaja, 2) ?></td>
            </tr>
            <tr>
                <td><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>L <?= number_format($totalVenta, 2) ?></strong></td>
            </tr>
            <tr>
                <td>Forma de pago:</td>
                <td class="text-right"><?= htmlspecialchars(ucfirst($venta['metodo_pago'] ?? 'efectivo')) ?></td>
            </tr>
            <tr>
                <td>Efectivo / Recibido:</td>
                <td class="text-right">L <?= number_format($venta['pagado_con'] ?? $venta['efectivo'] ?? 0, 2) ?></td>
            </tr>
            <tr>
                <td>Cambio:</td>
                <td class="text-right">L <?= number_format($venta['cambio'] ?? 0, 2) ?></td>
            </tr>
        </table>
    <?php endif; ?>

    <div class="linea"></div>

    <div style="margin: 6px 0; font-size: 10px;">
        <strong>SON:</strong> <?= htmlspecialchars($montoEnLetras) ?>
    </div>

    <div class="linea"></div>

    <div class="text-center" style="margin-top: 6px;">
        <div><strong>*** <?= htmlspecialchars($etiquetaCopia) ?> ***</strong></div>
        <?php if ($esFactura && $ticketMostrarSar): ?>
            <div style="margin-top: 4px;">La factura es beneficio de todos, exíjala.</div>
        <?php endif; ?>
        <div><?php echo htmlspecialchars($configuracion['mensaje_ticket'] ?? '¡Gracias por su compra!'); ?></div>
    </div>
</div>
<?php endforeach; ?>

</body>
</html>