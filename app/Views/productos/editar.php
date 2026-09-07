<?php
$titulo = 'Editar Producto';
$accion = URL_BASE . 'productos/actualizar/' . (int)$producto['id'];
require APP_PATH . 'Views/productos/formulario.php';
