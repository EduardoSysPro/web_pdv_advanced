<!DOCTYPE html>
<html lang="es">
<head>
    <script>(function(){try{var t=localStorage.getItem('web_pdv_tema')||'claro';if(t==='oscuro'||t==='minimal')document.documentElement.setAttribute('data-tema',t);}catch(e){}})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
    <title>Iniciar Sesi&oacute;n - Web PDV</title>
    <link rel="icon" type="image/x-icon" href="<?php echo URL_BASE; ?>favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/estilos.css">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/login.css">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/dark.css">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/minimal.css?v=<?php echo filemtime(PUBLIC_PATH . 'css/minimal.css'); ?>">
</head>
<body class="login-page">
    <div class="login-shell">
        <aside class="login-hero" aria-label="Información de la marca">
            <div class="login-hero__pattern"></div>
            <div class="login-hero__content">

                <div class="login-hero__brand">
                    <span class="login-hero__badge"><i class="fa-solid fa-store"></i></span>
                    <div>
                        <strong class="login-hero__logo">Web PDV</strong>
                        <span class="login-hero__slogan">Administra tu negocio en un solo lugar</span>
                    </div>
                </div>

                <p class="login-hero__eyebrow">Un solo sistema, todos tus negocios</p>

                <h1>Punto de venta pensado para crecer contigo</h1>

                <p class="login-hero__text">
                    Vende, controla tu inventario, factura y consulta tus reportes desde cualquier dispositivo,
                    adaptado a la forma de trabajar de tu negocio.
                </p>

                

                <div class="login-hero__features">
                    <span><i class="fa-solid fa-bolt"></i> Ventas rápidas</span>
                    <span><i class="fa-solid fa-boxes-stacked"></i> Inventario</span>
                    <span><i class="fa-solid fa-chart-line"></i> Reportes</span>
                    <span><i class="fa-solid fa-calculator"></i> Corte de caja</span>
                </div>
            </div>
            <div class="login-hero__footer">
                &copy; 2026 Web PDV. Todos los derechos reservados - Carlos Gonzales Contacto: 9552-4118.
            </div>
        </aside>

        <main class="login-panel">
            <button class="login-tema-toggle" id="pos-boton-tema" type="button" aria-label="Cambiar tema claro/oscuro"><i class="fa-solid fa-moon"></i></button>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
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
    <script>
        (function () {
            var botonTema = document.getElementById('pos-boton-tema');
            var temaKey = 'web_pdv_tema';
            var ordenTemas = ['claro', 'minimal', 'oscuro'];

            function temaActual() {
                var t = document.documentElement.getAttribute('data-tema');
                return (t === 'oscuro' || t === 'minimal') ? t : 'claro';
            }

            function iconoTema(t) {
                if (t === 'oscuro') return '<i class="fa-solid fa-sun"></i>';
                if (t === 'minimal') return '<i class="fa-solid fa-wand-magic-sparkles"></i>';
                return '<i class="fa-solid fa-moon"></i>';
            }

            function actualizarIconoTema(t) {
                if (!botonTema) return;
                botonTema.innerHTML = iconoTema(t);
                botonTema.setAttribute('aria-label', t === 'oscuro' ? 'Tema oscuro. Cambiar a claro' : (t === 'minimal' ? 'Tema minimalista. Cambiar a oscuro' : 'Tema claro. Cambiar a minimalista'));
            }

            function aplicarTema(t) {
                if (t === 'claro') {
                    document.documentElement.removeAttribute('data-tema');
                } else {
                    document.documentElement.setAttribute('data-tema', t);
                }
                actualizarIconoTema(t);
                try { localStorage.setItem(temaKey, t); } catch (e) {}
            }

            actualizarIconoTema(temaActual());
            botonTema.addEventListener('click', function () {
                var actual = temaActual();
                aplicarTema(ordenTemas[(ordenTemas.indexOf(actual) + 1) % ordenTemas.length]);
            });
        }());
    </script>
</body>
</html>
