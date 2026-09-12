<?php

require_once CORE_PATH . 'Controller.php';

class SoporteController extends Controller
{
    public function index()
    {
        $this->requerirAdministrador();
        require APP_PATH . 'Views/soporte/index.php';
    }
}