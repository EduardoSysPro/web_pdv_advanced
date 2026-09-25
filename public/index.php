<?php

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);
define('CORE_PATH', ROOT_PATH . 'core' . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', __DIR__ . DIRECTORY_SEPARATOR);

require_once CONFIG_PATH . 'app.php';
require_once CONFIG_PATH . 'database.php';
require_once CORE_PATH . 'Controller.php';
require_once CORE_PATH . 'Router.php';

if (session_status() === PHP_SESSION_NONE) {
    $conexionSegura = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    if (defined('FORZAR_HTTPS') && FORZAR_HTTPS && !$conexionSegura) {
        $urlHttps = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $urlHttps);
        exit;
    }

    if ($conexionSegura) {
        ini_set('session.cookie_secure', 1);
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

$router = new Router();

$router->get('/',      'HomeController@index');
$router->get('/login', 'AuthController@login');
$router->post('/autenticar', 'AuthController@autenticar');
$router->post('/logout', 'AuthController@logout');

$router->get('/movil', 'VentasController@movil');

$router->get('/cotizaciones', 'CotizacionesController@index');
$router->get('/cotizaciones/crear', 'CotizacionesController@crear');
$router->post('/cotizaciones/guardar', 'CotizacionesController@guardar');
$router->post('/cotizaciones/actualizar/{id}', 'CotizacionesController@actualizar');
$router->get('/cotizaciones/ver/{id}', 'CotizacionesController@ver');
$router->get('/cotizaciones/imprimir/{id}', 'CotizacionesController@imprimir');
$router->get('/cotizaciones/editar/{id}', 'CotizacionesController@editar');
$router->post('/cotizaciones/cancelar/{id}', 'CotizacionesController@cancelar');
$router->get('/cotizaciones/facturar/{id}', 'CotizacionesController@facturar');
$router->get('/cotizaciones/pendientes', 'CotizacionesController@pendientes');
$router->get('/cotizaciones/buscar-productos', 'CotizacionesController@buscarProductosAjax');
$router->get('/cotizaciones/buscar-por-codigo', 'CotizacionesController@buscarPorCodigo');

$router->get('/ventas', 'VentasController@index');
$router->post('/ventas/buscar-producto', 'VentasController@buscarProducto');
$router->get('/ventas/buscar-producto',  'VentasController@buscarProducto');
$router->get('/ventas/buscar-productos', 'VentasController@buscarProductosAjax');
    $router->post('/ventas/buscar-productos', 'VentasController@buscarProductosAjax');
    $router->get('/ventas/historial-hoy', 'VentasController@historialHoy');
$router->get('/ventas/buscarClientePorRtn', 'VentasController@buscarClientePorRtn');
$router->post('/ventas/buscarClientePorRtn', 'VentasController@buscarClientePorRtn');
$router->get('/ventas/buscar-cliente-por-rtn', 'VentasController@buscarClientePorRtn');
$router->post('/ventas/buscar-cliente-por-rtn', 'VentasController@buscarClientePorRtn');
$router->post('/ventas/guardar', 'VentasController@guardarVenta');
$router->get('/ventas/ticket/{id}', 'VentasController@imprimirTicket');

$router->get('/productos', 'ProductosController@index');
$router->get('/productos/exportar-csv', 'ProductosController@exportarCsv');
$router->get('/productos/crear', 'ProductosController@crear');
$router->post('/productos/guardar', 'ProductosController@guardar');
$router->get('/productos/editar/{id}', 'ProductosController@editar');
$router->post('/productos/actualizar/{id}', 'ProductosController@actualizar');
$router->post('/productos/eliminar/{id}', 'ProductosController@eliminar');

$router->get('/categorias', 'CategoriasController@index');
$router->get('/categorias/crear', 'CategoriasController@crear');
$router->post('/categorias/guardar', 'CategoriasController@guardar');
$router->get('/categorias/editar/{id}', 'CategoriasController@editar');
$router->post('/categorias/actualizar/{id}', 'CategoriasController@actualizar');
$router->post('/categorias/eliminar/{id}', 'CategoriasController@eliminar');

$router->get('/inventario', 'InventarioController@index');
$router->post('/inventario/ajustar', 'InventarioController@ajustar');
$router->get('/inventario/bajo-stock', 'InventarioController@bajoStock');

$router->get('/compras', 'ComprasController@index');
$router->get('/compras/buscar-producto', 'ComprasController@buscarProducto');
$router->get('/compras/buscar-proveedores', 'ComprasController@buscarProveedores');
$router->post('/compras/buscar-proveedores', 'ComprasController@buscarProveedores');
$router->post('/compras/guardar-proveedor-ajax', 'ComprasController@guardarProveedorAjax');
$router->post('/compras/guardar', 'ComprasController@guardar');
$router->post('/compras/anular/{id}', 'ComprasController@anular');
$router->get('/compras/ver/{id}', 'ComprasController@ver');
$router->get('/compras/pagos', 'ComprasController@pagos');
$router->post('/compras/abonar', 'ComprasController@abonar');

$router->get('/proveedores', 'ProveedoresController@index');
$router->get('/proveedores/crear', 'ProveedoresController@crear');
$router->post('/proveedores/guardar', 'ProveedoresController@guardar');
$router->get('/proveedores/editar/{id}', 'ProveedoresController@editar');
$router->post('/proveedores/actualizar/{id}', 'ProveedoresController@actualizar');
$router->post('/proveedores/eliminar/{id}', 'ProveedoresController@eliminar');

$router->get('/clientes', 'ClientesController@index');
$router->post('/clientes/guardar', 'ClientesController@guardar');
$router->post('/clientes/guardar-ajax', 'ClientesController@guardarAjax');
$router->get('/clientes/editar/{id}', 'ClientesController@editar');
$router->post('/clientes/actualizar/{id}', 'ClientesController@actualizar');
$router->get('/clientes/estado-cuenta/{id}', 'ClientesController@estadoCuenta');
$router->post('/clientes/abonar', 'ClientesController@abonar');
$router->get('/clientes/abono/ticket/{id}', 'ClientesController@imprimirAbono');
$router->get('/clientes/buscar', 'ClientesController@buscar');
$router->get('/configuracion/usuarios/editar/{id}', 'ConfiguracionController@editarUsuario');
$router->post('/configuracion/usuarios/actualizar/{id}', 'ConfiguracionController@actualizarUsuario');

$router->get('/caja', 'CajaController@index');
$router->post('/caja/abrir', 'CajaController@abrir');
$router->post('/caja/movimiento', 'CajaController@movimiento');
$router->get('/caja/corte', 'CajaController@corte');
$router->post('/caja/cerrar', 'CajaController@cerrar');
$router->get('/caja/ticket/{id}', 'CajaController@imprimirCorte');

$router->get('/configuracion', 'ConfiguracionController@index');
$router->post('/configuracion/guardar-empresa', 'ConfiguracionController@guardarEmpresa');
$router->get('/configuracion/usuarios', 'ConfiguracionController@index');
$router->post('/configuracion/usuarios/guardar', 'ConfiguracionController@guardarUsuario');

$router->get('/usuarios', 'UsuariosController@index');
$router->get('/usuarios/editar/{id}', 'UsuariosController@editar');
$router->post('/usuarios/guardar', 'UsuariosController@guardar');
$router->post('/usuarios/actualizar/{id}', 'UsuariosController@actualizar');

$router->get('/reportes', 'ReportesController@index');
$router->get('/reportes/filtrar', 'ReportesController@index');
$router->get('/reportes/imprimir', 'ReportesController@imprimir');
$router->get('/reportes/exportar', 'ReportesController@exportar');

$router->get('/soporte', 'SoporteController@index');

$router->get('/comprobantes', 'ComprobantesController@index');
$router->get('/comprobantes/imprimir/{id}', 'ComprobantesController@imprimir');

$router->post('/impresora/imprimir-venta/{id}', 'ImpresoraController@imprimirVenta');
$router->post('/impresora/probar', 'ImpresoraController@probar');
$router->post('/impresora/escaneo-lan', 'ImpresoraController@escaneoLan');

$router->procesar();
