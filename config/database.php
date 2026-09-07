<?php

class Database
{
    private static $instancia = null;
    private $pdo;

    private function __construct()
    {
        $host = 'localhost';
        $dbname = 'web_pdv_db';
        $usuario = 'root';
        $contrasena = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $usuario, $contrasena, $opciones);
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    public static function getInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function getConexion()
    {
        return $this->pdo;
    }

    private function __clone() {}
    private function __wakeup() {}
}
