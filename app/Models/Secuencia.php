<?php

require_once CORE_PATH . 'Controller.php';

class Secuencia extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    /**
     * Devuelve el siguiente correlativo de una secuencia.
     * DEBE llamarse dentro de una transacción activa sobre la misma
     * conexión (Database singleton): el SELECT ... FOR UPDATE bloquea
     * la fila y evita folios duplicados por concurrencia.
     */
    public function siguiente($nombre, $prefijo, $longitud = 8)
    {
        $nombre = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$nombre);
        if ($nombre === '') {
            throw new Exception('Nombre de secuencia inválido.');
        }
        $this->pdo->prepare('INSERT INTO secuencias (nombre, valor) VALUES (:n, 0) ON DUPLICATE KEY UPDATE nombre = nombre')
            ->execute([':n' => $nombre]);
        $stmt = $this->pdo->prepare('SELECT valor FROM secuencias WHERE nombre = :n FOR UPDATE');
        $stmt->execute([':n' => $nombre]);
        $siguiente = (int)$stmt->fetchColumn() + 1;
        $this->pdo->prepare('UPDATE secuencias SET valor = :v WHERE nombre = :n')
            ->execute([':v' => $siguiente, ':n' => $nombre]);
        return $prefijo . str_pad((string)$siguiente, (int)$longitud, '0', STR_PAD_LEFT);
    }
}
