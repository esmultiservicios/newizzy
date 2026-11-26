CREATE TABLE IF NOT EXISTS api_zynko_document_dispatch (
 dispatch_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 connection_id BIGINT UNSIGNED NOT NULL,
 actor_id BIGINT UNSIGNED NOT NULL,
 credential_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 payload_cifrado MEDIUMTEXT NOT NULL,
 message_uuid CHAR(36) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (connection_id) REFERENCES api_external_connections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
