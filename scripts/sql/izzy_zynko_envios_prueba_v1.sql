CREATE TABLE IF NOT EXISTS api_zynko_test_sends (
    connection_id BIGINT UNSIGNED NOT NULL,
    operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    actor_id BIGINT UNSIGNED NOT NULL,
    mode ENUM('single','bulk') NOT NULL,
    credential_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    payload_cifrado MEDIUMTEXT NOT NULL,
    consent_ref_cifrada TEXT NOT NULL,
    state ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
    http_status SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    provider_code VARCHAR(48) NOT NULL DEFAULT '',
    detail VARCHAR(160) NOT NULL DEFAULT '',
    results_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (connection_id,operation_id),
    KEY idx_zynko_test_created (connection_id,created_at),
    CONSTRAINT fk_zynko_test_connection FOREIGN KEY (connection_id) REFERENCES api_external_connections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
