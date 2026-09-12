<?php

require_once CORE_PATH . 'Controller.php';
require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Configuracion.php';

class ConfiguracionController extends Controller
{
    private $modeloConfiguracion;

    public function __construct()
    {
        parent::__construct();
        $this->modeloConfiguracion = new Configuracion();
    }

    public function index()
    {
        $this->requerirAdministrador();
        $configuracion = $this->modeloConfiguracion->obtenerTodas();
        $mensaje = $_SESSION['mensaje_configuracion'] ?? null;
        $error = $_SESSION['error_configuracion'] ?? null;
        unset($_SESSION['mensaje_configuracion'], $_SESSION['error_configuracion']);
        require APP_PATH . 'Views/configuracion/index.php';
    }

    public function guardarEmpresa()
    {
        $this->requerirAdministrador();
        
        $camposPermitidos = [
            'nombre_negocio', 'rtn', 'telefono', 'email', 'direccion',
            'mensaje_ticket', 'ancho_ticket', 'tipo_comprobante_default', 'impuesto_porcentaje', 'moneda_simbolo',
            'ticket_fuente', 'ticket_tamano_fuente', 'ticket_mostrar_logo', 'ticket_mostrar_sar',
            'impresora_lan_activa', 'impresora_lan_ip', 'impresora_lan_puerto',
            'sar_cai', 'sar_rango_inicial', 'sar_rango_final', 'sar_fecha_limite', 'sar_correlativo_actual',
            'sar_punto_venta', 'sar_establecimiento', 'sar_tipo_documento'
        ];

        $datos = [];
        foreach ($camposPermitidos as $campo) {
            $datos[$campo] = trim($_POST[$campo] ?? '');
        }

        // Estado del switch SAR
        $datos['sar_activo'] = isset($_POST['sar_activo']) ? '1' : '0';
        $datos['ticket_mostrar_logo'] = isset($_POST['ticket_mostrar_logo']) ? '1' : '0';
        $datos['ticket_mostrar_sar'] = isset($_POST['ticket_mostrar_sar']) ? '1' : '0';
        $datos['impresora_lan_activa'] = isset($_POST['impresora_lan_activa']) ? '1' : '0';
        $datos['impresora_lan_puerto'] = max(1, min(65535, (int)($datos['impresora_lan_puerto'] ?: 9100)));
        $datos['tipo_comprobante_default'] = in_array(($datos['tipo_comprobante_default'] ?? 'recibo'), ['recibo', 'factura'], true)
            ? $datos['tipo_comprobante_default'] : 'recibo';
        $datos['ticket_fuente'] = $datos['ticket_fuente'] ?: 'Courier New';
        $datos['ticket_tamano_fuente'] = $datos['ticket_tamano_fuente'] ?: '11px';

        if ($datos['nombre_negocio'] === '' || !in_array($datos['ancho_ticket'], ['58mm','80mm'], true) || !is_numeric($datos['impuesto_porcentaje'])) {
            $_SESSION['error_configuracion'] = 'Revisa los datos obligatorios del negocio.';
            $this->redirigir('configuracion');
        }

        $datos['moneda_simbolo'] = $datos['moneda_simbolo'] ?: 'L';

        // Procesar logotipo
        if (!empty($_FILES['logotipo']['tmp_name']) && $_FILES['logotipo']['error'] === UPLOAD_ERR_OK) {
            $tipo = @getimagesize($_FILES['logotipo']['tmp_name']);
            if (!$tipo || !in_array($tipo['mime'], ['image/png','image/jpeg','image/webp'], true)) {
                $_SESSION['error_configuracion'] = 'El logotipo debe ser PNG, JPG o WEBP.';
                $this->redirigir('configuracion');
            }
            $directorio = PUBLIC_PATH . 'uploads' . DIRECTORY_SEPARATOR;
            if (!is_dir($directorio)) mkdir($directorio, 0755, true);
            $archivo = $directorio . 'logo.png';
            if (!move_uploaded_file($_FILES['logotipo']['tmp_name'], $archivo)) {
                $_SESSION['error_configuracion'] = 'No se pudo guardar el logotipo.';
                $this->redirigir('configuracion');
            }
            $datos['logotipo_path'] = 'uploads/logo.png';
        }

        $this->modeloConfiguracion->guardar($datos);
        $_SESSION['mensaje_configuracion'] = 'Configuración actualizada correctamente.';
        $this->redirigir('configuracion');
    }

    public function editarUsuario($parametros)
    {
        $this->requerirAdministrador();
        $usuarioId = (int)($parametros['id'] ?? 0);
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Usuario.php';
        $modeloUsuario = new Usuario();
        $usuario = $modeloUsuario->obtenerPorId($usuarioId);
        if (!$usuario) {
            $_SESSION['error_configuracion'] = 'Usuario no encontrado.';
            $this->redirigir('configuracion');
        }
        $cajas = $modeloUsuario->obtenerCajas();
        require APP_PATH . 'Views/configuracion/editar_usuario.php';
    }

    public function actualizarUsuario($parametros)
    {
        $this->requerirAdministrador();
        $usuarioId = (int)($parametros['id'] ?? 0);
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Usuario.php';
        $modeloUsuario = new Usuario();
        
        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'rol' => $_POST['rol'] ?? 'cajero',
            'estado' => (int)($_POST['estado'] ?? 1),
            'sucursal' => trim($_POST['sucursal'] ?? ''),
            'caja_id' => (int)($_POST['caja_id'] ?? 0)
        ];

        if ($datos['nombre'] === '' || $datos['usuario'] === '') {
            $_SESSION['error_configuracion'] = 'El nombre y usuario son obligatorios.';
            $this->redirigir('configuracion');
        }

        $modeloUsuario->actualizar($usuarioId, $datos);
        $_SESSION['mensaje_configuracion'] = 'Usuario actualizado correctamente.';
        $this->redirigir('configuracion');
    }

    public function guardarUsuario()
    {
        $this->requerirAdministrador();
        require_once APP_PATH . 'Models' . DIRECTORY_SEPARATOR . 'Usuario.php';
        $modeloUsuario = new Usuario();

        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'rol' => $_POST['rol'] ?? 'cajero',
            'estado' => (int)($_POST['estado'] ?? 1),
            'sucursal' => trim($_POST['sucursal'] ?? ''),
            'caja_id' => (int)($_POST['caja_id'] ?? 0)
        ];

        if ($datos['nombre'] === '' || $datos['usuario'] === '' || strlen($datos['password']) < 6) {
            $_SESSION['error_configuracion'] = 'Completa los datos y usa una contraseña de al menos 6 caracteres.';
            $this->redirigir('configuracion');
        }

        try {
            $modeloUsuario->insertar($datos);
            $_SESSION['mensaje_configuracion'] = 'Usuario creado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['error_configuracion'] = 'No se pudo crear el usuario. Verifica que el nombre de usuario no esté repetido.';
        }

        $this->redirigir('configuracion');
    }
}