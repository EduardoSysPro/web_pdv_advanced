<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Iniciar Sesi&oacute;n - Web PDV</title>
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/estilos.css">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/login.css">
</head>
<body class="login-page">
    <div class="login-shell">
        <aside class="login-hero" aria-label="Información de la marca">
            <div class="login-hero__pattern"></div>
            <div class="login-hero__content">

                <p class="login-hero__eyebrow">Plataforma inteligente</p>

                <p class="login-hero__text">
                    Optimiza tus ventas y gestiona tu inventario en tiempo real. ¡Ahorra tiempo y toma el control de tu negocio!
                </p>
            </div>
            <div class="login-hero__footer">
                &copy; 2026 Web PDV. Todos los derechos reservados - Carlos Gonzales Contacto: 9552-4118.
            </div>
        </aside>

        <main class="login-panel">
            <div class="login-panel__inner">


                <div class="login-header">
                    <h2>¡Bienvenido de nuevo!</h2>
                </div>

                <?php if (isset($error) && !empty($error)): ?>
                    <div class="login-alert" role="alert" aria-live="assertive">
                        <span class="login-alert__icon">⚠</span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo URL_BASE; ?>autenticar" method="POST" class="login-form" novalidate>
                    <div class="login-field">
                        <label for="usuario">Usuario / Correo Electrónico</label>
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            value="<?php echo htmlspecialchars($usuarioGuardado ?? ''); ?>"
                            placeholder="Ingresa tu usuario"
                            required
                            autofocus
                            autocomplete="username">
                    </div>

                    <div class="login-field">
                        <label for="password">Contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password">
                    </div>

                    <button type="submit" class="login-button">
                        Iniciar sesión
                    </button>
                </form>

            </div>
        </main>
    </div>
</body>
</html>
