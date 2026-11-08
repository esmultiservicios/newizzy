<?php
/** Emisión de tokens OAuth client_credentials para integraciones de servidor. */
declare(strict_types=1);

final class IzzyApiClientAuthService
{
    private mysqli $db;

    public function __construct()
    {
        $peticionAjax = true;
        require_once dirname(__DIR__) . '/configGenerales.php';
        require_once dirname(__DIR__) . '/mainModel.php';
        $this->db = (new mainModel())->connectionDBLocal(DB_MAIN);
    }

    public function emitirToken(string $clientId, string $secret): array
    {
        // Fail closed hasta que Fase 2 vincule cada credencial a un tenant
        // autorizado manualmente en DB_MAIN. La versión anterior de esta clase
        // no vinculaba api_integrations a server_customers y no debe exponerse.
        throw new DomainException('Las integraciones externas todavía no están habilitadas.');
    }

    public function __destruct()
    {
        if (isset($this->db) && $this->db instanceof mysqli) $this->db->close();
    }
}
