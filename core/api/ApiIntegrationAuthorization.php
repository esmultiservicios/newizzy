<?php
/**
 * Autorización central para futuras integraciones servidor a servidor.
 * No controla el inicio de sesión de usuarios en la APK.
 * Las empresas quedan DENEGADAS por defecto hasta autorización manual de IZZY.
 */
declare(strict_types=1);

final class IzzyApiIntegrationAuthorization
{
    private mysqli $db;

    public function __construct()
    {
        $peticionAjax = true;
        require_once dirname(__DIR__) . '/configGenerales.php';
        require_once dirname(__DIR__) . '/mainModel.php';
        $this->db = (new mainModel())->connectionDBLocal(DB_MAIN);
    }

    /**
     * Retorna exclusivamente el tenant autorizado; jamás acepta una DB del consumidor.
     * Debe invocarse en la emisión y uso de credenciales de integraciones.
     */
    public function exigirAutorizacion(int $serverCustomersId): array
    {
        if ($serverCustomersId <= 0) throw new DomainException('Integración no autorizada.');
        $stmt = $this->db->prepare(
            'SELECT s.server_customers_id, s.db FROM server_customers s '
            . 'INNER JOIN api_empresa_autorizacion a ON a.server_customers_id = s.server_customers_id '
            . 'WHERE s.server_customers_id = ? AND s.estado = 1 AND a.habilitada = 1 LIMIT 1'
        );
        if (!$stmt) throw new RuntimeException('No se pudo validar la autorización de API.');
        $stmt->bind_param('i', $serverCustomersId);
        $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$row || !preg_match('/^[a-zA-Z0-9_]+$/D', (string)$row['db'])) {
            throw new DomainException('Integración no autorizada.');
        }
        return ['server_customers_id' => (int)$row['server_customers_id'], 'db_cliente' => (string)$row['db']];
    }

    public function __destruct()
    {
        if (isset($this->db)) $this->db->close();
    }
}
