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
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

$router = new Router();

$router->get('/',      'HomeController@index');
$router->get('/login', 'AuthController@login');
$router->post('/autenticar', 'AuthController@autenticar');
$router->get('/logout', 'AuthController@logout');

$router->get('/movil', 'VentasController@movil');
$router->get('/ventas', 'VentasController@index');
$router->post('/ventas/buscar-producto', 'VentasController@buscarProducto');
$router->get('/ventas/buscar-producto',  'VentasController@buscarProducto');
$router->get('/ventas/buscar-productos', 'VentasController@buscarProductosAjax');
$router->post('/ventas/buscar-productos', 'VentasController@buscarProductosAjax');
$router->get('/ventas/buscarClientePorRtn', 'VentasController@buscarClientePorRtn');
$router->post('/ventas/buscarClientePorRtn', 'VentasController@buscarClientePorRtn');
$router->get('/ventas/buscar-cliente-por-rtn', 'VentasController@buscarClientePorRtn');
$router->post('/ventas/buscar-cliente-por-rtn', 'VentasController@buscarClientePorRtn');
$router->post('/ventas/guardar', 'VentasController@guardarVenta');
$router->get('/ventas/ticket/{id}', 'VentasController@imprimirTicket');

$router->get('/productos', 'ProductosController@index');
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

$router->get('/comprobantes', 'ComprobantesController@index');
$router->get('/comprobantes/imprimir/{id}', 'ComprobantesController@imprimir');

$router->procesar();
