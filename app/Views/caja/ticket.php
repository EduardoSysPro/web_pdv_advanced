<?php
$diferencia = (float)($corte['diferencia'] ?? 0);
$configuracion = $configuracion ?? [];
$ticketFuente = $configuracion['ticket_fuente'] ?? 'Courier New';
$ticketTamanoFuente = $configuracion['ticket_tamano_fuente'] ?? '11px';
$ticketMostrarLogo = isset($configuracion['ticket_mostrar_logo']) ? (string)$configuracion['ticket_mostrar_logo'] === '1' : true;
$anchoTicket = $configuracion['ancho_ticket'] ?? '80mm';
$nombreNegocio = $configuracion['nombre_negocio'] ?? 'Mi Abarrotería';
$rtnNegocio = $configuracion['rtn'] ?? '';
$telefonoNegocio = $configuracion['telefono'] ?? '';
$emailNegocio = $configuracion['email'] ?? '';
$direccionNegocio = $configuracion['direccion'] ?? '';
$mensajeTicket = $configuracion['mensaje_ticket'] ?? '¡Gracias por su compra!';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de caja</title>
    <style>
        @page {
            size: <?php echo htmlspecialchars($anchoTicket); ?> auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            width: <?php echo htmlspecialchars($anchoTicket); ?>;
            margin: 0;
            padding: 0;
            font-family: "<?php echo htmlspecialchars($ticketFuente); ?>", Courier, monospace;
            font-size: <?php echo htmlspecialchars($ticketTamanoFuente); ?>;
            color: #000;
        }
        .ticket-copia {
            padding: 8px;
        }
        .centrado {
            text-align: center;
        }
        .tienda {
            font-size: 16px;
            font-weight: bold;
        }
        .linea {
            border-top: 1px dashed #000;
            margin: 7px 0;
        }
        .fila {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .resultado {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #000;
            border-bottom: 1px double #000;
            padding: 5px 0;
        }
        .nota {
            font-size: 9px;
            margin-top: 10px;
        }
        .mini {
            font-size: 9px;
            line-height: 1.4;
        }
        @media screen {
            body {
                margin: 12px auto;
                box-shadow: 0 0 0 1px #ddd;
            }
        }
    </style>
</head>
<body onload="window.print();">
    <main class="ticket-copia">
        <header class="centrado">
            <?php if ($ticketMostrarLogo && !empty($configuracion['logotipo_path'])): ?>
                <div style="margin-bottom: 6px;">
                    <img src="<?php echo htmlspecialchars(URL_BASE . ltrim((string)$configuracion['logotipo_path'], '/')); ?>" alt="Logo" style="max-width: 60px; max-height: 30px; display: block; margin: 0 auto;">
                </div>
            <?php endif; ?>
            <div class="tienda"><?php echo htmlspecialchars($nombreNegocio); ?></div>
            <div>COMPROBANTE DE CORTE</div>
            <?php if (!empty($rtnNegocio)): ?>
                <div>RTN: <?php echo htmlspecialchars($rtnNegocio); ?></div>
            <?php endif; ?>
            <?php if (!empty($direccionNegocio)): ?>
                <div><?php echo htmlspecialchars($direccionNegocio); ?></div>
            <?php endif; ?>
            <?php if (!empty($telefonoNegocio) || !empty($emailNegocio)): ?>
                <div>
                    <?php echo !empty($telefonoNegocio) ? htmlspecialchars($telefonoNegocio) : ''; ?>
                    <?php echo (!empty($telefonoNegocio) && !empty($emailNegocio)) ? ' | ' : ''; ?>
                    <?php echo !empty($emailNegocio) ? htmlspecialchars($emailNegocio) : ''; ?>
                </div>
            <?php endif; ?>
            <div><?php echo htmlspecialchars($corte['cajero']); ?></div>
            <div><?php echo date('d/m/Y H:i', strtotime($corte['fecha_cierre'])); ?></div>
        </header>

        <div class="linea"></div>

        <div class="fila">
            <span>Fondo inicial</span>
            <span><?php echo formatearMoneda($corte['fondo_inicial']); ?></span>
        </div>
        <div class="fila">
            <span>Ventas efectivo</span>
            <span><?php echo formatearMoneda($ventas['efectivo']); ?></span>
        </div>
        <div class="fila">
            <span>Ventas tarjeta</span>
            <span><?php echo formatearMoneda($ventas['tarjeta']); ?></span>
        </div>
        <div class="fila">
            <span>Ventas transferencia</span>
            <span><?php echo formatearMoneda($ventas['transferencia']); ?></span>
        </div>
        <div class="fila">
            <span>Entradas</span>
            <span><?php echo formatearMoneda($movimientos['ingreso']); ?></span>
        </div>
        <div class="fila">
            <span>Salidas</span>
            <span><?php echo formatearMoneda($movimientos['egreso']); ?></span>
        </div>

        <div class="linea"></div>

        <div class="fila resultado">
            <span>Esperado</span>
            <span><?php echo formatearMoneda($esperado); ?></span>
        </div>
        <div class="fila">
            <span>Contado</span>
            <span><?php echo formatearMoneda($corte['monto_declarado']); ?></span>
        </div>
        <div class="fila resultado">
            <span><?php echo $diferencia < 0 ? 'Faltante' : 'Sobrante'; ?></span>
            <span><?php echo formatearMoneda(abs($diferencia)); ?></span>
        </div>

        <p class="centrado nota">
            Comprobante de cierre de caja.<br>
            Conserve este documento.
        </p>


    </main>
</body>
</html>