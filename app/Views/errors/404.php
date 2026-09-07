<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --verde-principal: #00a86b;
            --verde-claro: #dff9ee;
            --fondo: #f4f6f8;
            --oscuro: #1f2a37;
            --gris: #5f6f7f;
            --blanco: #ffffff;
            --borde: rgba(31, 42, 55, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--fondo) 0%, #edf2f5 100%);
            font-family: 'Inter', sans-serif;
            color: var(--oscuro);
        }

        .contenedor-404 {
            width: min(90vw, 760px);
            background: var(--blanco);
            border: 1px solid var(--borde);
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            padding: 42px 36px;
            text-align: center;
        }

        .icono-circulo {
            width: 110px;
            height: 110px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--verde-claro);
            color: var(--verde-principal);
            border: 1px solid rgba(0, 168, 107, 0.15);
            box-shadow: 0 10px 25px rgba(0, 168, 107, 0.12);
        }

        .icono-circulo i {
            font-size: 52px;
            line-height: 1;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 12px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--verde-principal);
            background: rgba(0, 168, 107, 0.08);
            border-radius: 999px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.1;
            color: var(--oscuro);
        }

        p {
            margin: 0 auto;
            max-width: 560px;
            font-size: 1.04rem;
            line-height: 1.7;
            color: var(--gris);
        }

        .acciones {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 220px;
            min-height: 48px;
            border-radius: 12px;
            padding: 12px 22px;
            border: 1px solid transparent;
            font-weight: 700;
            font-size: 0.96rem;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--verde-principal);
            color: var(--blanco);
            box-shadow: 0 10px 25px rgba(0, 168, 107, 0.2);
        }

        .btn-secondary {
            background: transparent;
            color: var(--oscuro);
            border-color: rgba(31, 42, 55, 0.18);
        }

        @media (max-width: 560px) {
            .contenedor-404 {
                padding: 28px 20px;
            }

            .acciones {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="contenedor-404">
        <div class="icono-circulo" aria-hidden="true">
            <i class="bi bi-search"></i>
        </div>

        <div class="eyebrow">Error 404</div>
        <h1>404 - Página no encontrada</h1>
        <p>
            Lo sentimos, la página o recurso que buscas no existe o ha sido movido.
        </p>

        <div class="acciones">
            <a href="<?= htmlspecialchars(URL_BASE); ?>ventas" class="btn btn-primary">Volver al Punto de Venta</a>
            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Regresar atrás</button>
        </div>
    </div>
</body>
</html>
