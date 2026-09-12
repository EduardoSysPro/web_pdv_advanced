<?php

require_once CORE_PATH . 'Controller.php';

class LoginIntento extends Controller
{
    private const LIMITE_INTENTOS = 5;
    private const VENTANA_MINUTOS = 15;

    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
        $this->crearTabla();
    }

    private function crearTabla()
    {
        $sql = "CREATE TABLE IF NOT EXISTS login_intentos (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            usuario      VARCHAR(100) NOT NULL DEFAULT '',
            ip           VARCHAR(45)  NOT NULL DEFAULT '',
            intentado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resultado    VARCHAR(10)  NOT NULL DEFAULT 'fallo',
            KEY idx_login_intentos_busqueda (usuario, ip, intentado_en),
            KEY idx_login_intentos_limpieza (intentado_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }

    public function registrar($usuario, $ip)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_intentos (usuario, ip, resultado) VALUES (:usuario, :ip, :resultado)'
        );
        $stmt->execute([
            ':usuario' => mb_substr((string)$usuario, 0, 100),
            ':ip' => mb_substr((string)$ip, 0, 45),
            ':resultado' => 'fallo'
        ]);
    }

    public function minutosBloqueado($usuario, $ip)
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total, MIN(intentado_en) AS inicio
             FROM login_intentos
             WHERE usuario = :usuario AND ip = :ip AND resultado = :resultado
               AND intentado_en >= NOW() - INTERVAL ' . (int)self::VENTANA_MINUTOS . ' MINUTE'
        );
        $stmt->execute([
            ':usuario' => mb_substr((string)$usuario, 0, 100),
            ':ip' => mb_substr((string)$ip, 0, 45),
            ':resultado' => 'fallo'
        ]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int)$fila['total'] >= self::LIMITE_INTENTOS && !empty($fila['inicio'])) {
            $transcurridos = (int)floor((time() - strtotime($fila['inicio'])) / 60);
            return max(1, self::VENTANA_MINUTOS - $transcurridos);
        }
        return 0;
    }

    public function limpiar($usuario, $ip)
    {
        $stmt = $this->pdo->prepare('DELETE FROM login_intentos WHERE usuario = :usuario AND ip = :ip');
        $stmt->execute([
            ':usuario' => mb_substr((string)$usuario, 0, 100),
            ':ip' => mb_substr((string)$ip, 0, 45)
        ]);
    }
}