<?php

class Router
{
    private $rutasGet = [];
    private $rutasPost = [];

    public function get($ruta, $controladorMetodo)
    {
        $this->rutasGet[$ruta] = $controladorMetodo;
    }

    public function post($ruta, $controladorMetodo)
    {
        $this->rutasPost[$ruta] = $controladorMetodo;
    }

    public function procesar()
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '/';

        if ($url === '') {
            $url = '/';
        }

        if ($metodo === 'POST' && !$this->verificarCsrfPeticion()) {
            http_response_code(403);
            die('Solicitud inválida o sesión expirada. Recarga la página e intenta de nuevo.');
        }

        $rutas = $metodo === 'GET' ? $this->rutasGet : $this->rutasPost;

        foreach ($rutas as $ruta => $controladorMetodo) {
            $parametros = $this->coincidirRuta($ruta, $url);
            if ($parametros !== false) {
                $this->invocarControlador($controladorMetodo, $parametros);
                return;
            }
        }

        $this->mostrarError404();
    }

    private function verificarCsrfPeticion()
    {
        $token = $_POST['csrf_token'] ?? '';
        if ($token === '') {
            $contenido = file_get_contents('php://input');
            $datos = json_decode((string)$contenido, true);
            $token = is_array($datos) ? ($datos['csrf_token'] ?? '') : '';
        }
        return csrf_verificar($token);
    }

    private function coincidirRuta($ruta, $url)
    {
        $partesRuta = explode('/', trim($ruta, '/'));
        $partesUrl = explode('/', trim($url, '/'));

        if (count($partesRuta) !== count($partesUrl)) {
            return false;
        }

        $parametros = [];

        for ($i = 0; $i < count($partesRuta); $i++) {
            if (strpos($partesRuta[$i], '{') === 0 && strpos($partesRuta[$i], '}') === strlen($partesRuta[$i]) - 1) {
                $nombreParametro = trim($partesRuta[$i], '{}');
                $parametros[$nombreParametro] = $partesUrl[$i];
            } else {
                if ($partesRuta[$i] !== $partesUrl[$i]) {
                    return false;
                }
            }
        }

        return $parametros;
    }

    private function invocarControlador($controladorMetodo, $parametros)
    {
        list($nombreControlador, $nombreMetodo) = explode('@', $controladorMetodo);

        $archivoControlador = APP_PATH . 'Controllers' . DIRECTORY_SEPARATOR . $nombreControlador . '.php';

        if (!file_exists($archivoControlador)) {
            $this->mostrarError404();
            return;
        }

        require_once $archivoControlador;

        if (!class_exists($nombreControlador)) {
            $this->mostrarError404();
            return;
        }

        $controlador = new $nombreControlador();

        if (!method_exists($controlador, $nombreMetodo)) {
            $this->mostrarError404();
            return;
        }

        call_user_func_array([$controlador, $nombreMetodo], [$parametros]);
    }

    private function mostrarError404()
    {
        http_response_code(404);
        $rutaVista = APP_PATH . 'Views' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.php';

        if (file_exists($rutaVista)) {
            require $rutaVista;
            return;
        }

        echo 'Página no encontrada (404)';
    }
}
