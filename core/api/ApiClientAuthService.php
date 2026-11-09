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
        if (!preg_match('/^izzy_cli_[a-f0-9]{24}$/D',$clientId) || !preg_match('/^izzy_sec_[A-Za-z0-9_-]{43}$/D',$secret)) throw new DomainException('Credenciales inválidas.');
        $hash=hash('sha256',$secret);
        $stmt=$this->db->prepare('SELECT i.id,i.scopes,i.server_customers_id,s.db FROM api_integrations i INNER JOIN api_empresa_autorizacion a ON a.server_customers_id=i.server_customers_id AND a.habilitada=1 INNER JOIN server_customers s ON s.server_customers_id=i.server_customers_id AND s.estado=1 AND s.sistema_id=1 WHERE i.client_id=? AND i.secret_hash=? AND i.revoked_at IS NULL AND (i.expires_at IS NULL OR i.expires_at>NOW()) LIMIT 1');
        if (!$stmt) throw new RuntimeException('No se pudo comprobar la integración.');
        $stmt->bind_param('ss',$clientId,$hash); $stmt->execute(); $integration=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$integration || !preg_match('/^[a-zA-Z0-9_]+$/D',(string)$integration['db']) || trim((string)$integration['scopes'])==='') throw new DomainException('Integración no autorizada.');
        $token='izzya_'.bin2hex(random_bytes(32));
        $tokenHash=hash('sha256',$token);
        $id=(int)$integration['id'];$scopes=(string)$integration['scopes'];
        $stmt=$this->db->prepare('INSERT INTO api_integration_tokens (token_hash,integration_id,scopes,expires_at) VALUES (?,?,?,DATE_ADD(NOW(),INTERVAL 60 MINUTE))');
        $stmt->bind_param('sis',$tokenHash,$id,$scopes); $stmt->execute(); $stmt->close();
        return ['access_token'=>$token,'token_type'=>'Bearer','expires_in'=>3600,'scope'=>$scopes];
    }

    public function autenticarToken(string $token,string $scope): array
    {
        if (!preg_match('/^izzya_[a-f0-9]{64}$/D',$token)) throw new DomainException('Token inválido.');
        $hash=hash('sha256',$token);
        $stmt=$this->db->prepare('SELECT i.server_customers_id,s.db,i.scopes FROM api_integration_tokens t INNER JOIN api_integrations i ON i.id=t.integration_id INNER JOIN api_empresa_autorizacion a ON a.server_customers_id=i.server_customers_id AND a.habilitada=1 INNER JOIN server_customers s ON s.server_customers_id=i.server_customers_id AND s.estado=1 AND s.sistema_id=1 WHERE t.token_hash=? AND t.revoked_at IS NULL AND t.expires_at>NOW() AND i.revoked_at IS NULL AND (i.expires_at IS NULL OR i.expires_at>NOW()) LIMIT 1');
        $stmt->bind_param('s',$hash);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
        if (!$row || !preg_match('/^[a-zA-Z0-9_]+$/D',(string)$row['db'])) throw new DomainException('Token no autorizado.');
        if ($scope!=='' && !in_array($scope,preg_split('/\s+/',trim((string)$row['scopes'])),true)) throw new DomainException('Alcance no autorizado.');
        return ['server_customers_id'=>(int)$row['server_customers_id'],'db_cliente'=>(string)$row['db'],'scopes'=>(string)$row['scopes']];
    }

    public function __destruct()
    {
        if (isset($this->db) && $this->db instanceof mysqli) $this->db->close();
    }
}
