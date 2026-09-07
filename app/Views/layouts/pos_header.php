<?php
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Cajero');
$rolUsuario = $_SESSION['rol'] ?? 'cajero';
$esAdministrador = (int)($_SESSION['rol_id'] ?? 0) === 1 || in_array(strtolower((string)$rolUsuario), ['admin', 'administrador'], true);
$rolEtiqueta = $esAdministrador ? 'ADMINISTRADOR' : 'CAJERO';
$rutaActual = trim((string)($_GET['url'] ?? ''), '/');
$esRutaActiva = static function ($ruta) use ($rutaActual) {
    return $rutaActual === $ruta || strpos($rutaActual, $ruta . '/') === 0;
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web PDV - <?php echo htmlspecialchars($tituloPagina ?? 'Punto de Venta'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/estilos.css">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/pos.css">
</head>
<body class="pos-body pos-modulo-body">
    <div class="pos-app-shell" id="pos-app-shell">
        <aside class="pos-sidebar" aria-label="Menú principal">
            <div class="pos-sidebar-brand">
                <span class="pos-brand-icon" aria-hidden="true"><i class="fa-solid fa-cash-register"></i></span>
                <span class="pos-brand-text">Punto de Venta</span>
            </div>

            <nav class="pos-sidebar-nav">
                <a class="pos-sidebar-link <?php echo $esRutaActiva('ventas') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>ventas" data-tooltip="Ventas"><i class="fa-solid fa-cart-shopping"></i><span>Ventas</span></a>
                <a class="pos-sidebar-link <?php echo $esRutaActiva('movil') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>movil" data-tooltip="Terminal Móvil"><i class="fa-solid fa-mobile-screen"></i><span>Vista Móvil</span></a>
                <a class="pos-sidebar-link <?php echo $esRutaActiva('clientes') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>clientes" data-tooltip="Clientes"><i class="fa-solid fa-users"></i><span>Clientes</span></a>
                <?php if ($esAdministrador): ?>
                    <a class="pos-sidebar-link <?php echo $esRutaActiva('productos') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>productos" data-tooltip="Productos"><i class="fa-solid fa-box"></i><span>Productos</span></a>
                    <a class="pos-sidebar-link <?php echo $esRutaActiva('inventario') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>inventario" data-tooltip="Inventario"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a>
                <?php endif; ?>
                <a class="pos-sidebar-link <?php echo $esRutaActiva('caja') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>caja" data-tooltip="Corte de Caja"><i class="fa-solid fa-calculator"></i><span>Corte de Caja</span></a>
                <a class="pos-sidebar-link <?php echo $esRutaActiva('comprobantes') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>comprobantes" data-tooltip="Comprobantes"><i class="fa-solid fa-receipt"></i><span>Reimpresión</span></a>
                <?php if ($esAdministrador): ?>
                    <a class="pos-sidebar-link <?php echo $esRutaActiva('reportes') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>reportes" data-tooltip="Reportes"><i class="fa-solid fa-chart-line"></i><span>Reportes</span></a>
                <?php endif; ?>
                <?php if ($esAdministrador): ?>
                    <div class="pos-sidebar-divider" aria-hidden="true"></div>
                    <a class="pos-sidebar-link <?php echo $esRutaActiva('configuracion') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>configuracion" data-tooltip="Configuración"><i class="fa-solid fa-gear"></i><span>Configuración</span></a>
                    <a class="pos-sidebar-link <?php echo $esRutaActiva('usuarios') ? 'is-active' : ''; ?>" href="<?php echo URL_BASE; ?>usuarios" data-tooltip="Usuarios"><i class="fa-solid fa-user-gear"></i><span>Usuarios</span></a>
                <?php endif; ?>
            </nav>

            <div class="pos-sidebar-bottom">
                <a class="pos-sidebar-link pos-sidebar-logout" href="<?php echo URL_BASE; ?>logout" data-tooltip="Salir"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Salir</span></a>
            </div>
        </aside>

        <div class="pos-workspace">
            <header class="pos-top-header">
                <button class="pos-sidebar-toggle" id="pos-sidebar-toggle" type="button" aria-label="Contraer menú" aria-expanded="true"><i class="fa-solid fa-bars"></i></button>
                <div class="pos-context-info">
                    <div class="pos-context-item"><i class="fa-regular fa-user"></i><span class="pos-context-label">Usuario</span><strong><?php echo $nombreUsuario; ?></strong></div>
                    <span class="pos-role-badge"><?php echo $rolEtiqueta; ?></span>
                    <span class="pos-branch-badge"><i class="fa-solid fa-store" aria-hidden="true"></i><?php echo htmlspecialchars($_SESSION['sucursal_nombre'] ?? 'Abarrotes Central'); ?></span>
                    <div class="pos-context-item"><i class="fa-solid fa-cash-register"></i><span class="pos-context-label">Caja</span><strong><?php echo htmlspecialchars($_SESSION['caja_nombre'] ?? 'Caja 01'); ?></strong></div>
                </div>
                <div class="pos-header-clock"><i class="fa-regular fa-calendar"></i><time id="info-fecha-hora"><?php echo date('d/m/Y H:i'); ?></time></div>
            </header>

            <main class="app-main pos-modulo-main <?php echo htmlspecialchars($claseMain ?? ''); ?>">
            <?php $mensajeAcceso = $_SESSION['error_usuarios'] ?? ($_SESSION['error_configuracion'] ?? null); unset($_SESSION['error_usuarios'], $_SESSION['error_configuracion']); ?>
            <?php if ($mensajeAcceso): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($mensajeAcceso); ?></div><?php endif; ?>
