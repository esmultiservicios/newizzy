<?php
/** Administración de sesiones creadas por el flujo existente de api/v1/auth. */
declare(strict_types=1);

final class IzzyApiAdminService
{
    private mysqli $db;

    public function __construct()
    {
        $peticionAjax = true;
        require_once dirname(__DIR__) . '/configGenerales.php';
        require_once dirname(__DIR__) . '/mainModel.php';
        $this->db = (new mainModel())->connectionDBLocal(DB_MAIN);
    }

    public function listarSesiones(int $limit = 100): array
    {
        $limit = max(1, min(250, $limit));
        $result = $this->db->query('SELECT id, users_id, empresa_id, server_customers_id, db_cliente, expires_at, revoked_at, created_at '
            . 'FROM api_auth_tokens ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
        if (!$result) throw new RuntimeException('No se pudieron consultar las sesiones móviles.');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function revocarSesion(int $id): void
    {
        if ($id <= 0) throw new InvalidArgumentException('La sesión seleccionada no es válida.');
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('SELECT refresh_family_id FROM api_auth_tokens WHERE id = ? LIMIT 1 FOR UPDATE');
            if (!$stmt) throw new RuntimeException('No se pudo consultar la sesión.');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) throw new InvalidArgumentException('La sesión ya no existe.');
            $family = (string)($row['refresh_family_id'] ?? '');
            if ($family === '') throw new RuntimeException('La sesión no tiene asociada una familia de renovación.');

            $stmt = $this->db->prepare('UPDATE api_auth_tokens SET revoked_at = COALESCE(revoked_at, NOW()) WHERE refresh_family_id = ?');
            if (!$stmt) throw new RuntimeException('No se pudo revocar el token de acceso.');
            $stmt->bind_param('s', $family); $stmt->execute(); $stmt->close();

            $stmt = $this->db->prepare('UPDATE api_auth_refresh_tokens SET revoked_at = COALESCE(revoked_at, NOW()) WHERE family_id = ?');
            if (!$stmt) throw new RuntimeException('No se pudo revocar el token de renovación.');
            $stmt->bind_param('s', $family); $stmt->execute(); $stmt->close();

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function __destruct()
    {
        if (isset($this->db) && $this->db instanceof mysqli) $this->db->close();
    }
}
