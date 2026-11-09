-- IZZY API | Script consolidado Fases 1 y 2, solo DB_MAIN.
-- Respalde DB_MAIN antes de ejecutar. CREATE IF NOT EXISTS no borra registros.
-- En instalaciones con tablas V007 existentes, no se reconstruyen ni vacían.

CREATE TABLE IF NOT EXISTS api_auth_rate_limits (
  identifier_hash CHAR(64) NOT NULL,
  window_start DATETIME NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  PRIMARY KEY (identifier_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_auth_refresh_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_hash CHAR(64) NOT NULL,
  family_id CHAR(32) NOT NULL,
  users_id INT NOT NULL,
  empresa_id INT NOT NULL,
  server_customers_id INT NOT NULL DEFAULT 0,
  db_cliente VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  rotated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_refresh_hash (token_hash),
  KEY idx_api_refresh_family (family_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_auth_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_hash CHAR(64) NOT NULL,
  users_id INT NOT NULL,
  empresa_id INT NOT NULL,
  server_customers_id INT NOT NULL DEFAULT 0,
  db_cliente VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  refresh_family_id CHAR(32) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_auth_token_hash (token_hash),
  KEY idx_api_auth_family (refresh_family_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Fase 1 + Fase 2: autorización explícita por empresa, sin aprobaciones automáticas.
CREATE TABLE IF NOT EXISTS api_empresa_autorizacion (
  server_customers_id INT NOT NULL,
  habilitada TINYINT(1) NOT NULL DEFAULT 0,
  autorizado_por_users_id INT NULL,
  autorizado_at DATETIME NULL,
  actualizado_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (server_customers_id),
  KEY idx_api_empresa_habilitada (habilitada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agrega columnas solo cuando aún no existen, preservando integraciones anteriores.
DROP PROCEDURE IF EXISTS izzy_api_migrar_integraciones;
DELIMITER $$
CREATE PROCEDURE izzy_api_migrar_integraciones()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='api_integrations' AND COLUMN_NAME='server_customers_id') THEN
    ALTER TABLE api_integrations ADD COLUMN server_customers_id INT NOT NULL DEFAULT 0 AFTER created_by;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='api_integrations' AND COLUMN_NAME='scopes') THEN
    ALTER TABLE api_integrations ADD COLUMN scopes VARCHAR(255) NOT NULL DEFAULT '' AFTER server_customers_id;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='api_integrations' AND INDEX_NAME='ix_api_integrations_tenant') THEN
    ALTER TABLE api_integrations ADD KEY ix_api_integrations_tenant (server_customers_id, revoked_at);
  END IF;
END$$
DELIMITER ;
CALL izzy_api_migrar_integraciones();
DROP PROCEDURE izzy_api_migrar_integraciones;

-- Los tokens solo contienen hashes, nunca el secreto en texto legible.
CREATE TABLE IF NOT EXISTS api_integration_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_hash CHAR(64) NOT NULL,
  integration_id BIGINT UNSIGNED NOT NULL,
  scopes VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_integration_tokens_hash (token_hash),
  KEY idx_api_integration_tokens_integration (integration_id),
  CONSTRAINT fk_api_integration_tokens_integration FOREIGN KEY (integration_id)
    REFERENCES api_integrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integraciones heredadas mantienen server_customers_id=0 y quedan denegadas.
-- No se asigna automáticamente ninguna credencial a una empresa.
