<?php
$producto = $producto ?? [];
$titulo = 'Nuevo Producto';
$accion = URL_BASE . 'productos/guardar';
require APP_PATH . 'Views/productos/formulario.php';
