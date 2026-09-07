<?php

require_once CORE_PATH . 'Controller.php';

class HomeController extends Controller
{
    public function index()
    {
        if ($this->estaAutenticado()) {
            $this->redirigir('ventas');
        } else {
            $this->redirigir('login');
        }
    }
}
