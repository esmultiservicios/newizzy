<?php
/** Registro de credenciales servidor a servidor de la API IZZY. */
declare(strict_types=1);

final class IzzyApiIntegrationService
{
    private mysqli $db;

    public function __construct()
    {
        $peticionAjax = true;
        require_once dirname(__DIR__) . '/configGenerales.php';
        require_once dirname(__DIR__) . '/mainModel.php';
        $this->db = (new mainModel())->connectionDBLocal(DB_MAIN);
    }

    public function listar(): array
    {
        $result = $this->db->query('SELECT i.id, i.nombre, i.client_id, i.server_customers_id, i.scopes, i.expires_at, i.revoked_at, i.created_at, '
            . 'COALESCE(NULLIF(TRIM(c.nombre),\'\'),\'Empresa sin nombre\') AS nombre_empresa '
            . 'FROM api_integrations i LEFT JOIN server_customers s ON s.server_customers_id=i.server_customers_id '
            . 'LEFT JOIN clientes c ON c.clientes_id=s.clientes_id ORDER BY i.created_at DESC, i.id DESC LIMIT 200');
        if (!$result) throw new RuntimeException('No se pudieron cargar las integraciones. Verifique la migración api_integrations.');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function autorizaciones(): array
    {
        $result=$this->db->query('SELECT s.server_customers_id, s.codigo_cliente, s.db, s.estado, s.sistema_id, '
            .'COALESCE(NULLIF(TRIM(si.nombre),\'\'),\'Sin sistema\') AS sistema_nombre, '
            .'COALESCE(NULLIF(TRIM(c.nombre),\'\'),\'Empresa sin nombre\') AS nombre_empresa, '
            .'COALESCE(a.habilitada,0) AS habilitada '
            .'FROM server_customers s LEFT JOIN clientes c ON c.clientes_id=s.clientes_id '
            .'LEFT JOIN sistema si ON si.sistema_id=s.sistema_id '
            .'LEFT JOIN api_empresa_autorizacion a ON a.server_customers_id=s.server_customers_id '
            .'ORDER BY s.server_customers_id DESC');
        if (!$result) throw new RuntimeException('No se pudo consultar la autorización de empresas.');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function autorizar(int $clienteId, bool $habilitar, int $operador): void
    {
        if ($clienteId<=0 || $operador<=0) throw new InvalidArgumentException('Empresa u operador inválido.');
        $stmt=$this->db->prepare('SELECT estado,sistema_id FROM server_customers WHERE server_customers_id=? LIMIT 1');
        $stmt->bind_param('i',$clienteId); $stmt->execute(); $cliente=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$cliente || ($habilitar && (int)$cliente['estado']!==1)) throw new InvalidArgumentException('La empresa debe existir y estar activa.');
        if ($habilitar && (int)$cliente['sistema_id']!==1) throw new InvalidArgumentException('En esta fase solo puede autorizar empresas del sistema IZZY.');
        $enabled=$habilitar?1:0;
        $this->db->begin_transaction();
        try {
            $stmt=$this->db->prepare('INSERT INTO api_empresa_autorizacion (server_customers_id,habilitada,autorizado_por_users_id,autorizado_at) VALUES (?,?,?,IF(?=1,NOW(),NULL)) ON DUPLICATE KEY UPDATE habilitada=VALUES(habilitada),autorizado_por_users_id=VALUES(autorizado_por_users_id),autorizado_at=VALUES(autorizado_at)');
            $stmt->bind_param('iiii',$clienteId,$enabled,$operador,$enabled); $stmt->execute(); $stmt->close();
            if (!$habilitar) {
                $stmt=$this->db->prepare('UPDATE api_integration_tokens t INNER JOIN api_integrations i ON i.id=t.integration_id SET t.revoked_at=COALESCE(t.revoked_at,NOW()) WHERE i.server_customers_id=?');
                $stmt->bind_param('i',$clienteId); $stmt->execute(); $stmt->close();
            }
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollback(); throw $e; }
    }

    public function permitido(int $clienteId): bool
    {
        if ($clienteId<=0) return false;
        $stmt=$this->db->prepare('SELECT 1 FROM api_empresa_autorizacion a INNER JOIN server_customers s ON s.server_customers_id=a.server_customers_id WHERE a.server_customers_id=? AND a.habilitada=1 AND s.estado=1 AND s.sistema_id=1 LIMIT 1');
        $stmt->bind_param('i',$clienteId); $stmt->execute(); $ok=(bool)$stmt->get_result()->fetch_assoc(); $stmt->close();
        return $ok;
    }

    public function listarCliente(int $clienteId): array
    {
        $stmt=$this->db->prepare('SELECT id,nombre,client_id,scopes,expires_at,revoked_at,created_at FROM api_integrations WHERE server_customers_id=? ORDER BY id DESC LIMIT 100');
        $stmt->bind_param('i',$clienteId); $stmt->execute(); $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
        return $rows;
    }

    public function crear(string $name, int $expiresInDays, int $createdBy, int $clienteId, array $scopes): array
    {
        $name = trim($name);
        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($name === '' || $nameLength > 100) throw new InvalidArgumentException('Escriba un nombre de hasta 100 caracteres.');
        if (!in_array($expiresInDays, [0, 30, 90, 365], true)) throw new InvalidArgumentException('El vencimiento seleccionado no es válido.');
        if ($createdBy <= 0) throw new InvalidArgumentException('No se pudo identificar al administrador actual.');
        if (!$this->permitido($clienteId)) throw new DomainException('IZZY no ha autorizado el uso de integraciones para esta empresa.');
        require_once __DIR__.'/ApiModuleCatalog.php';
        $validos=array_map(static fn(array $module): string => $module['clave'].':read', IzzyApiModuleCatalog::items());
        $scopes=array_values(array_unique(array_map('strval',$scopes)));
        if (!$scopes || array_diff($scopes,$validos)) throw new InvalidArgumentException('Seleccione alcances autorizados válidos.');

        $clientId = 'izzy_cli_' . bin2hex(random_bytes(12));
        $secret = 'izzy_sec_' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $secretHash = hash('sha256', $secret);
        $expiresAt = $expiresInDays > 0 ? date('Y-m-d H:i:s', time() + ($expiresInDays * 86400)) : null;

        $scopeText=implode(' ',$scopes);
        $stmt = $this->db->prepare('INSERT INTO api_integrations (nombre, client_id, secret_hash, created_by, server_customers_id, scopes, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) throw new RuntimeException('No se pudo preparar el registro de la integración.');
        $stmt->bind_param('sssiiss', $name, $clientId, $secretHash, $createdBy, $clienteId, $scopeText, $expiresAt);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('No se pudo guardar la integración en DB_MAIN.');
        }
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return ['id' => $id, 'nombre' => $name, 'client_id' => $clientId, 'client_secret' => $secret, 'expires_at' => $expiresAt];
    }

    public function revocar(int $id, ?int $clienteId = null): void
    {
        if ($id <= 0) throw new InvalidArgumentException('La integración seleccionada no es válida.');
        $stmt = $this->db->prepare('UPDATE api_integrations SET revoked_at = COALESCE(revoked_at, NOW()) WHERE id = ?'.($clienteId!==null?' AND server_customers_id = ?':''));
        if (!$stmt) throw new RuntimeException('No se pudo preparar la revocación.');
        if ($clienteId!==null) $stmt->bind_param('ii',$id,$clienteId);
        else $stmt->bind_param('i', $id);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
        if ($changed < 1) {
            $check = $this->db->prepare('SELECT id FROM api_integrations WHERE id = ?'.($clienteId!==null?' AND server_customers_id = ?':'').' LIMIT 1');
            if (!$check) throw new RuntimeException('No se pudo verificar la integración.');
            if ($clienteId!==null) $check->bind_param('ii',$id,$clienteId);
            else $check->bind_param('i',$id);
            $check->execute();
            $exists = (bool)$check->get_result()->fetch_assoc(); $check->close();
            if (!$exists) throw new InvalidArgumentException('La integración ya no existe.');
        }
        $stmt=$this->db->prepare('UPDATE api_integration_tokens SET revoked_at=COALESCE(revoked_at,NOW()) WHERE integration_id=?');
        $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();
    }

    public function __destruct()
    {
        if (isset($this->db) && $this->db instanceof mysqli) $this->db->close();
    }
}
