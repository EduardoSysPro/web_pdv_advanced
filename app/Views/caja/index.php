<?php $tituloPagina = 'Corte de Caja'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">F8 / Turno</span>
        <h1>Corte de Caja</h1>
        <p>Controla el efectivo y cierra tu jornada con precisión.</p>
    </div>
    <a class="btn-pos btn-pos-primary" href="<?php echo URL_BASE; ?>ventas">Volver a ventas</a>
</section>

<?php if ($error): ?>
    <div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (empty($turno) && !empty($_SESSION['ultimo_corte_id'])): ?>
    <?php unset($_SESSION['ultimo_corte_id']); ?>
<?php endif; ?>

<?php if ($mensaje): ?>
    <div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>

<?php if (empty($turno)): ?>
    <?php if (!empty($ultimosCortes)): ?>
        <section class="tarjeta">
            <h2 class="tarjeta-titulo">Cortes disponibles para imprimir</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <?php foreach ($ultimosCortes as $corte): ?>
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
                        <div>
                            <span style="font-size: 12px; color: #666;">Fecha de cierre</span>
                            <strong><?php echo date('d/m/Y H:i', strtotime($corte['fecha_cierre'])); ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 12px; color: #666;">Monto declarado</span>
                            <strong><?php echo formatearMoneda($corte['monto']); ?></strong>
                        </div>
                        <a href="<?php echo URL_BASE; ?>caja/ticket/<?php echo (int)$corte['id']; ?>" class="btn-pos btn-primary" style="text-align: center; text-decoration: none; padding: 8px; font-size: 13px;">Imprimir</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php if (empty($turno)): ?>
    <section class="tarjeta apertura-caja">
        <h2 class="tarjeta-titulo">Apertura de Caja</h2>
        <p>Registra el efectivo con el que inicia tu turno.</p>
        <form method="POST" action="<?php echo URL_BASE; ?>caja/abrir" class="caja-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <?php if (!empty($cajaSinAsignar)): ?><div class="campo"><label for="caja_id">Caja para este turno</label><select class="form-control-pos" id="caja_id" name="caja_id" required><?php foreach ($cajasDisponibles as $cajaDisponible): ?><option value="<?php echo (int)$cajaDisponible['id']; ?>"><?php echo htmlspecialchars($cajaDisponible['nombre']); ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <div class="campo">
                <label for="fondo_inicial">Fondo inicial (L)</label>
                <input class="form-control-pos" id="fondo_inicial" name="fondo_inicial" type="number" min="0" step="0.01" value="0.00" required autofocus>
            </div>
            <button class="btn-pos btn-pos-success" type="submit">Abrir Caja</button>
        </form>
    </section>
<?php else: ?>
    <div class="resumen-caja">
        <div>
            <span>Fondo inicial</span>
            <strong><?php echo formatearMoneda($resumen['fondo_inicial'] ?? ($resumen['caja']['fondo_inicial'] ?? 0)); ?></strong>
        </div>
        <div>
            <span>Ventas en efectivo</span>
            <strong><?php echo formatearMoneda($resumen['ventas']['efectivo'] ?? 0); ?></strong>
        </div>
        <div>
            <span>Entradas extra</span>
            <strong><?php echo formatearMoneda($resumen['movimientos']['ingreso'] ?? 0); ?></strong>
        </div>
        <div>
            <span>Salidas / gastos</span>
            <strong><?php echo formatearMoneda($resumen['movimientos']['egreso'] ?? 0); ?></strong>
        </div>
        <div class="esperado">
            <span>Total esperado en caja</span>
            <strong><?php echo formatearMoneda($resumen['efectivo_esperado'] ?? 0); ?></strong>
        </div>
    </div>

    <section class="tarjeta caja-panel">
        <h2 class="tarjeta-titulo">Movimientos rápidos</h2>
        <div class="pestanas-caja">
            <button type="button" class="activa" data-tipo="ingreso">[+] Ingresar dinero (Cambio)</button>
            <button type="button" data-tipo="egreso">[-] Sacar dinero (Gasto / Insumo)</button>
        </div>

        <form method="POST" action="<?php echo URL_BASE; ?>caja/movimiento" class="caja-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="tipo" id="tipo-movimiento" value="ingreso">

            <div class="campo">
                <label for="monto">Monto (L)</label>
                <input class="form-control-pos" id="monto" name="monto" type="number" min="0.01" step="0.01" placeholder="0.00" required>
            </div>

            <div class="campo">
                <label for="concepto">Concepto / Motivo</label>
                <input class="form-control-pos" id="concepto" name="concepto" maxlength="255" placeholder="Ej. Compra de bolsas / fondo para cambio" required>
            </div>

            <button class="btn-pos btn-pos-primary" type="submit" id="btn-movimiento">Registrar ingreso</button>
        </form>
    </section>

    <section class="tarjeta caja-panel corte-panel">
        <h2 class="tarjeta-titulo">Cierre de caja</h2>

        <div class="corte-datos">
            <div>
                <span>Ventas tarjeta</span>
                <strong><?php echo formatearMoneda($resumen['ventas']['tarjeta'] ?? 0); ?></strong>
            </div>
            <div>
                <span>Ventas transferencia</span>
                <strong><?php echo formatearMoneda($resumen['ventas']['transferencia'] ?? 0); ?></strong>
            </div>
        </div>

        <form method="POST" action="<?php echo URL_BASE; ?>caja/cerrar" class="caja-form" id="form-cierre">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="campo">
                <label for="monto_declarado">Efectivo contado físicamente (L)</label>
                <input class="form-control-pos" id="monto_declarado" name="monto_declarado" type="number" min="0" step="0.01" placeholder="0.00" required>
            </div>

            <div class="diferencia-caja">
                <span>Diferencia</span>
                <strong id="diferencia">L 0.00</strong>
            </div>

            <button class="btn-pos btn-pos-danger" type="submit">Cerrar Caja</button>
        </form>
    </section>

    <section class="tarjeta caja-panel">
        <h2 class="tarjeta-titulo">Historial de movimientos</h2>
        <div class="tabla-responsive">
            <table class="tabla-catalogo">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>Concepto</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr>
                            <td colspan="5" class="tabla-vacia">No hay movimientos registrados en este turno.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_hora'] ?? $movimiento['fecha'] ?? 'now')); ?></td>
                                <td><?php echo htmlspecialchars($movimiento['usuario_nombre'] ?? $_SESSION['usuario'] ?? 'Usuario'); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($movimiento['tipo'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($movimiento['concepto'] ?? 'Sin concepto'); ?></td>
                                <td><?php echo formatearMoneda($movimiento['monto'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<script>
(function () {
    const esperado = <?php echo $resumen ? json_encode((float)($resumen['efectivo_esperado'] ?? 0)) : '0'; ?>;
    const contado = document.getElementById('monto_declarado');
    const diferencia = document.getElementById('diferencia');

    if (contado && diferencia) {
        contado.addEventListener('input', function () {
            const valor = (Number(contado.value) || 0) - esperado;
            const texto = (valor < 0 ? '-L ' : 'L ') + Math.abs(valor).toFixed(2);
            diferencia.textContent = texto;
            diferencia.className = valor < 0 ? 'faltante' : (valor > 0 ? 'sobrante' : '');
        });
    }

    document.querySelectorAll('.pestanas-caja button').forEach(function (boton) {
        boton.addEventListener('click', function () {
            document.querySelectorAll('.pestanas-caja button').forEach(function (btn) {
                btn.classList.remove('activa');
            });
            boton.classList.add('activa');
            document.getElementById('tipo-movimiento').value = boton.dataset.tipo;
            document.getElementById('btn-movimiento').textContent = boton.dataset.tipo === 'ingreso' ? 'Registrar ingreso' : 'Registrar retiro';
        });
    });
}());
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
