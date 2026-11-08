-- IZZY | Registro de integraciones servidor a servidor
-- Agregar este bloque al script general de cambios de DB_MAIN y ejecutarlo una vez.
-- No modifica las tablas de login móvil ni las sesiones de usuarios.

CREATE TABLE IF NOT EXISTS `api_integrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `client_id` VARCHAR(48) NOT NULL,
  `secret_hash` CHAR(64) NOT NULL,
  `created_by` INT NOT NULL,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `revoked_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_integrations_client_id` (`client_id`),
  KEY `ix_api_integrations_state` (`revoked_at`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
