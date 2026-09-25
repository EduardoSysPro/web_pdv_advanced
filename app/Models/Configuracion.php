<?php

require_once CORE_PATH . 'Controller.php';

class Configuracion extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getInstancia()->getConexion();
    }

    private static $cacheMapa = null;

    public function obtenerMapa(): array
    {
        if (self::$cacheMapa !== null) {
            return self::$cacheMapa;
        }
        $predeterminados = $this->predeterminados();
        try {
            $stmt = $this->pdo->query('SELECT clave, valor FROM configuracion');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $predeterminados[$fila['clave']] = $fila['valor'];
            }
        } catch (PDOException $e) {
            return $predeterminados;
        }
        self::$cacheMapa = $predeterminados;
        return $predeterminados;
    }

    public function obtenerTodas()
    {
        return $this->obtenerMapa();
    }

    public function guardar($datos)
    {
        $stmt = $this->pdo->prepare('INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor) ON DUPLICATE KEY UPDATE valor=VALUES(valor)');
        // Puede llamarse dentro de una transacción ajena (ej. venta con factura
        // fiscal): solo abre transacción propia si no hay una activa.
        $externa = $this->pdo->inTransaction();
        if (!$externa) {
            $this->pdo->beginTransaction();
        }
        try {
            foreach ($datos as $clave => $valor) {
                $stmt->execute([':clave' => $clave, ':valor' => $valor]);
            }
            if (!$externa) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if (!$externa && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        self::$cacheMapa = null;
    }

    private function predeterminados()
    {
        return [
            // Parámetros generales
            'nombre_negocio'        => 'Mi Abarrotería',
            'rtn'                   => '',
            'telefono'              => '',
            'email'                 => '',
            'direccion'             => '',
            'mensaje_ticket'        => '¡Gracias por su compra!',
            'ancho_ticket'          => '80mm',
            'tipo_comprobante_default' => 'recibo',
            'impuesto_porcentaje'   => '15',
            'moneda_simbolo'        => 'L',
            'logotipo_path'         => '',
            'ticket_fuente'         => 'Courier New',
            'ticket_tamano_fuente'  => '11px',
            'ticket_mostrar_logo'   => '1',
            'ticket_mostrar_sar'    => '1',

            // Impresora de red (LAN)
            'impresora_lan_activa'  => '0',
            'impresora_lan_ip'      => '',
            'impresora_lan_puerto'  => '9100',

            // Parámetros opcionales del SAR Honduras
            'sar_activo'            => '0',
            'sar_cai'               => '',
            'sar_rango_inicial'     => '',
            'sar_rango_final'       => '',
            'sar_fecha_limite'      => '',
            'sar_correlativo_actual' => '0',
            'sar_punto_venta'       => '001',
            'sar_establecimiento'   => '001',
            'sar_tipo_documento'    => '01'
        ];
    }
}