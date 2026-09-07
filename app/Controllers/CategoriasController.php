<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Categoria.php';

class CategoriasController extends Controller
{
    private $modelo;

    public function __construct()
    {
        parent::__construct();
        $this->modelo = new Categoria();
    }

    public function index()
    {
        $this->requerirAdministrador();
        $categorias = $this->modelo->obtenerTodasConConteo();
        $mensaje = $_SESSION['mensaje_categorias'] ?? null;
        $error = $_SESSION['error_categorias'] ?? null;
        unset($_SESSION['mensaje_categorias'], $_SESSION['error_categorias']);
        require APP_PATH . 'Views/categorias/index.php';
    }

    public function crear()
    {
        $this->requerirAdministrador();
        $categoria = ['nombre' => '', 'descripcion' => ''];
        $errores = $_SESSION['errores_categorias'] ?? [];
        unset($_SESSION['errores_categorias']);
        $titulo = 'Nueva categoría';
        $accion = URL_BASE . 'categorias/guardar';
        require APP_PATH . 'Views/categorias/formulario.php';
    }

    public function guardar()
    {
        $this->requerirAdministrador();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('categorias');
        }

        $datos = $this->leerDatos();
        $errores = $this->validarDatos($datos);

        if ($this->modelo->existeNombre($datos['nombre'])) {
            $errores[] = 'Ya existe una categoría con ese nombre.';
        }

        if ($errores) {
            $_SESSION['errores_categorias'] = $errores;
            $_SESSION['datos_categoria'] = $datos;
            $this->redirigir('categorias/crear');
        }

        $this->modelo->insertar($datos);
        $_SESSION['mensaje_categorias'] = 'Categoría creada correctamente.';
        $this->redirigir('categorias');
    }

    public function editar($parametros)
    {
        $this->requerirAdministrador();
        $id = (int)($parametros['id'] ?? 0);
        $categoria = $this->modelo->obtenerPorId($id);

        if (!$categoria) {
            $_SESSION['error_categorias'] = 'La categoría no existe.';
            $this->redirigir('categorias');
        }

        $errores = $_SESSION['errores_categorias'] ?? [];
        unset($_SESSION['errores_categorias']);
        $titulo = 'Editar categoría';
        $accion = URL_BASE . 'categorias/actualizar/' . $id;
        require APP_PATH . 'Views/categorias/formulario.php';
    }

    public function actualizar($parametros)
    {
        $this->requerirAdministrador();
        $id = (int)($parametros['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('categorias/editar/' . $id);
        }

        $datos = $this->leerDatos();
        $errores = $this->validarDatos($datos);

        if ($this->modelo->existeNombre($datos['nombre'], $id)) {
            $errores[] = 'Ya existe otra categoría con ese nombre.';
        }

        if ($errores) {
            $_SESSION['errores_categorias'] = $errores;
            $this->redirigir('categorias/editar/' . $id);
        }

        $this->modelo->actualizar($id, $datos);
        $_SESSION['mensaje_categorias'] = 'Categoría actualizada correctamente.';
        $this->redirigir('categorias');
    }

    public function eliminar($parametros)
    {
        $this->requerirAdministrador();
        $id = (int)($parametros['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
            $productosAsignados = $this->modelo->contarProductos($id);
            if ($productosAsignados > 0) {
                $_SESSION['error_categorias'] = 'No se puede eliminar la categoría porque tiene productos asociados.';
                $this->redirigir('categorias');
            }

            $this->modelo->eliminar($id);
            $_SESSION['mensaje_categorias'] = 'Categoría eliminada correctamente.';
        }

        $this->redirigir('categorias');
    }

    private function leerDatos()
    {
        return [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
        ];
    }

    private function validarDatos($datos)
    {
        $errores = [];
        if (mb_strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre de la categoría es obligatorio.';
        }

        if (mb_strlen($datos['nombre']) > 100) {
            $errores[] = 'El nombre de la categoría no puede exceder 100 caracteres.';
        }

        if (mb_strlen($datos['descripcion']) > 255) {
            $errores[] = 'La descripción no puede exceder 255 caracteres.';
        }

        return $errores;
    }
}
