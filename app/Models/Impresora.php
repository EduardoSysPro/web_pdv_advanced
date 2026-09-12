<?php

require_once CORE_PATH . 'Controller.php';

/**
 * Impresión de tickets por red (LAN) para impresoras térmicas ESC/POS.
 * Envía texto ESC/POS crudo a la IP:puerto (9100 estándar) mediante socket TCP.
 */
class Impresora
{
    private $pdo;

    private const COLS_58MM = 32;
    private const COLS_80MM = 42;

    public function __construct()
    {
        $this->pdo = Database::getInstancia()->getConexion();
    }

    /**
     * Imprime el ticket de una venta por red. Devuelve ['exito'=>bool, 'mensaje'=>string].
     */
    public function imprimirVenta($ventaId, array $configuracion, int $copias = 2)
    {
        $ventaId = (int)$ventaId;
        if ($ventaId <= 0) {
            return ['exito' => false, 'mensaje' => 'Comprobante no válido.'];
        }

        if (!$this->impresoraConfigurada($configuracion)) {
            return ['exito' => false, 'mensaje' => 'La impresora LAN no está configurada. Actívala en Configuración, sección "Impresora térmica por red".'];
        }

        $sql = "SELECT v.*, u.nombre AS cajero, c.nombre AS cliente
                FROM ventas v
                LEFT JOIN usuarios u ON u.id = v.usuario_id
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE v.id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $ventaId]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            return ['exito' => false, 'mensaje' => 'Comprobante no encontrado.'];
        }

        if (($venta['metodo_pago'] ?? 'efectivo') === 'credito' && (($venta['tipo_comprobante'] ?? 'recibo') === 'factura')) {
            $saldoCliente = 0.0;
            if (!empty($venta['cliente_id'])) {
                $stmtSaldo = $this->pdo->prepare('SELECT saldo_pendiente FROM clientes WHERE id = :id LIMIT 1');
                $stmtSaldo->execute([':id' => (int)$venta['cliente_id']]);
                $saldoCliente = (float)($stmtSaldo->fetchColumn() ?: 0);
            }
            if ($saldoCliente > 0) {
                return ['exito' => false, 'mensaje' => 'La factura a crédito aún no está saldada. No puede imprimirse hasta que el cliente pague el total.'];
            }
        }

        $detalles = $this->obtenerDetalles($ventaId);

        return $this->enviar($configuracion, $this->construirTicket($venta, $detalles, $configuracion, $copias));
    }

    /**
     * Envía un ticket de prueba a la impresora.
     */
    public function probar($ip, $puerto, array $configuracion)
    {
        $ip = trim((string)$ip);
        $puerto = max(1, min(65535, (int)$puerto));
        if ($ip === '') {
            return ['exito' => false, 'mensaje' => 'Ingresa la dirección IP de la impresora.'];
        }

        $ancho = ($configuracion['ancho_ticket'] ?? '80mm') === '58mm' ? self::COLS_58MM : self::COLS_80MM;
        $lineas = [];
        $lineas[] = ['txt' => $this->centrar($configuracion['nombre_negocio'] ?? 'MI TIENDA', $ancho), 'bold' => true];
        $lineas[] = ['txt' => $this->centrar('TICKET DE PRUEBA - IMPRESORA LAN', $ancho), 'bold' => true];
        $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];
        $lineas[] = ['txt' => $this->izquierda('Fecha: ' . date('d/m/Y H:i:s'), $ancho), 'bold' => false];
        $lineas[] = ['txt' => $this->izquierda('Host: ' . $ip . ':' . $puerto, $ancho), 'bold' => false];
        $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];
        $lineas[] = ['txt' => $this->centrar('*** Si lees este ticket, la', $ancho), 'bold' => false];
        $lineas[] = ['txt' => $this->centrar('impresion por red funciona ***', $ancho), 'bold' => false];
        if (!empty($configuracion['mensaje_ticket'])) {
            $lineas[] = ['txt' => $this->centrar($configuracion['mensaje_ticket'], $ancho), 'bold' => false];
        }

        return $this->enviar(['impresora_lan_ip' => $ip, 'impresora_lan_puerto' => $puerto], $this->construirBytes($lineas, true, true));
    }

    /**
     * Abre un socket TCP hacia la impresora y envía los bytes ESC/POS.
     */
    private function enviar(array $configuracion, string $bytes)
    {
        $host = (string)($configuracion['impresora_lan_ip'] ?? '');
        $puerto = (int)($configuracion['impresora_lan_puerto'] ?? 9100);

        $fp = @fsockopen($host, $puerto, $errno, $errstr, 3.0);
        if (!$fp) {
            return ['exito' => false, 'mensaje' => 'No se pudo conectar con la impresora (' . $host . ':' . $puerto . '): ' . ($errstr ?: 'error de red.') . ' Revisa que esté encendida, con IP fija y en la misma red.'];
        }

        stream_set_timeout($fp, 5);
        $totalLongitud = strlen($bytes);
        $escrito = 0;
        $error = false;
        while ($escrito < $totalLongitud) {
            $parcial = @fwrite($fp, substr($bytes, $escrito));
            if ($parcial === false || $parcial === 0) {
                $error = true;
                break;
            }
            $escrito += $parcial;
        }
        @fflush($fp);                      // Vaciar el búfer de envío
        $info = stream_get_meta_data($fp);

        // Esperar para que la impresora procese el búfer completo e incluso
        // ejecute el corte mecánico (GS V) antes de cerrar el socket.
        usleep(600000);

        @fclose($fp);

        if ($error || $escrito < $totalLongitud) {
            return ['exito' => false, 'mensaje' => 'Se conectó a la impresora pero no se pudieron enviar todos los datos (' . $host . ':' . $puerto . ').'];
        }
        if (!empty($info['timed_out'])) {
            return ['exito' => false, 'mensaje' => 'Se conectó a la impresora pero la transmisión tardó demasiado (' . $host . ':' . $puerto . ').'];
        }

        return ['exito' => true, 'mensaje' => 'Ticket impreso correctamente en la impresora LAN (' . $host . ':' . $puerto . ').'];
    }

    private function impresoraConfigurada(array $configuracion)
    {
        return ((string)($configuracion['impresora_lan_activa'] ?? '0') === '1'
            && trim((string)($configuracion['impresora_lan_ip'] ?? '')) !== '');
    }

    private function obtenerDetalles($ventaId)
    {
        $hasPrecioLista = $this->columnaExiste('detalle_ventas', 'precio_lista');
        $hasDescuentoDetalle = $this->columnaExiste('detalle_ventas', 'descuento_unitario');
        $columnasDescuento = ($hasPrecioLista ? ', dv.precio_lista' : '')
            . ($hasDescuentoDetalle ? ', dv.descuento_unitario' : '');
        $sql = "SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, p.nombre,
                       dv.tipo_presentacion, dv.nombre_presentacion, dv.factor_unidades{$columnasDescuento}
                FROM detalle_ventas dv
                INNER JOIN productos p ON p.id = dv.producto_id
                WHERE dv.venta_id = :venta_id ORDER BY dv.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':venta_id' => (int)$ventaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function columnaExiste($tabla, $columna)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna");
        $stmt->execute([':tabla' => $tabla, ':columna' => $columna]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Construye el ticket completo como texto ESC/POS en columnas fijas.
     * $copias controla cuántas hojas se imprimen (2 = Original + Copia).
     */
    public function construirTicket(array $venta, array $detalles, array $configuracion, int $copias = 2)
    {
        $ancho = ($configuracion['ancho_ticket'] ?? '80mm') === '58mm' ? self::COLS_58MM : self::COLS_80MM;
        $lineas = [];

        $esFactura = ($venta['tipo_comprobante'] ?? '') === 'factura';
        $totalVenta = (float)($venta['total'] ?? 0);
        $simbolo = $configuracion['moneda_simbolo'] ?? 'L';
        $descuentoRebaja = (float)($venta['descuento_total'] ?? $venta['descuento'] ?? 0);

        $tieneDesglose = isset($venta['importe_gravado_15']) || isset($venta['importe_gravado_18']) || isset($venta['importe_exento']);
        if ($tieneDesglose) {
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

        $etiquetasCopias = ['Original: Cliente', 'Copia: Emisor'];
        $copias = max(1, min(5, $copias));
        $bytesTicket = '';
        $totalCopias = $copias;

        for ($indiceCopia = 0; $indiceCopia < $copias; $indiceCopia++) {
            $etiquetaCopia = $etiquetasCopias[$indiceCopia] ?? 'Original: Cliente';
            $lineas = [];

            // Encabezado del negocio
            $lineas[] = ['txt' => $this->centrar($configuracion['nombre_negocio'] ?? 'MI TIENDA', $ancho), 'bold' => true];
            if (!empty($configuracion['rtn'])) $lineas[] = ['txt' => $this->centrar('RTN: ' . $configuracion['rtn'], $ancho), 'bold' => false];
            if (!empty($configuracion['direccion'])) $lineas[] = ['txt' => $this->centrar($configuracion['direccion'], $ancho), 'bold' => false];
            if (!empty($configuracion['telefono'])) $lineas[] = ['txt' => $this->centrar('Tel: ' . $configuracion['telefono'], $ancho), 'bold' => false];
            if (!empty($configuracion['email'])) $lineas[] = ['txt' => $this->centrar($configuracion['email'], $ancho), 'bold' => false];
            $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];

            // Documento
            $lineas[] = ['txt' => $this->izquierda($this->truncar(($esFactura ? 'FACTURA' : 'DOCUMENTO NO FISCAL / RECIBO INTERNO') . ': ' . ($venta['folio'] ?? $venta['id']), $ancho), $ancho), 'bold' => true];
            if ($esFactura) {
                $lineas[] = ['txt' => $this->izquierda('CAI: ' . ($venta['cai'] ?? $configuracion['sar_cai'] ?? ''), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->izquierda('Rango Autorizado:', $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->izquierda($this->truncar($venta['rango_autorizado'] ?? (($configuracion['sar_rango_inicial'] ?? '') . ' al ' . ($configuracion['sar_rango_final'] ?? '')), $ancho), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->izquierda('F. Limite Emision: ' . ($venta['fecha_limite_emision'] ?? $configuracion['sar_fecha_limite'] ?? ''), $ancho), 'bold' => false];
            }
            $lineas[] = ['txt' => $this->izquierda('Fecha Emision: ' . ($venta['fecha_venta'] ?? date('Y-m-d H:i:s')), $ancho), 'bold' => false];
            $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];

            // Cliente
            $lineas[] = ['txt' => $this->centrar('DATOS DE CLIENTE', $ancho), 'bold' => true];
            $lineas[] = ['txt' => $this->izquierda('Nombre: ' . (!empty($venta['cliente_nombre']) ? $venta['cliente_nombre'] : 'Consumidor Final'), $ancho), 'bold' => false];
            $lineas[] = ['txt' => $this->izquierda('RTN/ID: ' . (!empty($venta['cliente_rtn']) ? $venta['cliente_rtn'] : 'S/N'), $ancho), 'bold' => false];
            if (!empty($venta['cliente_direccion'])) $lineas[] = ['txt' => $this->izquierda('Direccion: ' . $venta['cliente_direccion'], $ancho), 'bold' => false];
            $lineas[] = ['txt' => $this->izquierda('Cajero: ' . ($venta['cajero'] ?? 'Cajero'), $ancho), 'bold' => false];

            if ($esFactura) {
                $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->centrar('DATOS DE ADQUIRIENTE EXONERADO', $ancho), 'bold' => true];
                $lineas[] = ['txt' => $this->izquierda('Orden Compra Exenta: _____________', $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->izquierda('Constancia Registro: _____________', $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->izquierda('Registro SAG: ____________________', $ancho), 'bold' => false];
            }

            $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];

            // Detalle de productos
            $der = 14;
            $izq = $ancho - $der;
            $lineas[] = ['txt' => $this->izquierda($this->truncar('Cant/Descripcion', $izq), $izq) . $this->derecha('P.U.', 7) . $this->derecha('Total', 7), 'bold' => true];
            foreach ($detalles as $item) {
                $cantidadItem = (float)($item['cantidad'] ?? 0);
                $cantidadTexto = rtrim(rtrim(number_format($cantidadItem, 3, '.', ''), '0'), '.');
                $nombreProducto = $item['nombre'] ?? 'Producto';
                $precioUnitario = (float)($item['precio_unitario'] ?? 0);
                $precioLista = (float)($item['precio_lista'] ?? $precioUnitario);
                $descuentoUnitario = (float)($item['descuento_unitario'] ?? max(0, $precioLista - $precioUnitario));
                $subtotal = (float)($item['subtotal'] ?? 0);
                $tipoPres = $item['tipo_presentacion'] ?? 'unidad';

                $nomPres = !empty($item['nombre_presentacion']) ? $item['nombre_presentacion'] : ($tipoPres === 'empaque' ? 'Caja' : 'Unidad');
                $factor = (float)($item['factor_unidades'] ?? 1);
                $factorTexto = rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.');
                $etiquetaPres = $tipoPres === 'empaque' ? ' [' . $nomPres . ' x' . $factorTexto . ']' : '';

                $lineas[] = ['txt' => $this->izquierda($this->truncar($nombreProducto . $etiquetaPres, $izq), $izq) . $this->derecha('', 7) . $this->derecha('', 7), 'bold' => false];
                $lineas[] = ['txt' => $this->izquierda($this->truncar($cantidadTexto . ($tipoPres === 'empaque' ? ' ' . strtolower($nomPres) : '') . ' x', $izq), $izq) . $this->derecha(number_format($precioUnitario, 2), 7) . $this->derecha(number_format($subtotal, 2), 7), 'bold' => false];
                if ($descuentoUnitario > 0.001) {
                    $lineas[] = ['txt' => $this->izquierda($this->truncar('  Lista ' . number_format($precioLista, 2) . ' - Desc. ' . number_format($descuentoUnitario, 2), $ancho), $ancho), 'bold' => false];
                }
            }

            $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];

            // Totales e ISV
            if ($esFactura) {
                $lineas[] = ['txt' => $this->fila('Importe Exento:', $simbolo . ' ' . number_format($importeExento, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Importe Exonerado:', $simbolo . ' ' . number_format($importeExonerado, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Importe Gravado 15%:', $simbolo . ' ' . number_format($gravado15, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('ISV 15%:', $simbolo . ' ' . number_format($isv15, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Importe Gravado 18%:', $simbolo . ' ' . number_format($gravado18, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('ISV 18%:', $simbolo . ' ' . number_format($isv18, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Descuentos y Rebajas Otorgadas:', $simbolo . ' ' . number_format($descuentoRebaja, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Total a Pagar:', $simbolo . ' ' . number_format($totalVenta, 2), $ancho), 'bold' => true];
                $lineas[] = ['txt' => $this->fila('Efectivo / Recibido:', $simbolo . ' ' . number_format($venta['pagado_con'] ?? $venta['efectivo'] ?? 0, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Cambio:', $simbolo . ' ' . number_format($venta['cambio'] ?? 0, 2), $ancho), 'bold' => false];
            } else {
                $lineas[] = ['txt' => $this->fila('Descuentos y Rebajas Otorgadas:', $simbolo . ' ' . number_format($descuentoRebaja, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('TOTAL:', $simbolo . ' ' . number_format($totalVenta, 2), $ancho), 'bold' => true];
                $lineas[] = ['txt' => $this->fila('Forma de pago:', ucfirst($venta['metodo_pago'] ?? 'efectivo'), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Efectivo / Recibido:', $simbolo . ' ' . number_format($venta['pagado_con'] ?? $venta['efectivo'] ?? 0, 2), $ancho), 'bold' => false];
                $lineas[] = ['txt' => $this->fila('Cambio:', $simbolo . ' ' . number_format($venta['cambio'] ?? 0, 2), $ancho), 'bold' => false];
            }

            $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];

            // Monto en letras y pie
            $lineas[] = ['txt' => $this->izquierda('SON: ' . $this->numeroALetras($totalVenta), $ancho), 'bold' => true];
            $lineas[] = ['txt' => $this->separador($ancho), 'bold' => false];
            $lineas[] = ['txt' => $this->centrar('*** ' . $etiquetaCopia . ' ***', $ancho), 'bold' => false];
            if ($esFactura && ((string)($configuracion['ticket_mostrar_sar'] ?? '1') === '1')) {
                $lineas[] = ['txt' => $this->centrar('La factura es beneficio de todos, exijala.', $ancho), 'bold' => false];
            }
            $lineas[] = ['txt' => $this->centrar($configuracion['mensaje_ticket'] ?? '¡Gracias por su compra!', $ancho), 'bold' => false];
            $lineas[] = ['txt' => '', 'bold' => false];

            $bytesTicket .= $this->construirBytes($lineas, true, $indiceCopia === $totalCopias - 1);
        }

        return $bytesTicket;
    }

    /**
     * Convierte las líneas a comandos ESC/POS (texto + corte de papel opcional).
     * $cortar = true: alimenta 3 líneas y corta al finalizar este bloque.
     * $protegerFin = true: agrega líneas de relleno DESPUÉS del corte para que el
     * comando de corte no vaya al final del búfer y se pierda al cerrar el socket.
     */
    public function construirBytes(array $lineas, bool $cortar = true, bool $protegerFin = false)
    {
        $bytes  = "\x1b\x40"; // Inicializar impresora
        $bytes .= "\x1b\x74\x1a"; // Página de códigos Latin-1/CP850
        foreach ($lineas as $linea) {
            $texto = $this->normalizar($linea['txt'] ?? '');
            $bytes .= ($linea['bold'] ?? false) ? "\x1b\x45\x01" : "\x1b\x45\x00";
            $bytes .= $texto . "\x0a";
        }
        $bytes .= "\x1b\x45\x00"; // Quitar negrita
        if ($cortar) {
            $bytes .= "\x1b\x64\x03"; // Alimentar 3 líneas antes del corte
            $bytes .= "\x1d\x56\x42"; // Corte de papel (parcial)
        }
        if ($protegerFin) {
            $bytes .= "\x1b\x64\x07"; // Alimentar 7 líneas tras el último corte
        }
        return $bytes;
    }

    private function normalizar(string $texto)
    {
        $convertido = @iconv('UTF-8', 'CP850//TRANSLIT', $texto);
        return $convertido !== false ? $convertido : $texto;
    }

    private function truncar(string $texto, int $ancho)
    {
        if (strlen($texto) <= $ancho) return $texto;
        return $this->normalizar(substr($texto, 0, max(0, $ancho - 1)) . '~');
    }

    private function izquierda(string $texto, int $ancho)
    {
        return str_pad($this->truncar($texto, $ancho), $ancho, ' ', STR_PAD_RIGHT);
    }

    private function derecha(string $texto, int $ancho)
    {
        return str_pad(substr($texto, 0, $ancho), $ancho, ' ', STR_PAD_LEFT);
    }

    private function centrar(string $texto, int $ancho)
    {
        return str_pad($this->truncar($texto, $ancho), $ancho, ' ', STR_PAD_BOTH);
    }

    private function separador(int $ancho)
    {
        return str_repeat('-', $ancho);
    }

    private function fila(string $etiqueta, string $valor, int $ancho)
    {
        return str_pad($this->truncar($etiqueta, $ancho - 12), $ancho - 12, ' ', STR_PAD_RIGHT) . $this->derecha($valor, 12);
    }

    private function numeroALetras($numero)
    {
        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas  = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $dieces   = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $entero   = floor((float)$numero);
        $centavos = str_pad(round(((float)$numero - $entero) * 100), 2, '0', STR_PAD_LEFT);

        if ($entero == 0) return 'CERO LEMPIRAS CON ' . $centavos . '/100 CENTAVOS';
        if ($entero == 100) return 'CIEN LEMPIRAS CON ' . $centavos . '/100 CENTAVOS';

        $convertirTres = function ($n) use ($unidades, $decenas, $dieces, $centenas) {
            $c = floor($n / 100);
            $d = floor(($n % 100) / 10);
            $u = $n % 10;
            $texto = '';
            if ($c > 0) $texto .= ($c == 1 && $d == 0 && $u == 0) ? 'CIEN ' : $centenas[$c] . ' ';
            if ($d == 1) {
                $texto .= $dieces[$u] . ' ';
            } elseif ($d == 2 && $u > 0) {
                $texto .= 'VEINTI' . strtolower($unidades[$u]) . ' ';
            } else {
                if ($d > 0) $texto .= $decenas[$d] . ($u > 0 ? ' Y ' : ' ');
                if ($u > 0 && $d != 2) $texto .= $unidades[$u] . ' ';
            }
            return trim($texto);
        };

        $final = '';
        if ($entero >= 1000) {
            $miles = floor($entero / 1000);
            $resto = $entero % 1000;
            $final .= ($miles == 1) ? 'MIL ' : $convertirTres($miles) . ' MIL ';
            if ($resto > 0) $final .= $convertirTres($resto) . ' ';
        } else {
            $final .= $convertirTres($entero) . ' ';
        }

        return trim($final) . ' LEMPIRAS CON ' . $centavos . '/100 CENTAVOS';
    }
}