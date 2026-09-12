<?php
$nombreUsuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';
$rolUsuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
$esAdministrador = (int)($_SESSION['rol_id'] ?? 0) === 1 || in_array(strtolower((string)$rolUsuario), ['admin', 'administrador'], true);
$rolEtiqueta = $esAdministrador ? 'Administrador' : 'Cajero';
$rolClase = $rolUsuario === 'admin' ? 'badge-admin' : 'badge-cajero';
$fechaActual = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
    <title>Web PDV - Sistema de Punto de Venta</title>
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/estilos.css">
</head>
<body>
    <header class="app-header">
        <div class="header-contenedor">
            <div class="header-logo">
                <span class="logo-icono">&#128722;</span>
                <span class="logo-texto">Web PDV</span>
                <span class="logo-sub">Abarrotes</span>
            </div>
            <div class="header-info">
                <div class="header-usuario">
                    <span class="usuario-nombre">&#128100; <?php echo htmlspecialchars($nombreUsuario); ?></span>
                    <span class="badge <?php echo $rolClase; ?>"><?php echo $rolEtiqueta; ?></span>
                </div>
                <div class="header-fecha">
                    <span class="fecha-icono">&#128197;</span>
                    <span><?php echo $fechaActual; ?></span>
                </div>
                <a href="<?php echo URL_BASE; ?>logout" class="btn-logout">
                    <span>&#128274;</span> Cerrar Sesi&oacute;n
                </a>
                <a href="<?php echo URL_BASE; ?>productos" class="btn-logout">F3 Productos</a>
                <a href="<?php echo URL_BASE; ?>reportes" class="btn-logout">Reportes</a>
                <?php if ($esAdministrador): ?><a href="<?php echo URL_BASE; ?>configuracion" class="btn-logout">Configuración</a><a href="<?php echo URL_BASE; ?>usuarios" class="btn-logout">Usuarios</a><?php endif; ?>
            </div>
        </div>
    </header>
    <main class="app-main">
