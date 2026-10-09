-- ============================================================================
-- IZZY | DB_Cambios.sql
-- Actualización acumulativa e idempotente
-- Generado a partir del histórico DB_Cambios.txt del repositorio y ampliado
-- con Nota de Crédito (aplicaciones + devolución de inventario).
--
-- OBJETIVO
--   - Tabla existente      => no se recrea.
--   - Columna existente    => no se vuelve a agregar.
--   - Índice existente     => no se duplica.
--   - Dato existente       => no se inserta nuevamente.
--   - FK existente         => no se duplica.
--   - Puede ejecutarse nuevamente con seguridad razonable.
--
-- IMPORTANTE
--   - NO crea ni activa una autorización SAR.
--   - Nota de Crédito requiere documento_id = 2 ACTIVO y su secuencia SAR.
--   - Las secciones DB_MAIN solo hacen cambios si la tabla objetivo existe
--     en la base donde se está ejecutando.
-- ============================================================================

SET NAMES utf8mb4;
SET @IZZY_DB := DATABASE();

-- ============================================================================
-- HELPERS TEMPORALES
-- ============================================================================

DROP PROCEDURE IF EXISTS izzy_add_column;
DROP PROCEDURE IF EXISTS izzy_add_index;
DROP PROCEDURE IF EXISTS izzy_add_fk;
DROP PROCEDURE IF EXISTS izzy_drop_column;
DROP PROCEDURE IF EXISTS izzy_change_engine;
DROP PROCEDURE IF EXISTS izzy_exec_if_table;

DELIMITER $$

CREATE PROCEDURE izzy_add_column(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @izzy_sql = CONCAT(
            'ALTER TABLE `', REPLACE(p_table,'`','``'),
            '` ADD COLUMN `', REPLACE(p_column,'`','``'),
            '` ', p_definition
        );
        PREPARE izzy_stmt FROM @izzy_sql;
        EXECUTE izzy_stmt;
        DEALLOCATE PREPARE izzy_stmt;
    END IF;
END$$

CREATE PROCEDURE izzy_add_index(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @izzy_sql = CONCAT(
            'ALTER TABLE `', REPLACE(p_table,'`','``'),
            '` ADD ', p_definition
        );
        PREPARE izzy_stmt FROM @izzy_sql;
        EXECUTE izzy_stmt;
        DEALLOCATE PREPARE izzy_stmt;
    END IF;
END$$

CREATE PROCEDURE izzy_add_fk(
    IN p_table VARCHAR(64),
    IN p_constraint VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_constraint
    ) THEN
        SET @izzy_sql = CONCAT(
            'ALTER TABLE `', REPLACE(p_table,'`','``'),
            '` ADD CONSTRAINT `', REPLACE(p_constraint,'`','``'),
            '` ', p_definition
        );
        PREPARE izzy_stmt FROM @izzy_sql;
        EXECUTE izzy_stmt;
        DEALLOCATE PREPARE izzy_stmt;
    END IF;
END$$

CREATE PROCEDURE izzy_drop_column(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64)
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @izzy_sql = CONCAT(
            'ALTER TABLE `', REPLACE(p_table,'`','``'),
            '` DROP COLUMN `', REPLACE(p_column,'`','``'), '`'
        );
        PREPARE izzy_stmt FROM @izzy_sql;
        EXECUTE izzy_stmt;
        DEALLOCATE PREPARE izzy_stmt;
    END IF;
END$$

CREATE PROCEDURE izzy_change_engine(
    IN p_table VARCHAR(64),
    IN p_engine VARCHAR(32)
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND ENGINE <> p_engine
    ) THEN
        SET @izzy_sql = CONCAT(
            'ALTER TABLE `', REPLACE(p_table,'`','``'),
            '` ENGINE=', p_engine
        );
        PREPARE izzy_stmt FROM @izzy_sql;
        EXECUTE izzy_stmt;
        DEALLOCATE PREPARE izzy_stmt;
    END IF;
END$$

CREATE PROCEDURE izzy_exec_if_table(
    IN p_table VARCHAR(64),
    IN p_sql LONGTEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
    ) THEN
        SET @izzy_sql = p_sql;
        PREPARE izzy_stmt FROM @izzy_sql;
        EXECUTE izzy_stmt;
        DEALLOCATE PREPARE izzy_stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- 2026-09-11 | CHEQUES
-- ============================================================================

CALL izzy_add_column('cheque','numero_cheque',"VARCHAR(30) COLLATE utf8mb4_spanish_ci NULL AFTER `cheque_id`");
CALL izzy_add_column('cheque','tipo_pago_id',"INT NOT NULL DEFAULT 4 AFTER `empresa_id`");
CALL izzy_add_column('cheque','egresos_id',"INT NULL AFTER `tipo_pago_id`");
CALL izzy_add_column('cheque','categoria_gastos_id',"INT NULL AFTER `egresos_id`");
CALL izzy_add_column('cheque','movimientos_cuentas_id',"INT NULL AFTER `categoria_gastos_id`");
CALL izzy_add_column('cheque','movimiento_reintegro_id',"INT NULL AFTER `movimientos_cuentas_id`");
CALL izzy_add_column('cheque','estado',"TINYINT NOT NULL DEFAULT 1 COMMENT '1 Activo / 0 Anulado' AFTER `observacion`");
CALL izzy_add_column('cheque','fecha_anulacion',"DATETIME NULL AFTER `fecha_registro`");
CALL izzy_add_column('cheque','colaboradores_anula_id',"INT NULL AFTER `fecha_anulacion`");
CALL izzy_add_column('cheque','motivo_anulacion',"VARCHAR(255) COLLATE utf8mb4_spanish_ci NULL AFTER `colaboradores_anula_id`");

CALL izzy_exec_if_table('cheque',
    "UPDATE `cheque` SET `estado`=1 WHERE `estado` IS NULL"
);

CALL izzy_add_index('cheque','idx_cheque_empresa_fecha',"INDEX `idx_cheque_empresa_fecha` (`empresa_id`,`fecha`)");
CALL izzy_add_index('cheque','idx_cheque_cuenta',"INDEX `idx_cheque_cuenta` (`cuentas_id`)");
CALL izzy_add_index('cheque','idx_cheque_proveedor',"INDEX `idx_cheque_proveedor` (`proveedores_id`)");
CALL izzy_add_index('cheque','idx_cheque_categoria',"INDEX `idx_cheque_categoria` (`categoria_gastos_id`)");
CALL izzy_add_index('cheque','idx_cheque_egreso',"INDEX `idx_cheque_egreso` (`egresos_id`)");
CALL izzy_add_index('cheque','idx_cheque_estado',"INDEX `idx_cheque_estado` (`estado`)");
CALL izzy_add_index('cheque','ux_cheque_empresa_numero',"UNIQUE INDEX `ux_cheque_empresa_numero` (`empresa_id`,`numero_cheque`)");

INSERT INTO `tipo_pago` (`tipo_pago_id`,`tipo_cuenta_id`,`nombre`,`cuentas_id`,`estado`,`fecha_registro`)
SELECT 4,4,'Cheque',0,1,NOW()
WHERE EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tipo_pago'
)
AND NOT EXISTS (SELECT 1 FROM `tipo_pago` WHERE `tipo_pago_id`=4);

CALL izzy_exec_if_table('tipo_pago',
    "UPDATE `tipo_pago` SET `nombre`='Cheque', `estado`=1 WHERE `tipo_pago_id`=4"
);

-- ============================================================================
-- 2026-09-04 + 2026-10 | NOTA DE CRÉDITO
-- ============================================================================

CREATE TABLE IF NOT EXISTS `notas_credito` (
  `nota_credito_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `facturas_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `secuencia_facturacion_id` int NOT NULL,
  `documento_id` int NOT NULL DEFAULT 2,
  `number` int NOT NULL,
  `prefijo` varchar(30) NOT NULL DEFAULT '',
  `relleno` int NOT NULL DEFAULT 0,
  `numero_completo` varchar(80) NOT NULL,
  `fecha` date NOT NULL,
  `motivo` varchar(500) NOT NULL,
  `base_acreditada` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv15_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv18_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `total_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `importe_factura_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `total_acreditado_anterior` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `cxc_aplicada` tinyint(1) NOT NULL DEFAULT 0,
  `cxc_saldo_antes` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `cxc_saldo_despues` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `credito_favor` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `secuencia_actualizada` tinyint(1) NOT NULL DEFAULT 0,
  `estado` tinyint NOT NULL DEFAULT 1 COMMENT '1=Emitida, 2=Anulada',
  `colaboradores_id` int NOT NULL,
  `usuario` int NOT NULL,
  `origen` varchar(30) NOT NULL DEFAULT 'escritorio',
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`nota_credito_id`),
  UNIQUE KEY `uq_nc_numero_fiscal` (`empresa_id`,`secuencia_facturacion_id`,`number`),
  KEY `idx_nc_factura` (`empresa_id`,`facturas_id`,`estado`),
  KEY `idx_nc_cliente` (`empresa_id`,`clientes_id`),
  KEY `idx_nc_fecha` (`empresa_id`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `notas_credito_detalle` (
  `nota_credito_detalle_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nota_credito_id` bigint unsigned NOT NULL,
  `facturas_detalle_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `producto` varchar(255) NOT NULL,
  `cantidad_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `precio_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `descuento_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `base_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv15_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv18_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `base_acreditada` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv15_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv18_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `total_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`nota_credito_detalle_id`),
  KEY `idx_ncd_nota` (`nota_credito_id`),
  KEY `idx_ncd_factura_detalle` (`facturas_detalle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_fk(
    'notas_credito_detalle',
    'fk_ncd_nota',
    'FOREIGN KEY (`nota_credito_id`) REFERENCES `notas_credito` (`nota_credito_id`) ON DELETE RESTRICT ON UPDATE CASCADE'
);

CREATE TABLE IF NOT EXISTS `notas_credito_aplicaciones` (
  `nota_credito_aplicacion_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nota_credito_id` bigint unsigned NOT NULL,
  `empresa_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `facturas_id_destino` int NOT NULL,
  `cobrar_clientes_id` int NOT NULL,
  `importe` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `saldo_antes` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `saldo_despues` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `estado` tinyint NOT NULL DEFAULT 0 COMMENT '0=Pendiente, 1=Aplicada, 2=Requiere revisión',
  `usuario` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`nota_credito_aplicacion_id`),
  UNIQUE KEY `uq_nc_aplicacion_factura` (`nota_credito_id`,`facturas_id_destino`),
  KEY `idx_nc_aplicacion_cliente` (`empresa_id`,`clientes_id`,`estado`),
  KEY `idx_nc_aplicacion_destino` (`empresa_id`,`facturas_id_destino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `notas_credito_inventario` (
  `nota_credito_inventario_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nota_credito_id` bigint unsigned NOT NULL,
  `nota_credito_detalle_id` bigint unsigned NOT NULL DEFAULT 0,
  `movimiento_origen_id` int NOT NULL,
  `movimiento_devolucion_id` int NOT NULL DEFAULT 0,
  `empresa_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `almacen_id` int NOT NULL,
  `lote_id` int NOT NULL DEFAULT 0,
  `cantidad_devuelta` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `estado` tinyint NOT NULL DEFAULT 0 COMMENT '0=Pendiente, 1=Aplicada',
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`nota_credito_inventario_id`),
  UNIQUE KEY `uq_nc_inventario_mov` (`nota_credito_id`,`movimiento_origen_id`),
  KEY `idx_nc_inv_origen` (`movimiento_origen_id`,`estado`),
  KEY `idx_nc_inv_factura` (`empresa_id`,`nota_credito_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================================
-- 2026-08-23 | RESTAURANTE / PANTALLA COCINA (DB_MAIN)
-- Solo actúa sobre restaurante_pantalla_dispositivos si la tabla ya existe.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `restaurante_configuracion_historial` (
    `historial_id` BIGINT NOT NULL AUTO_INCREMENT,
    `server_customers_id` INT NOT NULL DEFAULT 0,
    `empresa_id` INT NOT NULL,
    `users_id` INT NOT NULL DEFAULT 0,
    `colaborador_id` INT NOT NULL DEFAULT 0,
    `categoria` VARCHAR(40) NOT NULL DEFAULT 'general',
    `resumen` VARCHAR(255) NOT NULL DEFAULT '',
    `cambios_json` LONGTEXT NULL,
    `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`historial_id`),
    KEY `idx_rest_cfg_hist_empresa` (`server_customers_id`,`empresa_id`,`fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `restaurante_pantalla_accesos` (
    `acceso_id` BIGINT NOT NULL AUTO_INCREMENT,
    `server_customers_id` INT NOT NULL,
    `empresa_id` INT NOT NULL,
    `tipo` VARCHAR(20) NOT NULL DEFAULT 'cocina',
    `token_hash` CHAR(64) NOT NULL,
    `token_cifrado` TEXT NOT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 0,
    `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` DATETIME NULL DEFAULT NULL,
    `fecha_regeneracion` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`acceso_id`),
    UNIQUE KEY `uq_rest_pantalla_empresa_tipo` (`server_customers_id`,`empresa_id`,`tipo`),
    UNIQUE KEY `uq_rest_pantalla_token_hash` (`token_hash`),
    KEY `idx_rest_pantalla_resolver` (`token_hash`,`tipo`,`activo`),
    KEY `idx_rest_pantalla_cliente` (`server_customers_id`,`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `restaurante_pantalla_pruebas` (
    `prueba_id` BIGINT NOT NULL AUTO_INCREMENT,
    `acceso_id` BIGINT NOT NULL,
    `mensaje` VARCHAR(160) NOT NULL DEFAULT 'Prueba de conexión IZZY',
    `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_expira` DATETIME NOT NULL,
    PRIMARY KEY (`prueba_id`),
    KEY `idx_rest_pantalla_prueba` (`acceso_id`,`fecha_expira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CALL izzy_add_column(
    'restaurante_pantalla_dispositivos',
    'oculto_vista',
    'TINYINT(1) NOT NULL DEFAULT 0 AFTER `activo`'
);

-- ============================================================================
-- 2026-08-21 | FACTURACIÓN RECURRENTE
-- ============================================================================

CALL izzy_exec_if_table(
    'config',
    "UPDATE `config`
     SET `accion`='Convertir Proforma Pagada a Factura', `activar`=1
     WHERE `config_id`=7"
);

CREATE TABLE IF NOT EXISTS `facturas_recurrentes` (
  `rec_id` INT NOT NULL AUTO_INCREMENT,
  `empresa_id` INT NOT NULL,
  `clientes_id` INT NOT NULL,
  `colaboradores_id` INT NOT NULL,
  `tipo_documento` TINYINT NOT NULL COMMENT '0=Normal, 1=Proforma',
  `tipo_factura` TINYINT NOT NULL DEFAULT 2 COMMENT 'Siempre 2=Credito',
  `notas` VARCHAR(255) NOT NULL DEFAULT '',
  `fecha_dolar` DATE NOT NULL,
  `exoneracion_orden` VARCHAR(100) DEFAULT NULL,
  `exoneracion_constancia` VARCHAR(100) DEFAULT NULL,
  `exoneracion_sag` VARCHAR(100) DEFAULT NULL,
  `exoneracion_orden_interno` VARCHAR(100) DEFAULT NULL,
  `periodicidad` ENUM('once','daily','weekly','monthly') NOT NULL DEFAULT 'monthly',
  `dia_mes` TINYINT DEFAULT NULL COMMENT 'Día original para recurrencia mensual',
  `start_at` DATETIME NOT NULL,
  `next_run_at` DATETIME NOT NULL,
  `until_at` DATE DEFAULT NULL,
  `estado` TINYINT NOT NULL DEFAULT 1 COMMENT '1=Activa, 2=Cancelada, 3=Finalizada',
  `enviar_correo` TINYINT NOT NULL DEFAULT 1 COMMENT '1=Enviar al cliente, 2=No enviar',
  `ultimo_facturas_id` INT DEFAULT NULL,
  `last_run_at` DATETIME DEFAULT NULL,
  `ultimo_error` TEXT DEFAULT NULL,
  `usuario_crea` INT NOT NULL,
  `fecha_crea` DATETIME NOT NULL,
  PRIMARY KEY (`rec_id`),
  KEY `idx_recurrente_pendiente` (`estado`,`next_run_at`),
  KEY `idx_recurrente_empresa` (`empresa_id`,`rec_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column('facturas_recurrentes','dia_mes','TINYINT DEFAULT NULL AFTER `periodicidad`');
CALL izzy_add_column('facturas_recurrentes','enviar_correo','TINYINT NOT NULL DEFAULT 1 AFTER `estado`');
CALL izzy_add_column('facturas_recurrentes','ultimo_facturas_id','INT DEFAULT NULL AFTER `enviar_correo`');
CALL izzy_add_column('facturas_recurrentes','last_run_at','DATETIME DEFAULT NULL AFTER `ultimo_facturas_id`');
CALL izzy_add_column('facturas_recurrentes','ultimo_error','TEXT DEFAULT NULL AFTER `last_run_at`');

CREATE TABLE IF NOT EXISTS `facturas_recurrentes_detalle` (
  `rec_detalle_id` INT NOT NULL AUTO_INCREMENT,
  `rec_id` INT NOT NULL,
  `productos_id` INT NOT NULL,
  `producto` VARCHAR(255) NOT NULL,
  `cantidad` DECIMAL(12,2) NOT NULL,
  `precio` DECIMAL(12,4) NOT NULL,
  `descuento` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `isv_valor` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `isv_valor1` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `medida` VARCHAR(50) DEFAULT NULL,
  `almacen_id` INT DEFAULT NULL,
  `precio_real` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `referencia_producto` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`rec_detalle_id`),
  KEY `idx_rec_detalle` (`rec_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column('facturas_recurrentes_detalle','isv_valor1','DECIMAL(12,4) NOT NULL DEFAULT 0 AFTER `isv_valor`');
CALL izzy_add_column('facturas_recurrentes_detalle','precio_real','DECIMAL(12,4) NOT NULL DEFAULT 0 AFTER `almacen_id`');
CALL izzy_add_column('facturas_recurrentes_detalle','referencia_producto','VARCHAR(255) DEFAULT NULL AFTER `precio_real`');

CALL izzy_add_fk(
    'facturas_recurrentes_detalle',
    'fk_recurrente_detalle',
    'FOREIGN KEY (`rec_id`) REFERENCES `facturas_recurrentes` (`rec_id`) ON DELETE CASCADE'
);

CREATE TABLE IF NOT EXISTS `facturas_recurrentes_ejecuciones` (
  `ejecucion_id` BIGINT NOT NULL AUTO_INCREMENT,
  `rec_id` INT NOT NULL,
  `empresa_id` INT NOT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `facturas_id` INT DEFAULT NULL,
  `estado` TINYINT NOT NULL DEFAULT 0 COMMENT '0=Procesando, 1=Generada, 2=Error',
  `correo_estado` TINYINT NOT NULL DEFAULT 0 COMMENT '0=Pendiente, 1=Enviado, 2=Error, 3=No solicitado',
  `mensaje` TEXT DEFAULT NULL,
  `fecha_inicio` DATETIME NOT NULL,
  `fecha_fin` DATETIME DEFAULT NULL,
  PRIMARY KEY (`ejecucion_id`),
  UNIQUE KEY `uq_rec_fecha` (`rec_id`,`scheduled_at`),
  KEY `idx_ejecucion_factura` (`facturas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_fk(
    'facturas_recurrentes_ejecuciones',
    'fk_recurrente_ejecucion',
    'FOREIGN KEY (`rec_id`) REFERENCES `facturas_recurrentes` (`rec_id`) ON DELETE CASCADE'
);

-- ============================================================================
-- 2026-07-11 | CONFIG PROFORMA
-- ============================================================================

INSERT INTO `config` (`config_id`,`accion`,`activar`)
SELECT 7,'Convertir Proforma Credito a Factura',2
WHERE EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='config'
)
AND NOT EXISTS (SELECT 1 FROM `config` WHERE `config_id`=7);

-- ============================================================================
-- 2026-07-05 | INVENTARIO / COMANDAS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `inventario_ajustes` (
  `inventario_ajustes_id` INT AUTO_INCREMENT PRIMARY KEY,
  `productos_id` INT NOT NULL,
  `almacen_id` INT NOT NULL,
  `lote_id` INT NULL,
  `saldo_sistema` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `conteo_fisico` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `diferencia` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tipo_ajuste` VARCHAR(20) NOT NULL,
  `movimientos_id` INT NULL,
  `comentario` VARCHAR(255) NULL,
  `empresa_id` INT NOT NULL,
  `colaboradores_id` INT NOT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- mesa_id ya existente se conserva; solo se normaliza si la tabla/columna existen.
SET @izzy_col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='factura_comanda' AND COLUMN_NAME='mesa_id'
);
SET @izzy_sql := IF(
    @izzy_col_exists>0,
    'ALTER TABLE `factura_comanda` MODIFY COLUMN `mesa_id` INT NULL',
    'SELECT ''factura_comanda.mesa_id no existe; sin cambios'' AS resultado'
);
PREPARE izzy_stmt FROM @izzy_sql; EXECUTE izzy_stmt; DEALLOCATE PREPARE izzy_stmt;

-- ============================================================================
-- 2026-06-21 | AUDITORÍA ADMIN
-- ============================================================================

CREATE TABLE IF NOT EXISTS `auditoria_admin_autorizaciones` (
  `auditoria_admin_id` int NOT NULL AUTO_INCREMENT,
  `usuario_sesion_id` int DEFAULT NULL,
  `usuario_sesion_tipo_id` int DEFAULT NULL,
  `admin_users_id` int DEFAULT NULL,
  `admin_tipo_user_id` int DEFAULT NULL,
  `empresa_id` int NOT NULL DEFAULT 0,
  `modulo` varchar(80) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Sistema',
  `accion` varchar(120) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Validación administrativa',
  `referencia_id` varchar(80) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `referencia_texto` varchar(180) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `usuario_ingresado` varchar(80) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `permitido` tinyint NOT NULL DEFAULT 0,
  `resultado` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'RECHAZADO',
  `mensaje` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token_hash` char(64) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`auditoria_admin_id`),
  KEY `idx_empresa_fecha` (`empresa_id`,`fecha_registro`),
  KEY `idx_admin_users` (`admin_users_id`),
  KEY `idx_usuario_sesion` (`usuario_sesion_id`),
  KEY `idx_modulo_accion` (`modulo`,`accion`),
  KEY `idx_permitido` (`permitido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================================
-- 2026-06-20 | CONFIG / CORREO
-- ============================================================================

INSERT INTO `config` (`config_id`,`accion`,`activar`)
SELECT 6,'Activar ISV Proforma',2
WHERE EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='config'
)
AND NOT EXISTS (SELECT 1 FROM `config` WHERE `config_id`=6);

INSERT INTO `config` (`config_id`,`accion`,`activar`)
SELECT 5,'Activar Cobro Proforma',2
WHERE EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='config'
)
AND NOT EXISTS (SELECT 1 FROM `config` WHERE `config_id`=5);

CALL izzy_exec_if_table(
    'correo',
    "UPDATE `correo`
     SET `correo`='izzycloud@esmultiservicios.com',
         `graph_user`='izzycloud@esmultiservicios.com'"
);

-- ============================================================================
-- 2026-06-07 | COTIZACIÓN / ISV
-- ============================================================================

CALL izzy_add_column('cotizacion_detalles','isv_valor1','FLOAT(12,2) NOT NULL DEFAULT 0.00 AFTER `isv_valor`');

INSERT INTO `isv` (`isv_id`,`isv_tipo_id`,`valor`,`activar`,`fecha_registro`)
SELECT 1,1,15.00,1,NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='isv')
AND NOT EXISTS (SELECT 1 FROM `isv` WHERE `isv_id`=1);

INSERT INTO `isv` (`isv_id`,`isv_tipo_id`,`valor`,`activar`,`fecha_registro`)
SELECT 2,1,18.00,1,NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='isv')
AND NOT EXISTS (SELECT 1 FROM `isv` WHERE `isv_id`=2);

INSERT INTO `isv` (`isv_id`,`isv_tipo_id`,`valor`,`activar`,`fecha_registro`)
SELECT 3,2,15.00,1,NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='isv')
AND NOT EXISTS (SELECT 1 FROM `isv` WHERE `isv_id`=3);

INSERT INTO `isv` (`isv_id`,`isv_tipo_id`,`valor`,`activar`,`fecha_registro`)
SELECT 4,2,18.00,1,NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='isv')
AND NOT EXISTS (SELECT 1 FROM `isv` WHERE `isv_id`=4);

-- ============================================================================
-- 2026-06-03 | INVERSIÓN / REPOSICIÓN
-- ============================================================================

CALL izzy_add_column(
    'categoria_gastos','es_inversion',
    "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = No se cuenta como gasto; se clasifica como inversión/reposición'"
);
CALL izzy_add_column(
    'cuentas','es_inversion',
    "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Cuenta usada para inversión/reposición'"
);

CALL izzy_exec_if_table(
    'categoria_gastos',
    "UPDATE `categoria_gastos`
     SET `es_inversion`=1
     WHERE UPPER(`nombre`) LIKE '%INVERSION%'
        OR UPPER(`nombre`) LIKE '%INVERSIÓN%'
        OR UPPER(`nombre`) LIKE '%REPOSICION%'
        OR UPPER(`nombre`) LIKE '%REPOSICIÓN%'"
);

CALL izzy_exec_if_table(
    'cuentas',
    "UPDATE `cuentas`
     SET `es_inversion`=1
     WHERE UPPER(`nombre`) LIKE '%INVERSION%'
        OR UPPER(`nombre`) LIKE '%INVERSIÓN%'
        OR UPPER(`nombre`) LIKE '%REPOSICION%'
        OR UPPER(`nombre`) LIKE '%REPOSICIÓN%'"
);

SET @izzy_can_update_egresos := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='egresos' AND COLUMN_NAME='categoria_gastos_id'
);
SET @izzy_sql := IF(
    @izzy_can_update_egresos > 0
    AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='categoria_gastos' AND COLUMN_NAME='es_inversion'
    ),
    "UPDATE `egresos`
     SET `categoria_gastos_id`=(
        SELECT cg.categoria_gastos_id
        FROM categoria_gastos cg
        WHERE cg.es_inversion=1
        ORDER BY cg.categoria_gastos_id ASC
        LIMIT 1
     )
     WHERE `estado`=1
       AND `tipo_egreso`=2
       AND UPPER(`observacion`) LIKE '%INVERSION%'
       AND EXISTS (SELECT 1 FROM categoria_gastos cg2 WHERE cg2.es_inversion=1)",
    "SELECT 'Egresos de inversión: estructura no disponible; sin cambios' AS resultado"
);
PREPARE izzy_stmt FROM @izzy_sql; EXECUTE izzy_stmt; DEALLOCATE PREPARE izzy_stmt;

-- ============================================================================
-- 2026-06-01 | LIMPIEZA INGRESOS / PIN
-- ============================================================================

CALL izzy_drop_column('ingresos','recibide');

CALL izzy_add_index('pin','idx_pin_login',"INDEX `idx_pin_login` (`codigo_cliente`,`pin`,`fecha_hora_fin`)");
CALL izzy_add_index('pin','idx_pin_cliente',"INDEX `idx_pin_cliente` (`server_customers_id`,`codigo_cliente`,`fecha_hora_fin`)");

-- ============================================================================
-- 2026-05-31 | JOBS QUEUE
-- ============================================================================

CALL izzy_add_column('jobs_queue','error_message','TEXT NULL AFTER `max_attempts`');

-- ============================================================================
-- 2026-05-30 | CORREO GRAPH
-- ============================================================================

CALL izzy_add_column('correo','metodo_envio',"ENUM('SMTP','GRAPH') NOT NULL DEFAULT 'SMTP' AFTER `correo_tipo_id`");
CALL izzy_add_column('correo','tenant_id','VARCHAR(100) NULL AFTER `smtp_secure`');
CALL izzy_add_column('correo','client_id','VARCHAR(100) NULL AFTER `tenant_id`');
CALL izzy_add_column('correo','client_secret','TEXT NULL AFTER `client_id`');
CALL izzy_add_column('correo','graph_user','VARCHAR(150) NULL AFTER `client_secret`');
CALL izzy_add_column('correo','save_to_sent_items','TINYINT(1) NOT NULL DEFAULT 1 AFTER `graph_user`');

-- ============================================================================
-- 2026-05-27 | RETIROS DE CAJA
-- ============================================================================

CREATE TABLE IF NOT EXISTS `caja_retiros` (
  `caja_retiros_id` int NOT NULL AUTO_INCREMENT,
  `apertura_id` int NOT NULL,
  `egresos_id` int NOT NULL,
  `cuentas_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `monto` float(12,2) NOT NULL,
  `motivo` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 0. Anulado',
  `colaboradores_id` int NOT NULL,
  `fecha` date NOT NULL,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`caja_retiros_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================================
-- 2026-05-26 / 2026-05-25 | COSTO UNITARIO
-- ============================================================================

CALL izzy_add_column(
    'facturas_detalles',
    'costo_unitario',
    'DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `precio`'
);

SET @izzy_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='facturas_detalles' AND COLUMN_NAME='costo_unitario'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productos' AND COLUMN_NAME='precio_compra'
    ),
    "UPDATE `facturas_detalles` fd
     INNER JOIN `productos` p ON p.productos_id=fd.productos_id
     SET fd.costo_unitario=p.precio_compra
     WHERE (fd.costo_unitario IS NULL OR fd.costo_unitario=0)
       AND p.precio_compra>0",
    "SELECT 'Costo unitario: estructura no disponible; sin cambios' AS resultado"
);
PREPARE izzy_stmt FROM @izzy_sql; EXECUTE izzy_stmt; DEALLOCATE PREPARE izzy_stmt;

-- ============================================================================
-- 2026-05-17 | CONFIG PROFORMA
-- ============================================================================

INSERT INTO `config` (`config_id`,`accion`,`activar`)
SELECT 3,'Activar Proforma',2
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='config')
AND NOT EXISTS (SELECT 1 FROM `config` WHERE `config_id`=3);

INSERT INTO `config` (`config_id`,`accion`,`activar`)
SELECT 4,'Activar Rebajar Inventario Proforma',2
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='config')
AND NOT EXISTS (SELECT 1 FROM `config` WHERE `config_id`=4);

-- ============================================================================
-- 2025-09-20 / 2025-09-18 | FACTURAS / MOVIMIENTOS
-- ============================================================================

CALL izzy_add_column('facturas_detalles','isv_valor1','FLOAT(12,4) NOT NULL DEFAULT 0.0000 AFTER `isv_valor`');
CALL izzy_change_engine('movimientos','InnoDB');

-- ============================================================================
-- 2025-09-12 | PROMOCIONES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `promociones` (
  `promo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `descripcion` VARCHAR(255) NULL,
  `tipo_descuento` ENUM('PORC','MONTO') NOT NULL,
  `valor` DECIMAL(12,2) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `hora_inicio` TIME NULL,
  `hora_fin` TIME NULL,
  `dias_semana` SET('mon','tue','wed','thu','fri','sat','sun') NULL,
  `prioridad` INT NOT NULL DEFAULT 0,
  `aplica_a` ENUM('PRODUCTO','CATEGORIA','TODOS') NOT NULL,
  `acumula_con_mayoreo` TINYINT(1) NOT NULL DEFAULT 0,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `creado_por` INT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `ix_promos_vigencia` (`empresa_id`,`estado`,`fecha_inicio`,`fecha_fin`,`prioridad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `promo_productos` (
  `promo_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  PRIMARY KEY (`promo_id`,`producto_id`),
  KEY `ix_pp_prod` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL izzy_add_fk('promo_productos','fk_pp_promo',
    'FOREIGN KEY (`promo_id`) REFERENCES `promociones` (`promo_id`) ON DELETE CASCADE'
);
CALL izzy_add_fk('promo_productos','fk_pp_prod',
    'FOREIGN KEY (`producto_id`) REFERENCES `productos` (`productos_id`) ON DELETE CASCADE'
);

CREATE TABLE IF NOT EXISTS `promo_categorias` (
  `promo_id` INT NOT NULL,
  `categoria_id` INT NOT NULL,
  PRIMARY KEY (`promo_id`,`categoria_id`),
  KEY `ix_pc_cat` (`categoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL izzy_add_fk('promo_categorias','fk_pc_promo',
    'FOREIGN KEY (`promo_id`) REFERENCES `promociones` (`promo_id`) ON DELETE CASCADE'
);

-- La vista se puede recrear sin duplicar objetos.
CREATE OR REPLACE VIEW `v_promos_vigentes_producto` AS
SELECT
    pr.promo_id, pr.empresa_id, p.productos_id AS producto_id,
    pr.tipo_descuento, pr.valor, pr.prioridad, pr.acumula_con_mayoreo
FROM promociones pr
JOIN promo_productos pp ON pp.promo_id=pr.promo_id
JOIN productos p ON p.productos_id=pp.producto_id
WHERE pr.estado=1
  AND NOW() BETWEEN pr.fecha_inicio AND pr.fecha_fin
  AND (pr.hora_inicio IS NULL OR pr.hora_fin IS NULL OR TIME(NOW()) BETWEEN pr.hora_inicio AND pr.hora_fin)
  AND (pr.dias_semana IS NULL OR FIND_IN_SET(LOWER(DAYNAME(NOW())),pr.dias_semana)>0)
UNION ALL
SELECT
    pr.promo_id, pr.empresa_id, p.productos_id AS producto_id,
    pr.tipo_descuento, pr.valor, pr.prioridad, pr.acumula_con_mayoreo
FROM promociones pr
JOIN promo_categorias pc ON pc.promo_id=pr.promo_id
JOIN productos p ON p.categoria_id=pc.categoria_id
WHERE pr.estado=1
  AND NOW() BETWEEN pr.fecha_inicio AND pr.fecha_fin
  AND (pr.hora_inicio IS NULL OR pr.hora_fin IS NULL OR TIME(NOW()) BETWEEN pr.hora_inicio AND pr.hora_fin)
  AND (pr.dias_semana IS NULL OR FIND_IN_SET(LOWER(DAYNAME(NOW())),pr.dias_semana)>0)
UNION ALL
SELECT
    pr.promo_id, pr.empresa_id, p.productos_id AS producto_id,
    pr.tipo_descuento, pr.valor, pr.prioridad, pr.acumula_con_mayoreo
FROM promociones pr
JOIN productos p ON p.empresa_id=pr.empresa_id
WHERE pr.estado=1
  AND pr.aplica_a='TODOS'
  AND NOW() BETWEEN pr.fecha_inicio AND pr.fecha_fin
  AND (pr.hora_inicio IS NULL OR pr.hora_fin IS NULL OR TIME(NOW()) BETWEEN pr.hora_inicio AND pr.hora_fin)
  AND (pr.dias_semana IS NULL OR FIND_IN_SET(LOWER(DAYNAME(NOW())),pr.dias_semana)>0);

-- ============================================================================
-- 2025-09-11 / 2025-09-09 | RESTAURANTE / COMBOS
-- ============================================================================

CALL izzy_add_column(
    'factura_comanda','servicio_tipo',
    "ENUM('mesa','llevar') NOT NULL DEFAULT 'llevar' AFTER `estado`"
);
CALL izzy_add_column(
    'categoria','estacion',
    "ENUM('ninguna','cocina','barra') NOT NULL DEFAULT 'ninguna' AFTER `nombre`"
);

CALL izzy_change_engine('productos','InnoDB');
CALL izzy_change_engine('categoria','InnoDB');

CREATE TABLE IF NOT EXISTS `combos` (
  `combo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `productos_id` INT NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `precio_venta` DECIMAL(12,2) NULL,
  `version_actual` INT NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_combo_producto` (`productos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_fk('combos','fk_combos_producto_padre',
    'FOREIGN KEY (`productos_id`) REFERENCES `productos` (`productos_id`) ON UPDATE CASCADE ON DELETE RESTRICT'
);

CREATE TABLE IF NOT EXISTS `combo_detalle` (
  `combo_detalle_id` INT AUTO_INCREMENT PRIMARY KEY,
  `combo_id` INT NOT NULL,
  `productos_id` INT NOT NULL,
  `cantidad_por_porcion` DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
  `unidad` VARCHAR(20) NULL,
  `merma_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `obligatorio` TINYINT(1) NOT NULL DEFAULT 1,
  `precio_extra` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `version` INT NOT NULL DEFAULT 1,
  `orden` SMALLINT NOT NULL DEFAULT 1,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_detalle_combo` (`combo_id`),
  KEY `idx_detalle_producto` (`productos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_fk('combo_detalle','fk_detalle_combo',
    'FOREIGN KEY (`combo_id`) REFERENCES `combos` (`combo_id`) ON UPDATE CASCADE ON DELETE CASCADE'
);
CALL izzy_add_fk('combo_detalle','fk_detalle_producto_hijo',
    'FOREIGN KEY (`productos_id`) REFERENCES `productos` (`productos_id`) ON UPDATE CASCADE ON DELETE RESTRICT'
);

CREATE TABLE IF NOT EXISTS `combo_categoria_regla` (
  `combo_categoria_regla_id` INT AUTO_INCREMENT PRIMARY KEY,
  `combo_id` INT NOT NULL,
  `categoria_id` INT NOT NULL,
  `max_seleccion` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_combo_categoria` (`combo_id`,`categoria_id`),
  KEY `idx_ccr_combo` (`combo_id`),
  KEY `idx_ccr_categoria` (`categoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_fk('combo_categoria_regla','fk_ccr_combo',
    'FOREIGN KEY (`combo_id`) REFERENCES `combos` (`combo_id`) ON UPDATE CASCADE ON DELETE CASCADE'
);
CALL izzy_add_fk('combo_categoria_regla','fk_ccr_categoria',
    'FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`categoria_id`) ON UPDATE CASCADE ON DELETE RESTRICT'
);

-- ============================================================================
-- 2025-09-05 | PRODUCTOS RESTAURANTE / ISV
-- ============================================================================

CALL izzy_add_column('productos','restaurante','TINYINT NOT NULL DEFAULT 0');
CALL izzy_add_column('productos','isv1','TINYINT NOT NULL DEFAULT 0');
CALL izzy_add_column('productos','isv2','TINYINT NOT NULL DEFAULT 0');

CALL izzy_add_index('productos','idx_productos_restaurante',"INDEX `idx_productos_restaurante` (`restaurante`)");
CALL izzy_add_index('productos','idx_productos_isv1',"INDEX `idx_productos_isv1` (`isv1`)");
CALL izzy_add_index('productos','idx_productos_isv2',"INDEX `idx_productos_isv2` (`isv2`)");

-- ============================================================================
-- 2025-08-24 | ACCESO SUBMENU1
-- ============================================================================
-- Solo normaliza AUTO_INCREMENT si la columna ya existe.
SET @izzy_col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='acceso_submenu1' AND COLUMN_NAME='acceso_submenu1_id'
);
SET @izzy_sql := IF(
    @izzy_col_exists>0,
    'ALTER TABLE `acceso_submenu1` MODIFY `acceso_submenu1_id` INT(11) NOT NULL AUTO_INCREMENT',
    'SELECT ''acceso_submenu1_id no existe; sin cambios'' AS resultado'
);
PREPARE izzy_stmt FROM @izzy_sql; EXECUTE izzy_stmt; DEALLOCATE PREPARE izzy_stmt;

-- ============================================================================
-- 2025-08-22 | PAGOS - TRAZABILIDAD CONTABLE
-- ============================================================================

CALL izzy_add_column('pagos','contabilizado','TINYINT(1) NOT NULL DEFAULT 0');
CALL izzy_add_column('pagos','referencia_ingreso_id','INT NULL');

-- ============================================================================
-- 2025-05-18 / 2025-05-17 | FACTURAS / CxC
-- ============================================================================

CALL izzy_add_column('facturas','no_orden','VARCHAR(50) NULL AFTER `fecha_dolar`');
CALL izzy_add_column('facturas','constancia','VARCHAR(50) NULL AFTER `no_orden`');
CALL izzy_add_column('facturas','identificativo_sag','VARCHAR(50) NULL AFTER `constancia`');
CALL izzy_add_column('facturas','numero_interno','VARCHAR(50) NULL AFTER `identificativo_sag`');
CALL izzy_add_column(
    'cobrar_clientes','tipo_factura',
    "INT NOT NULL DEFAULT 2 COMMENT '1=Contado, 2=Crédito' AFTER `estado`"
);

-- ============================================================================
-- HISTÓRICO 2024-2022 | CONVERSIÓN DE NOTAS DEL TXT A OPERACIONES SEGURAS
-- ============================================================================

CALL izzy_add_column('apertura','empresa_id','INT NULL AFTER `fecha_registro`');

CREATE TABLE IF NOT EXISTS `menu_plan` (
  `menu_plan_id` INT NOT NULL AUTO_INCREMENT,
  `menu_id` INT NULL,
  `planes_id` INT NOT NULL,
  PRIMARY KEY (`menu_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `submenu_plan` (
  `submenu_plan_id` INT NOT NULL AUTO_INCREMENT,
  `submenu_id` INT NOT NULL,
  `planes_id` INT NOT NULL,
  PRIMARY KEY (`submenu_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `submenu1_plan` (
  `submenu1_plan_id` INT NOT NULL AUTO_INCREMENT,
  `submenu1_id` INT NOT NULL,
  `planes_id` INT NOT NULL,
  PRIMARY KEY (`submenu1_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column(
    'contrato','semanal',
    "INT NOT NULL DEFAULT 0 COMMENT '0. No 1. Sí' AFTER `fecha_registro`"
);

CREATE TABLE IF NOT EXISTS `notificaciones` (
  `notificaciones_id` INT NOT NULL AUTO_INCREMENT,
  `correo` CHAR(100) NOT NULL,
  `nombre` CHAR(100) NOT NULL,
  PRIMARY KEY (`notificaciones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column('compras','cuentas_id','INT NULL AFTER `fecha_registro`');
CALL izzy_add_column('compras','recordatorio','INT NOT NULL DEFAULT 0 AFTER `cuentas_id`');

CALL izzy_add_column('nomina_detalles','salario_mensual','DECIMAL(12,2) NOT NULL DEFAULT 0.00');
CALL izzy_add_column('nomina_detalles','hrse25_valor','DECIMAL(12,2) NOT NULL DEFAULT 0.00');
CALL izzy_add_column('nomina_detalles','hrse50_valor','DECIMAL(12,2) NOT NULL DEFAULT 0.00');
CALL izzy_add_column('nomina_detalles','hrse75_valor','DECIMAL(12,2) NOT NULL DEFAULT 0.00');
CALL izzy_add_column('nomina_detalles','hrse100_valor','DECIMAL(12,2) NOT NULL DEFAULT 0.00');
CALL izzy_add_column('nomina_detalles','salario','DECIMAL(12,2) NOT NULL DEFAULT 0.00');

-- 'vales' pertenece a nomina_detalles (ya se gestiona arriba); no a nomina.
CALL izzy_add_column('nomina','cuentas_id','INT NULL AFTER `fecha_registro`');

CREATE TABLE IF NOT EXISTS `vale` (
  `vale_id` INT NOT NULL AUTO_INCREMENT,
  `nomina_id` INT NOT NULL,
  `colaboradores_id` INT NOT NULL,
  `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fecha` DATE NOT NULL,
  `nota` VARCHAR(254) NULL,
  `usuario` INT NOT NULL,
  `estado` INT NOT NULL DEFAULT 1,
  `empresa_id` INT NOT NULL,
  `fecha_registro` DATETIME NOT NULL,
  PRIMARY KEY (`vale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `categoria_gastos` (
  `categoria_gastos_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) NOT NULL,
  `estado` INT NOT NULL DEFAULT 1,
  `usuario` INT NOT NULL,
  `date_write` DATETIME NOT NULL,
  PRIMARY KEY (`categoria_gastos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column('egresos','categoria_gastos_id','INT NULL AFTER `fecha_registro`');

CREATE TABLE IF NOT EXISTS `config` (
  `config_id` INT NOT NULL,
  `accion` CHAR(40) NOT NULL,
  `activar` INT NOT NULL COMMENT '1. Si 2. No',
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `pin` (
  `pin_id` INT NOT NULL AUTO_INCREMENT,
  `server_customers_id` INT NOT NULL,
  `codigo_cliente` INT NOT NULL,
  `pin` INT NOT NULL,
  `fecha_hora_inicio` DATETIME NOT NULL,
  `fecha_hora_fin` DATETIME NOT NULL,
  PRIMARY KEY (`pin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column('server_customers','codigo_cliente','INT NULL AFTER `clientes_id`');
CALL izzy_add_column('users','server_customers_id','INT NULL AFTER `empresa_id`');

CREATE TABLE IF NOT EXISTS `sistema` (
  `sistema_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` CHAR(30) NOT NULL,
  `estado` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`sistema_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `planes` (
  `planes_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` CHAR(40) NOT NULL,
  `usuarios` INT NOT NULL DEFAULT 0,
  `estado` INT NOT NULL DEFAULT 1,
  `fecha_registro` DATETIME NOT NULL,
  PRIMARY KEY (`planes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column('server_customers','planes_id','INT NULL');
CALL izzy_add_column('server_customers','sistema_id','INT NULL');
CALL izzy_add_column('server_customers','estado','INT NOT NULL DEFAULT 1');
CALL izzy_add_column('plan','planes_id','INT NULL');

CALL izzy_add_column('clientes','empresa','CHAR(30) NULL');
CALL izzy_add_column('clientes','eslogan','CHAR(50) NULL');
CALL izzy_add_column('clientes','otra_informacion','CHAR(50) NULL');
CALL izzy_add_column('clientes','whatsapp','CHAR(8) NULL');

-- Asistencia: si la tabla ya existe, agrega únicamente lo que falte.
CALL izzy_add_column('asistencia','historial_id','INT NULL');
CALL izzy_add_column('asistencia','modulo','CHAR(30) NULL');
CALL izzy_add_column('asistencia','colaboradores_id','INT NULL');
CALL izzy_add_column('asistencia','status','CHAR(10) NULL');
CALL izzy_add_column('asistencia','observacion','CHAR(254) NULL');
CALL izzy_add_column('asistencia','fecha_registro','DATETIME NULL');
CALL izzy_add_column('asistencia','fecha','DATE NULL');
CALL izzy_add_column('asistencia','hora_entrada','TIME NULL');
CALL izzy_add_column('asistencia','hora_salida','TIME NULL');
CALL izzy_add_column('asistencia','estado','INT NOT NULL DEFAULT 1');

-- El TXT histórico listaba dos tipos para "fecha_ingreso". Se conserva la
-- columna si existe; si falta se agrega como DATE, que es el tipo semántico.
CALL izzy_add_column('colaboradores','fecha_ingreso','DATE NULL');

CREATE TABLE IF NOT EXISTS `tipo_contrato` (
  `tipo_contrato_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`tipo_contrato_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `pago_planificado` (
  `pago_planificado_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`pago_planificado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `tipo_empleado` (
  `tipo_empleado_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) NOT NULL,
  PRIMARY KEY (`tipo_empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La tabla contrato ya forma parte del esquema actual. Solo completa columnas.
CALL izzy_add_column('contrato','colaborador_id','INT NULL');
CALL izzy_add_column('contrato','tipo_contrato_id','INT NULL');
CALL izzy_add_column('contrato','pago_planificado_id','INT NULL');
CALL izzy_add_column('contrato','salario','DECIMAL(12,2) NOT NULL DEFAULT 0.00');
CALL izzy_add_column('contrato','fecha_inicio','DATE NULL');
CALL izzy_add_column('contrato','fecha_fin','VARCHAR(30) NULL');
CALL izzy_add_column('contrato','notas','VARCHAR(256) NULL');
CALL izzy_add_column('contrato','usuario','INT NULL');
CALL izzy_add_column('contrato','estado','INT NOT NULL DEFAULT 1');
CALL izzy_add_column('contrato','fecha_registro','DATETIME NULL');

-- Movimientos históricos.
CALL izzy_add_column('movimientos','clientes_id','INT NULL');
CALL izzy_add_column('movimientos','comentario','CHAR(255) NULL');
CALL izzy_add_column('movimientos','almacen_id','INT NULL');

-- Productos históricos.
CALL izzy_add_column('productos','id_producto_superior','INT NULL');
CALL izzy_add_column('productos','barCode','VARCHAR(256) NULL');

-- Documento / secuencia.
CREATE TABLE IF NOT EXISTS `documento` (
  `documento_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` CHAR(30) NOT NULL,
  `estado` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`documento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CALL izzy_add_column('secuencia_facturacion','documento_id','INT NULL AFTER `fecha_registro`');

-- Eliminar columnas que el histórico marcó como retiradas.
CALL izzy_drop_column('productos','cantidad');
CALL izzy_drop_column('secuencia_facturacion','comentario');

-- Corrección histórica puntual del menú: solo si el registro existe.
CALL izzy_exec_if_table(
    'submenu',
    "UPDATE `submenu` SET `menu_id`=8 WHERE `submenu_id`=16 AND `menu_id`=7"
);

-- ============================================================================
-- VERIFICACIÓN FINAL NOTA DE CRÉDITO
-- ============================================================================

SELECT
    CASE
      WHEN EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='documento'
      )
      AND EXISTS (
        SELECT 1 FROM `documento`
        WHERE `documento_id`=2 AND `estado`=1
      )
      THEN 'OK'
      ELSE 'FALTA/INACTIVO'
    END AS documento_nota_credito,
    CASE
      WHEN EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='secuencia_facturacion'
      )
      AND EXISTS (
        SELECT 1 FROM `secuencia_facturacion`
        WHERE `documento_id`=2 AND `activo`=1
      )
      THEN 'OK'
      ELSE 'SIN SECUENCIA ACTIVA'
    END AS secuencia_nota_credito;

-- ============================================================================
-- LIMPIEZA DE HELPERS
-- ============================================================================

DROP PROCEDURE IF EXISTS izzy_add_column;
DROP PROCEDURE IF EXISTS izzy_add_index;
DROP PROCEDURE IF EXISTS izzy_add_fk;
DROP PROCEDURE IF EXISTS izzy_drop_column;
DROP PROCEDURE IF EXISTS izzy_change_engine;
DROP PROCEDURE IF EXISTS izzy_exec_if_table;


-- ============================================================================
-- NOTAS DE CRÉDITO: FORMATOS DE IMPRESIÓN CARTA Y TICKET
-- IDs y tipos reservados 6/7. Mantener solo una opción activa.
-- Script idempotente: conserva las selecciones existentes al volver a ejecutarse.
-- ============================================================================
INSERT INTO `impresora` (`impresora_id`, `descripcion`, `estado`, `tipo`, `fecha_registro`)
SELECT 6, 'Nota de Crédito Carta', 1, 6, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `impresora` WHERE `tipo` = 6);
INSERT INTO `impresora` (`impresora_id`, `descripcion`, `estado`, `tipo`, `fecha_registro`)
SELECT 7, 'Nota de Crédito Ticket', 0, 7, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `impresora` WHERE `tipo` = 7);

-- FIN DB_Cambios.sql
