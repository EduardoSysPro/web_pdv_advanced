<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Proveedor.php';

class ProveedoresController extends Controller
{
    private $modelo;

    public function __construct()
    {
        parent::__construct();
        $this->modelo = new Proveedor();
    }

    public function index()
    {
        $this->requerirAdministrador();
        $busqueda = trim($_GET['busqueda'] ?? '');
        $proveedores = $this->modelo->obtenerTodos($busqueda);
        $mensaje = $_SESSION['mensaje_proveedores'] ?? null;
        unset($_SESSION['mensaje_proveedores']);
        $tituloPagina = 'Proveedores';
        require APP_PATH . 'Views/proveedores/index.php';
    }

    public function crear()
    {
        $this->requerirAdministrador();
        $proveedor = null;
        $tituloPagina = 'Nuevo proveedor';
        require APP_PATH . 'Views/proveedores/formulario.php';
    }

    public function guardar()
    {
        $this->requerirAdministrador();
        $datos = $this->leerDatos();
        if ($datos['nombre'] === '') {
            $_SESSION['mensaje_proveedores'] = 'El nombre del proveedor es obligatorio.';
            $this->redirigir('proveedores/crear');
        }
        if ($datos['rtn'] !== '' && $this->modelo->rtnExiste($datos['rtn'])) {
            $_SESSION['mensaje_proveedores'] = 'Ya existe un proveedor con ese RTN.';
            $this->redirigir('proveedores/crear');
        }
        try {
            $this->modelo->insertar($datos);
            $_SESSION['mensaje_proveedores'] = 'Proveedor creado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['mensaje_proveedores'] = 'No se pudo guardar el proveedor: ' . $e->getMessage();
        }
        $this->redirigir('proveedores');
    }

    public function editar($parametros)
    {
        $this->requerirAdministrador();
        $proveedor = $this->modelo->obtenerPorId((int)($parametros['id'] ?? 0));
        if (!$proveedor) $this->redirigir('proveedores');
        $tituloPagina = 'Editar proveedor';
        require APP_PATH . 'Views/proveedores/formulario.php';
    }

    public function actualizar($parametros)
    {
        $this->requerirAdministrador();
        $id = (int)($parametros['id'] ?? 0);
        $datos = $this->leerDatos();
        if ($datos['nombre'] === '') {
            $_SESSION['mensaje_proveedores'] = 'El nombre del proveedor es obligatorio.';
            $this->redirigir('proveedores/editar/' . $id);
        }
        if ($datos['rtn'] !== '' && $this->modelo->rtnExiste($datos['rtn'], $id)) {
            $_SESSION['mensaje_proveedores'] = 'Ya existe otro proveedor con ese RTN.';
            $this->redirigir('proveedores/editar/' . $id);
        }
        try {
            $this->modelo->actualizar($id, $datos);
            $_SESSION['mensaje_proveedores'] = 'Proveedor actualizado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['mensaje_proveedores'] = 'No se pudo actualizar el proveedor: ' . $e->getMessage();
        }
        $this->redirigir('proveedores');
    }

    public function eliminar($parametros)
    {
        $this->requerirAdministrador();
        $id = (int)($parametros['id'] ?? 0);
        if ($this->modelo->existeId($id)) {
            $this->modelo->eliminar($id);
            $_SESSION['mensaje_proveedores'] = 'Proveedor eliminado.';
        } else {
            $_SESSION['mensaje_proveedores'] = 'El proveedor no existe.';
        }
        $this->redirigir('proveedores');
    }

    private function leerDatos()
    {
        return [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'rtn' => trim($_POST['rtn'] ?? ''),
            'tipo' => ($_POST['tipo'] ?? 'contribuyente') === 'no_contribuyente' ? 'no_contribuyente' : 'contribuyente',
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'contacto' => trim($_POST['contacto'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
        ];
    }
}