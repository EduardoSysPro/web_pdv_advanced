<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Usuario.php';

class UsuariosController extends Controller
{
    private $modelo;

    public function __construct()
    {
        parent::__construct();
        $this->modelo = new Usuario();
    }

    public function index()
    {
        $this->requerirAdministrador();
        $usuarios = $this->modelo->obtenerTodos();
        $cajas = $this->modelo->obtenerCajas();
        $mensaje = $_SESSION['mensaje_usuarios'] ?? null;
        $error = $_SESSION['error_usuarios'] ?? null;
        unset($_SESSION['mensaje_usuarios'], $_SESSION['error_usuarios']);
        require APP_PATH . 'Views/usuarios/index.php';
    }

    public function guardar()
    {
        $this->requerirAdministrador();
        $datos = $this->leerUsuario();
        if ($datos['nombre'] === '' || $datos['usuario'] === '' || strlen($datos['password']) < 6 || !in_array($datos['rol'], ['admin', 'cajero', 'cajero_movil', 'vendedor'], true)) {
            $_SESSION['error_usuarios'] = 'Completa los datos y usa una contraseña de al menos 6 caracteres.';
            $this->redirigir('usuarios');
        }
        try {
            $this->modelo->insertar($datos);
            $_SESSION['mensaje_usuarios'] = 'Usuario creado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['error_usuarios'] = 'No se pudo crear el usuario. Verifica que el nombre de usuario no esté repetido.';
        }
        $this->redirigir('usuarios');
    }

    public function editar($parametros)
    {
        $this->requerirAdministrador();
        $usuarioId = (int)($parametros['id'] ?? 0);
        $usuario = $this->modelo->obtenerPorId($usuarioId);

        if (!$usuario) {
            $_SESSION['error_usuarios'] = 'Usuario no encontrado.';
            $this->redirigir('usuarios');
        }

        $cajas = $this->modelo->obtenerCajas();
        require APP_PATH . 'Views/usuarios/editar.php';
    }

    public function actualizar($parametros)
    {
        $this->requerirAdministrador();
        $usuarioId = (int)($parametros['id'] ?? 0);
        $datos = $this->leerUsuario();

        if ($usuarioId <= 0 || $datos['nombre'] === '' || $datos['usuario'] === '' || !in_array($datos['rol'], ['admin', 'cajero', 'cajero_movil', 'vendedor'], true)) {
            $_SESSION['error_usuarios'] = 'No se pudo actualizar el usuario. Revisa los datos.';
            $this->redirigir('usuarios');
        }

        if (isset($_POST['password']) && trim((string)$_POST['password']) !== '') {
            $datos['password'] = trim((string)$_POST['password']);
        } else {
            unset($datos['password']);
        }

        try {
            $this->modelo->actualizar($usuarioId, $datos);
            $_SESSION['mensaje_usuarios'] = 'Usuario actualizado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['error_usuarios'] = 'No se pudo actualizar el usuario. Verifica que el nombre de usuario no esté repetido.';
        }

        $this->redirigir('usuarios');
    }

    private function leerUsuario()
    {
        return [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'usuario' => trim($_POST['username'] ?? ($_POST['usuario'] ?? '')),
            'password' => $_POST['password'] ?? '',
            'rol' => $_POST['rol'] ?? 'cajero',
            'estado' => (int)($_POST['estado'] ?? 1),
            'sucursal' => trim($_POST['sucursal'] ?? ''),
            'caja' => trim($_POST['caja'] ?? ''),
            'caja_id' => (int)($_POST['caja_id'] ?? 0)
        ];
    }
}