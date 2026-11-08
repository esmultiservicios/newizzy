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
        $result = $this->db->query('SELECT id, nombre, client_id, expires_at, revoked_at, created_at '
            . 'FROM api_integrations ORDER BY created_at DESC, id DESC LIMIT 200');
        if (!$result) throw new RuntimeException('No se pudieron cargar las integraciones. Verifique la migración api_integrations.');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function crear(string $name, int $expiresInDays, int $createdBy): array
    {
        $name = trim($name);
        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($name === '' || $nameLength > 100) throw new InvalidArgumentException('Escriba un nombre de hasta 100 caracteres.');
        if (!in_array($expiresInDays, [0, 30, 90, 365], true)) throw new InvalidArgumentException('El vencimiento seleccionado no es válido.');
        if ($createdBy <= 0) throw new InvalidArgumentException('No se pudo identificar al administrador actual.');

        $clientId = 'izzy_cli_' . bin2hex(random_bytes(12));
        $secret = 'izzy_sec_' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $secretHash = hash('sha256', $secret);
        $expiresAt = $expiresInDays > 0 ? date('Y-m-d H:i:s', time() + ($expiresInDays * 86400)) : null;

        $stmt = $this->db->prepare('INSERT INTO api_integrations (nombre, client_id, secret_hash, created_by, expires_at) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) throw new RuntimeException('No se pudo preparar el registro de la integración.');
        $stmt->bind_param('sssis', $name, $clientId, $secretHash, $createdBy, $expiresAt);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('No se pudo guardar la integración en DB_MAIN.');
        }
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return ['id' => $id, 'nombre' => $name, 'client_id' => $clientId, 'client_secret' => $secret, 'expires_at' => $expiresAt];
    }

    public function revocar(int $id): void
    {
        if ($id <= 0) throw new InvalidArgumentException('La integración seleccionada no es válida.');
        $stmt = $this->db->prepare('UPDATE api_integrations SET revoked_at = COALESCE(revoked_at, NOW()) WHERE id = ?');
        if (!$stmt) throw new RuntimeException('No se pudo preparar la revocación.');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
        if ($changed < 1) {
            $check = $this->db->prepare('SELECT id FROM api_integrations WHERE id = ? LIMIT 1');
            if (!$check) throw new RuntimeException('No se pudo verificar la integración.');
            $check->bind_param('i', $id); $check->execute();
            $exists = (bool)$check->get_result()->fetch_assoc(); $check->close();
            if (!$exists) throw new InvalidArgumentException('La integración ya no existe.');
        }
    }

    public function __destruct()
    {
        if (isset($this->db) && $this->db instanceof mysqli) $this->db->close();
    }
}
