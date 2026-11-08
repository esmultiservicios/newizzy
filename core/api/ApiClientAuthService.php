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
        if (!preg_match('/^izzyc_[a-f0-9]{24}$/D', $clientId) || !preg_match('/^izzys_[a-f0-9]{64}$/D', $secret)) {
            throw new DomainException('Credenciales inválidas.');
        }
        $hash = hash('sha256', $secret);
        $stmt = $this->db->prepare('SELECT id, scopes FROM api_integrations WHERE client_id=? AND secret_hash=? AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at>NOW()) LIMIT 1');
        if (!$stmt) throw new RuntimeException('No se pudo validar la integración.');
        $stmt->bind_param('ss', $clientId, $hash); $stmt->execute(); $integration = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$integration) throw new DomainException('Credenciales inválidas o revocadas.');
        $token = 'izzya_' . bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $integrationId = (int)$integration['id'];
        $scopes = (string)$integration['scopes'];
        $stmt = $this->db->prepare('INSERT INTO api_integration_tokens (token_hash, integration_id, scopes, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))');
        if (!$stmt) throw new RuntimeException('No se pudo emitir el token.');
        $stmt->bind_param('sis', $tokenHash, $integrationId, $scopes); $stmt->execute(); $stmt->close();
        return ['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => 3600, 'scope' => $scopes];
    }

    public function __destruct()
    {
        if (isset($this->db) && $this->db instanceof mysqli) $this->db->close();
    }
}
