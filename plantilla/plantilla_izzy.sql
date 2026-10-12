-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 12, 2026 at 03:03 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `plantilla_izzy`
--

-- --------------------------------------------------------

--
-- Table structure for table `acceso_menu`
--

CREATE TABLE `acceso_menu` (
  `acceso_menu_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `privilegio_id` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Mostrar 0. Ocultar',
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Guarda los accesos de usuarios al menú principal según privilegios';

--
-- Dumping data for table `acceso_menu`
--

INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
(1, 1, 1, 1, '2025-06-30 14:46:22'),
(2, 2, 1, 1, '2025-06-30 14:26:01'),
(3, 3, 1, 1, '2025-06-30 14:26:01'),
(4, 4, 1, 1, '2025-06-30 14:26:01'),
(6, 6, 1, 1, '2025-06-30 14:26:01'),
(7, 7, 1, 1, '2025-06-30 14:26:01'),
(5, 5, 1, 1, '2025-06-30 14:26:01'),
(38, 8, 2, 1, '2025-06-30 12:37:38'),
(37, 5, 2, 1, '2025-06-30 12:37:37'),
(36, 7, 2, 1, '2025-06-30 12:37:38'),
(35, 6, 2, 1, '2025-06-30 12:37:37'),
(34, 4, 2, 1, '2025-06-30 12:37:36'),
(33, 3, 2, 1, '2025-06-30 12:37:36'),
(29, 8, 1, 1, '2025-06-30 17:03:55'),
(32, 2, 2, 1, '2025-06-30 12:37:35'),
(31, 1, 2, 1, '2025-06-30 20:45:00'),
(30, 2, 3, 1, '2025-06-30 21:05:00'),
(39, 1, 4, 0, '2025-06-30 20:07:30'),
(40, 2, 4, 0, '2025-06-30 17:31:56'),
(41, 9, 1, 1, '2025-06-30 08:20:43'),
(42, 10, 1, 1, '2025-06-30 15:20:11'),
(50, 1, 5, 1, '2025-08-24 10:06:40'),
(49, 5, 5, 1, '2025-08-24 10:04:29'),
(48, 7, 5, 1, '2025-08-24 10:04:28'),
(47, 6, 5, 1, '2025-08-24 10:04:27'),
(51, 12, 2, 1, '2025-09-05 03:48:45'),
(52, 11, 2, 1, '2025-09-05 03:48:46'),
(53, 12, 1, 1, '2025-09-05 03:48:51'),
(54, 11, 1, 1, '2025-09-05 03:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `acceso_submenu`
--

CREATE TABLE `acceso_submenu` (
  `acceso_submenu_id` int NOT NULL,
  `submenu_id` int NOT NULL,
  `privilegio_id` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Mostrar 0. Ocultar',
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Guarda los accesos de usuarios al submenú de nivel 1 según privilegios';

--
-- Dumping data for table `acceso_submenu`
--

INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
(1, 1, 1, 1, '2025-06-30 14:26:01'),
(2, 2, 1, 1, '2025-06-30 14:26:01'),
(3, 3, 1, 1, '2025-06-30 14:26:01'),
(4, 4, 1, 1, '2025-06-30 14:26:01'),
(5, 5, 1, 1, '2025-06-30 14:26:01'),
(6, 6, 1, 1, '2025-06-30 14:26:01'),
(7, 7, 1, 1, '2025-06-30 14:26:01'),
(8, 8, 1, 1, '2025-06-30 04:04:41'),
(9, 9, 1, 1, '2025-06-30 04:04:41'),
(10, 10, 1, 1, '2025-06-30 04:04:41'),
(11, 11, 1, 1, '2025-06-30 04:04:41'),
(12, 12, 1, 1, '2025-06-30 04:04:41'),
(13, 13, 1, 1, '2025-06-30 14:26:01'),
(14, 14, 1, 1, '2025-06-30 14:26:01'),
(15, 15, 1, 1, '2025-06-30 14:26:01'),
(16, 16, 1, 1, '2025-06-30 14:26:01'),
(17, 17, 1, 1, '2025-06-30 14:26:01'),
(18, 18, 1, 1, '2025-06-30 14:26:01'),
(19, 19, 1, 1, '2025-06-30 14:26:01'),
(20, 20, 1, 1, '2025-06-30 14:26:01'),
(21, 21, 1, 1, '2025-06-30 14:26:01'),
(22, 22, 1, 1, '2025-06-30 14:26:01'),
(23, 23, 1, 1, '2025-06-30 14:26:01'),
(24, 24, 1, 1, '2025-06-30 14:26:01'),
(25, 25, 1, 1, '2025-06-30 14:26:01'),
(51, 27, 1, 1, '2025-06-30 12:05:04'),
(52, 26, 1, 1, '2025-06-30 12:05:04'),
(53, 28, 1, 1, '2025-06-30 06:48:48'),
(54, 30, 1, 1, '2025-06-30 06:48:48'),
(55, 31, 1, 1, '2025-06-30 06:48:48'),
(56, 32, 1, 1, '2025-06-30 06:48:48'),
(57, 29, 1, 1, '2025-06-30 06:48:48'),
(174, 34, 2, 1, '2025-06-30 10:19:30'),
(173, 29, 2, 1, '2025-06-30 09:08:12'),
(172, 26, 2, 1, '2025-06-30 09:08:05'),
(171, 25, 2, 1, '2025-06-30 09:07:58'),
(170, 24, 2, 1, '2025-06-30 09:07:51'),
(169, 23, 2, 1, '2025-06-30 09:07:36'),
(168, 22, 2, 1, '2025-06-30 09:07:27'),
(167, 21, 2, 1, '2025-06-30 09:07:18'),
(166, 20, 2, 1, '2025-06-30 09:07:12'),
(165, 19, 2, 1, '2025-06-30 09:07:06'),
(164, 18, 2, 1, '2025-06-30 09:07:00'),
(163, 32, 2, 1, '2025-06-30 09:06:45'),
(162, 31, 2, 1, '2025-06-30 09:06:38'),
(161, 30, 2, 1, '2025-06-30 09:06:32'),
(160, 28, 2, 1, '2025-06-30 09:06:10'),
(159, 12, 2, 1, '2025-06-30 09:05:59'),
(158, 11, 2, 1, '2025-06-30 09:05:47'),
(157, 10, 2, 1, '2025-06-30 09:05:40'),
(156, 9, 2, 1, '2025-06-30 09:04:55'),
(155, 8, 2, 1, '2025-06-30 09:03:11'),
(154, 17, 2, 1, '2025-06-30 08:35:55'),
(153, 36, 2, 1, '2025-06-30 08:35:44'),
(152, 35, 2, 1, '2025-06-30 08:35:36'),
(151, 16, 2, 1, '2025-06-30 08:35:30'),
(150, 15, 2, 1, '2025-06-30 08:35:24'),
(149, 14, 2, 1, '2025-06-30 08:34:46'),
(148, 13, 2, 1, '2025-06-30 08:34:34'),
(147, 33, 2, 1, '2025-06-30 08:34:25'),
(146, 7, 2, 1, '2025-06-30 08:34:19'),
(129, 33, 1, 1, '2025-06-30 17:36:13'),
(130, 34, 1, 1, '2025-06-30 17:36:13'),
(145, 6, 2, 1, '2025-06-30 08:34:13'),
(144, 5, 2, 1, '2025-06-30 08:34:06'),
(133, 35, 1, 1, '2025-06-30 17:36:13'),
(134, 36, 1, 1, '2025-06-30 17:36:13'),
(143, 4, 2, 1, '2025-06-30 08:34:00'),
(142, 27, 2, 1, '2025-06-30 08:33:55'),
(137, 37, 1, 1, '2025-06-30 11:55:25'),
(141, 3, 2, 1, '2025-06-30 08:33:49'),
(140, 2, 2, 0, '2025-06-30 20:44:56'),
(139, 1, 2, 0, '2025-06-30 12:24:10'),
(138, 1, 3, 1, '2025-06-30 21:05:14'),
(178, 1, 4, 1, '2025-06-30 21:17:14'),
(179, 38, 1, 1, '2025-06-30 08:31:52'),
(180, 39, 1, 1, '2025-06-30 08:31:52'),
(181, 40, 1, 1, '2025-06-30 08:31:52'),
(182, 41, 1, 1, '2025-06-30 08:31:52'),
(183, 42, 1, 1, '2025-06-30 18:17:04'),
(199, 11, 5, 1, '2025-08-24 10:04:53'),
(198, 10, 5, 1, '2025-08-24 10:04:53'),
(197, 9, 5, 1, '2025-08-24 10:04:52'),
(196, 8, 5, 1, '2025-08-24 10:04:51'),
(195, 20, 5, 1, '2025-08-24 10:04:47'),
(194, 19, 5, 1, '2025-08-24 10:04:46'),
(193, 15, 5, 1, '2025-08-24 10:04:43'),
(192, 14, 5, 1, '2025-08-24 10:04:42'),
(200, 28, 5, 1, '2025-08-24 10:04:55'),
(201, 31, 5, 1, '2025-08-24 10:04:56'),
(202, 32, 5, 1, '2025-08-24 10:04:56'),
(203, 12, 5, 1, '2025-08-24 10:05:00'),
(204, 30, 5, 1, '2025-08-24 10:05:01'),
(205, 42, 2, 1, '2025-09-05 02:39:15');

-- --------------------------------------------------------

--
-- Table structure for table `acceso_submenu1`
--

CREATE TABLE `acceso_submenu1` (
  `acceso_submenu1_id` int NOT NULL,
  `submenu1_id` int NOT NULL,
  `privilegio_id` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Mostrar 0. Ocultar',
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Guarda los accesos de usuarios al submenú de nivel 2 según privilegios';

--
-- Dumping data for table `acceso_submenu1`
--

INSERT INTO `acceso_submenu1` (`acceso_submenu1_id`, `submenu1_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
(1, 1, 1, 1, '2025-06-30 14:26:01'),
(2, 2, 1, 1, '2025-06-30 14:26:01'),
(3, 3, 1, 1, '2025-06-30 14:26:01'),
(4, 4, 1, 1, '2025-06-30 14:26:01'),
(5, 5, 1, 1, '2025-06-30 14:26:01'),
(6, 6, 1, 1, '2025-06-30 14:26:01'),
(31, 1, 2, 1, '2025-06-30 17:40:59'),
(32, 2, 2, 1, '2025-06-30 12:37:00'),
(15, 3, 2, 1, '2025-06-30 12:37:01'),
(16, 4, 2, 1, '2025-06-30 12:37:02'),
(30, 6, 2, 1, '2025-06-30 12:37:03'),
(29, 5, 2, 1, '2025-06-30 12:37:02'),
(20, 7, 2, 1, '2025-06-30 12:37:04'),
(21, 7, 1, 1, '2025-06-30 15:31:55'),
(39, 29, 2, 0, '2025-06-30 12:35:34'),
(38, 16, 2, 0, '2025-06-30 12:35:14'),
(37, 15, 2, 0, '2025-06-30 12:33:56'),
(36, 32, 2, 0, '2025-06-30 12:33:11'),
(33, 13, 2, 0, '2025-06-30 12:21:57'),
(34, 22, 2, 0, '2025-06-30 12:23:59'),
(35, 31, 2, 0, '2025-06-30 12:25:13'),
(47, 6, 5, 1, '2025-08-24 10:05:15'),
(46, 5, 5, 1, '2025-08-24 10:05:15'),
(45, 4, 5, 1, '2025-08-24 10:05:14'),
(44, 3, 5, 1, '2025-08-24 10:05:13');

-- --------------------------------------------------------

--
-- Table structure for table `almacen`
--

CREATE TABLE `almacen` (
  `almacen_id` int NOT NULL,
  `ubicacion_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `empresa_id` int NOT NULL,
  `facturar_cero` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `almacen`
--

INSERT INTO `almacen` (`almacen_id`, `ubicacion_id`, `nombre`, `estado`, `empresa_id`, `facturar_cero`, `fecha_registro`) VALUES
(1, 1, 'Almacén Principal', 1, 1, 1, '2025-06-30 15:26:06');

-- --------------------------------------------------------

--
-- Table structure for table `apertura`
--

CREATE TABLE `apertura` (
  `apertura_id` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha` date NOT NULL,
  `factura_inicial` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `factura_final` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `apertura` float(12,2) NOT NULL COMMENT 'Monto de Apertura',
  `neto` float(12,2) NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
  `fecha_registro` datetime NOT NULL,
  `empresa_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asistencia`
--

CREATE TABLE `asistencia` (
  `asistencia_id` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha` date NOT NULL,
  `horai` time NOT NULL,
  `horaf` time DEFAULT NULL,
  `comentario` varchar(254) COLLATE utf8mb4_spanish_ci DEFAULT '',
  `estado` int NOT NULL COMMENT '0. Sin pagar 1. Pagado',
  `fecha_registro` datetime NOT NULL,
  `historial_id` int DEFAULT NULL,
  `modulo` char(30) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `status` char(10) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `observacion` char(254) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_admin_autorizaciones`
--

CREATE TABLE `auditoria_admin_autorizaciones` (
  `auditoria_admin_id` int NOT NULL,
  `usuario_sesion_id` int DEFAULT NULL COMMENT 'Usuario que estaba logueado cuando se pidió autorización',
  `usuario_sesion_tipo_id` int DEFAULT NULL COMMENT 'Tipo de usuario logueado',
  `admin_users_id` int DEFAULT NULL COMMENT 'Usuario administrador que autorizó',
  `admin_tipo_user_id` int DEFAULT NULL COMMENT 'Tipo de usuario administrador',
  `empresa_id` int NOT NULL DEFAULT '0',
  `modulo` varchar(80) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Sistema',
  `accion` varchar(120) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Validación administrativa',
  `referencia_id` varchar(80) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'ID de factura, compra, cotización u otro registro',
  `referencia_texto` varchar(180) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Número/documento/descripción visible',
  `motivo` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `usuario_ingresado` varchar(80) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Usuario escrito en el modal',
  `permitido` tinyint NOT NULL DEFAULT '0' COMMENT '1 autorizado, 0 rechazado',
  `resultado` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'RECHAZADO',
  `mensaje` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token_hash` char(64) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Hash del token generado, no guarda el token real',
  `ip` varchar(45) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banco`
--

CREATE TABLE `banco` (
  `banco_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `banco`
--

INSERT INTO `banco` (`banco_id`, `nombre`, `estado`, `fecha_registro`) VALUES
(0, 'Sin Banco', 1, '2025-06-30 14:56:40'),
(1, 'Ficohsa', 1, '2025-06-30 14:56:40'),
(2, 'BAC', 1, '2025-06-30 14:56:40'),
(3, 'Occidente', 1, '2025-06-30 14:56:40'),
(4, 'Lafise', 1, '2025-06-30 14:56:40'),
(5, 'Promerica', 1, '2025-06-30 14:56:40'),
(6, 'Bancafe', 1, '2025-06-30 14:56:40'),
(7, 'Banpais', 1, '2025-06-30 14:56:40'),
(8, 'Banrural', 1, '2025-06-30 14:56:40'),
(9, 'Banco Popular', 1, '2025-06-30 14:56:40'),
(10, 'Cooperativa Elga', 1, '2025-06-30 14:56:40');

-- --------------------------------------------------------

--
-- Table structure for table `bitacora`
--

CREATE TABLE `bitacora` (
  `bitacora_id` int NOT NULL,
  `bitacoraCodigo` char(9) COLLATE utf8mb4_spanish_ci NOT NULL,
  `bitacoraFecha` date NOT NULL,
  `bitacoraHoraInicio` time NOT NULL,
  `bitacoraHoraFinal` char(13) COLLATE utf8mb4_spanish_ci NOT NULL,
  `bitacoraTipo` int NOT NULL,
  `bitacoraYear` char(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `caja_retiros`
--

CREATE TABLE `caja_retiros` (
  `caja_retiros_id` int NOT NULL,
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
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categoria`
--

CREATE TABLE `categoria` (
  `categoria_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estacion` enum('ninguna','cocina','barra') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'ninguna',
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `categoria`
--

INSERT INTO `categoria` (`categoria_id`, `nombre`, `estacion`, `estado`, `fecha_registro`) VALUES
(1, 'Servicios Informáticos', 'ninguna', 1, '2025-06-30 18:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `categoria_gastos`
--

CREATE TABLE `categoria_gastos` (
  `categoria_gastos_id` int NOT NULL,
  `nombre` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL COMMENT '0 Inactivo 1. Activo',
  `usuario` int NOT NULL,
  `date_write` datetime NOT NULL,
  `es_inversion` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = No se cuenta como gasto; se clasifica como inversión/reposición'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `categoria_gastos`
--

INSERT INTO `categoria_gastos` (`categoria_gastos_id`, `nombre`, `estado`, `usuario`, `date_write`, `es_inversion`) VALUES
(1, 'Alimentacion', 1, 1, '2025-06-30 04:37:59', 0),
(2, 'Combustible', 1, 1, '2025-06-30 04:44:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `cheque`
--

CREATE TABLE `cheque` (
  `cheque_id` int NOT NULL,
  `numero_cheque` varchar(30) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `cuentas_id` int NOT NULL,
  `proveedores_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `tipo_pago_id` int NOT NULL DEFAULT '4',
  `egresos_id` int DEFAULT NULL,
  `categoria_gastos_id` int DEFAULT NULL,
  `movimientos_cuentas_id` int DEFAULT NULL,
  `movimiento_reintegro_id` int DEFAULT NULL,
  `fecha` date NOT NULL,
  `factura` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT '',
  `importe` float(12,2) NOT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT '',
  `estado` tinyint NOT NULL DEFAULT '1' COMMENT '1 Activo / 0 Anulado',
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `fecha_anulacion` datetime DEFAULT NULL,
  `colaboradores_anula_id` int DEFAULT NULL,
  `motivo_anulacion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `clientes_id` int NOT NULL,
  `nombre` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `rtn` char(14) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha` date NOT NULL,
  `departamentos_id` int NOT NULL,
  `municipios_id` int NOT NULL,
  `localidad` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `telefono` char(8) COLLATE utf8mb4_spanish_ci NOT NULL,
  `correo` char(70) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `empresa` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `eslogan` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `otra_informacion` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `whatsapp` char(8) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`clientes_id`, `nombre`, `rtn`, `fecha`, `departamentos_id`, `municipios_id`, `localidad`, `telefono`, `correo`, `estado`, `colaboradores_id`, `fecha_registro`, `empresa`, `eslogan`, `otra_informacion`, `whatsapp`) VALUES
(1, 'Consumidor Final', '999999999', '2025-06-30', 5, 68, '.', '0', 'alguien@algo.com', 1, 1, '2025-06-30 11:28:13', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `cobrar_clientes`
--

CREATE TABLE `cobrar_clientes` (
  `cobrar_clientes_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `facturas_id` int NOT NULL,
  `fecha` date NOT NULL,
  `saldo` float(12,4) NOT NULL,
  `estado` int NOT NULL COMMENT '1. Pendiente de Cobrar 2. Pago Realizado',
  `tipo_factura` int NOT NULL DEFAULT '2' COMMENT '1=Contado, 2=Crédito',
  `usuario` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `colaboradores`
--

CREATE TABLE `colaboradores` (
  `colaboradores_id` int NOT NULL,
  `puestos_id` int NOT NULL,
  `nombre` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `identidad` char(13) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
  `telefono` char(8) COLLATE utf8mb4_spanish_ci NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_egreso` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `colaboradores`
--

INSERT INTO `colaboradores` (`colaboradores_id`, `puestos_id`, `nombre`, `identidad`, `estado`, `telefono`, `empresa_id`, `fecha_registro`, `fecha_ingreso`, `fecha_egreso`) VALUES
(1, 2, 'ES MULTISERVICIOS', '', 1, '', 1, '2021-08-14 17:37:21', '0000-00-00', '');

-- --------------------------------------------------------

--
-- Table structure for table `combos`
--

CREATE TABLE `combos` (
  `combo_id` int NOT NULL COMMENT 'Identificador único del combo',
  `productos_id` int NOT NULL COMMENT 'Producto padre en la tabla productos',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado del combo (1=activo, 0=inactivo)',
  `precio_venta` decimal(12,2) DEFAULT NULL COMMENT 'Si es NULL, usa el precio del producto padre',
  `version_actual` int NOT NULL DEFAULT '1' COMMENT 'Número de versión de la receta vigente',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última fecha de actualización'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Tabla combos: guarda los productos que son combos y que se venden como un todo';

-- --------------------------------------------------------

--
-- Table structure for table `combo_categoria_regla`
--

CREATE TABLE `combo_categoria_regla` (
  `combo_categoria_regla_id` int NOT NULL COMMENT 'Identificador único de la regla de categoría',
  `combo_id` int NOT NULL COMMENT 'Referencia al combo (padre)',
  `categoria_id` int NOT NULL COMMENT 'Categoría de los productos hijos (ej: Bebidas)',
  `max_seleccion` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Número máximo de items seleccionables de esta categoría',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación de la regla'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Tabla combo_categoria_regla: guarda las restricciones por categoría dentro de un combo';

-- --------------------------------------------------------

--
-- Table structure for table `combo_detalle`
--

CREATE TABLE `combo_detalle` (
  `combo_detalle_id` int NOT NULL COMMENT 'Identificador único del detalle del combo',
  `combo_id` int NOT NULL COMMENT 'Referencia al combo (padre)',
  `productos_id` int NOT NULL COMMENT 'Producto hijo que compone el combo',
  `cantidad_por_porcion` decimal(12,4) NOT NULL DEFAULT '1.0000' COMMENT 'Cantidad de insumo consumido por porción',
  `unidad` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Unidad de medida (ej: g, ml, und)',
  `merma_pct` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Porcentaje de merma (ej: 10.00 = 10%)',
  `obligatorio` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=fijo (siempre incluido), 0=opcional',
  `precio_extra` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Recargo si el insumo opcional se selecciona',
  `version` int NOT NULL DEFAULT '1' COMMENT 'Número de versión de la receta',
  `orden` smallint NOT NULL DEFAULT '1' COMMENT 'Orden de presentación',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro del detalle'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Tabla combo_detalle: define los productos hijos que forman parte de cada combo';

-- --------------------------------------------------------

--
-- Table structure for table `compras`
--

CREATE TABLE `compras` (
  `compras_id` int NOT NULL,
  `proveedores_id` int NOT NULL,
  `number` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `tipo_compra` int NOT NULL COMMENT '1. Contado 2. Crédito',
  `colaboradores_id` int NOT NULL,
  `importe` float(12,2) NOT NULL,
  `notas` char(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha` date NOT NULL,
  `estado` int NOT NULL COMMENT '1. Borrador 2. Pagada 3. Crédito 4. Cancelada',
  `usuario` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `cuentas_id` int NOT NULL,
  `recordatorio` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compras_detalles`
--

CREATE TABLE `compras_detalles` (
  `compras_detalles_id` int NOT NULL,
  `compras_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio` float(12,2) NOT NULL,
  `isv_valor` float(12,2) NOT NULL,
  `descuento` float(12,2) NOT NULL,
  `medida` char(12) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `config_id` int NOT NULL,
  `accion` char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
  `activar` int NOT NULL COMMENT '1. Si 0.No'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`config_id`, `accion`, `activar`) VALUES
(1, 'Mostrar detalle facturas - Caja', 1),
(2, 'Validar Apertura Caja', 1),
(3, 'Activar Proforma', 2),
(4, 'Activar Rebajar Inventario Proforma', 2),
(5, 'Activar Cobro Proforma', 1),
(6, 'Activar ISV Proforma', 1),
(7, 'Convertir Proforma Pagada a Factura', 1),
(8, 'Clave Descuento y Precio', 2);

-- --------------------------------------------------------

--
-- Table structure for table `config_lista_blanca`
--

CREATE TABLE `config_lista_blanca` (
  `id` int NOT NULL,
  `nombre_config` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `modulos` longtext COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Lista blanca de los menús y pantallas permitidas para acceder en el sistema';

--
-- Dumping data for table `config_lista_blanca`
--

INSERT INTO `config_lista_blanca` (`id`, `nombre_config`, `modulos`) VALUES
(1, 'configuracion_principal', '[\"transferencia\",\"dashboard\",\"clientes\",\"facturas\",\"cotizacion\",\"cajas\",\"proveedores\",\"facturaCompras\",\"productos\",\"inventario\",\"cuentasContabilidad\",\"movimientosContabilidad\",\"ingresosContabilidad\",\"gastosContabilidad\",\"chequesContabilidad\",\"confCtaContabilidad\",\"confTipoPago\",\"confBancos\",\"confImpuestos\",\"historialAccesos\",\"bitacora\",\"colaboradores\",\"puestos\",\"users\",\"secuencia\",\"empresa\",\"privilegio\",\"tipoUser\",\"cobrarClientes\",\"pagarProveedores\",\"reporteCompras\",\"reporteVentas\",\"reporteCotizacion\",\"confAlmacen\",\"confUbicacion\",\"confCategoria\",\"confMedida\",\"confEmail\",\"confImpresora\",\"contrato\",\"nomina\",\"asistencia\",\"sistema\",\"programaPuntos\",\"registarMenus\",\"registrarPlanes\",\"asignacionPlanes\",\"DetallesFacturacion\",\"facturaMovil\",\"facturasRestaurante\",\"cocina\"]');

-- --------------------------------------------------------

--
-- Table structure for table `contrato`
--

CREATE TABLE `contrato` (
  `contrato_id` int NOT NULL,
  `colaborador_id` int NOT NULL,
  `tipo_contrato_id` int NOT NULL,
  `pago_planificado_id` int NOT NULL,
  `tipo_empleado_id` int NOT NULL,
  `salario_mensual` decimal(12,2) NOT NULL,
  `salario` decimal(12,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `notas` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `usuario` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 2.Inactivo',
  `fecha_registro` datetime NOT NULL,
  `semanal` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `correo`
--

CREATE TABLE `correo` (
  `correo_id` int NOT NULL COMMENT 'Identificador único de la configuración de correo',
  `correo_tipo_id` int NOT NULL COMMENT 'Tipo de correo: 1 Notificaciones, 2 Soporte, 3 Facturas, 4 No Reply',
  `metodo_envio` enum('SMTP','GRAPH') CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'SMTP' COMMENT 'Método de envío utilizado: SMTP usa PHPMailer; GRAPH usa Microsoft Graph API con OAuth',
  `server` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Servidor de correo. Para SMTP: servidor SMTP. Para GRAPH: graph.microsoft.com',
  `correo` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Correo emisor configurado para este tipo de envío',
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  `port` int NOT NULL COMMENT 'Puerto SMTP. Para GRAPH debe quedar en 0',
  `smtp_secure` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Seguridad SMTP: tls o ssl. Para GRAPH debe quedar vacío',
  `tenant_id` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `client_id` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `client_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  `graph_user` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Buzón real de Microsoft 365 que enviará el correo mediante Graph API',
  `save_to_sent_items` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Indica si Microsoft Graph debe guardar copia del correo en Elementos enviados: 1 Sí, 0 No',
  `estado` int NOT NULL COMMENT 'Estado de la configuración: 1 Activo, 2 Inactivo',
  `fecha_registro` datetime NOT NULL COMMENT 'Fecha y hora en que se registró la configuración de correo'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `correo`
--

INSERT INTO `correo` (`correo_id`, `correo_tipo_id`, `metodo_envio`, `server`, `correo`, `password`, `port`, `smtp_secure`, `tenant_id`, `client_id`, `client_secret`, `graph_user`, `save_to_sent_items`, `estado`, `fecha_registro`) VALUES
(1, 1, 'GRAPH', 'graph.microsoft.com', 'izzycloud@esmultiservicios.com', '', 0, '', 'a203WkZWenl3ZWF4TG12endPbCszWGpaM0VqbkdjRk5UN1cxR01vZ3dsbG45eGpzT1R5R0NHYTR3WDExeFVQQg==', 'NXlJUlR1RzV1KzQxU0xLK0ZOU2graU13OTQvUURSVGNneG0yZWE4Nm5VelpQNG9BMTlFWnE4RjY4RGVZeTFvaA==', 'MDVzcEdpanN6QURmOGkrZ3BPa2xCTlR2UlV4TzVTUkJvWFkzclRkR1lKRzZUVFlsdm5sUTZOano0eGxRMFpEZQ==', 'izzycloud@esmultiservicios.com', 1, 1, '2025-06-30 18:57:26'),
(2, 2, 'GRAPH', 'graph.microsoft.com', 'izzycloud@esmultiservicios.com', '', 0, '', 'a203WkZWenl3ZWF4TG12endPbCszWGpaM0VqbkdjRk5UN1cxR01vZ3dsbG45eGpzT1R5R0NHYTR3WDExeFVQQg==', 'NXlJUlR1RzV1KzQxU0xLK0ZOU2graU13OTQvUURSVGNneG0yZWE4Nm5VelpQNG9BMTlFWnE4RjY4RGVZeTFvaA==', 'MDVzcEdpanN6QURmOGkrZ3BPa2xCTlR2UlV4TzVTUkJvWFkzclRkR1lKRzZUVFlsdm5sUTZOano0eGxRMFpEZQ==', 'izzycloud@esmultiservicios.com', 1, 1, '2025-06-30 18:57:26'),
(3, 3, 'GRAPH', 'graph.microsoft.com', 'izzycloud@esmultiservicios.com', '', 0, '', 'a203WkZWenl3ZWF4TG12endPbCszWGpaM0VqbkdjRk5UN1cxR01vZ3dsbG45eGpzT1R5R0NHYTR3WDExeFVQQg==', 'NXlJUlR1RzV1KzQxU0xLK0ZOU2graU13OTQvUURSVGNneG0yZWE4Nm5VelpQNG9BMTlFWnE4RjY4RGVZeTFvaA==', 'MDVzcEdpanN6QURmOGkrZ3BPa2xCTlR2UlV4TzVTUkJvWFkzclRkR1lKRzZUVFlsdm5sUTZOano0eGxRMFpEZQ==', 'izzycloud@esmultiservicios.com', 1, 1, '2025-06-30 18:57:26'),
(4, 4, 'GRAPH', 'graph.microsoft.com', 'izzycloud@esmultiservicios.com', '', 0, '', 'a203WkZWenl3ZWF4TG12endPbCszWGpaM0VqbkdjRk5UN1cxR01vZ3dsbG45eGpzT1R5R0NHYTR3WDExeFVQQg==', 'NXlJUlR1RzV1KzQxU0xLK0ZOU2graU13OTQvUURSVGNneG0yZWE4Nm5VelpQNG9BMTlFWnE4RjY4RGVZeTFvaA==', 'MDVzcEdpanN6QURmOGkrZ3BPa2xCTlR2UlV4TzVTUkJvWFkzclRkR1lKRzZUVFlsdm5sUTZOano0eGxRMFpEZQ==', 'izzycloud@esmultiservicios.com', 1, 1, '2025-06-30 09:23:29');

-- --------------------------------------------------------

--
-- Table structure for table `correo_tipo`
--

CREATE TABLE `correo_tipo` (
  `correo_tipo_id` int NOT NULL,
  `nombre` char(15) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `correo_tipo`
--

INSERT INTO `correo_tipo` (`correo_tipo_id`, `nombre`) VALUES
(1, 'Notificaciones'),
(2, 'Soporte'),
(3, 'Facturas'),
(4, 'No Reply');

-- --------------------------------------------------------

--
-- Table structure for table `cotizacion`
--

CREATE TABLE `cotizacion` (
  `cotizacion_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `number` int NOT NULL,
  `tipo_factura` int NOT NULL COMMENT '1. Contado 2. Crédito',
  `colaboradores_id` int NOT NULL,
  `importe` float(12,2) NOT NULL,
  `notas` char(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha` date NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activa 2. Cancelada',
  `vigencia_cotizacion_id` int NOT NULL,
  `usuario` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `fecha_dolar` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cotizacion_detalles`
--

CREATE TABLE `cotizacion_detalles` (
  `cotizacion_detalle_id` int NOT NULL,
  `cotizacion_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio` float(12,2) NOT NULL,
  `isv_valor` float(12,2) NOT NULL,
  `isv_valor1` float(12,2) NOT NULL DEFAULT '0.00',
  `descuento` float(12,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cuentas`
--

CREATE TABLE `cuentas` (
  `cuentas_id` int NOT NULL,
  `codigo` char(11) COLLATE utf8mb4_spanish_ci NOT NULL,
  `nombre` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
  `fecha_registro` datetime NOT NULL,
  `es_inversion` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = Cuenta usada para inversión/reposición'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `cuentas`
--

INSERT INTO `cuentas` (`cuentas_id`, `codigo`, `nombre`, `estado`, `fecha_registro`, `es_inversion`) VALUES
(1, '1010', 'Apertura', 1, '2025-06-30 16:45:39', 0),
(2, '1011', 'Caja', 1, '2025-06-30 16:45:39', 0),
(3, '1012', 'Banco Bac', 1, '2025-06-30 16:45:00', 0),
(4, '1013', 'CxC Clientes', 1, '2025-06-30 16:45:00', 0),
(5, '1014', 'CxP Proveedores', 1, '2025-06-30 20:09:20', 0),
(6, '1015', 'CxP Socios', 1, '2025-06-30 11:37:02', 0),
(7, '1016', 'Cooperativa Chorotega', 1, '2026-02-16 12:54:00', 0),
(8, '1017', 'RESERVA ESTRATEGICA', 1, '2026-03-10 20:10:05', 0);

-- --------------------------------------------------------

--
-- Table structure for table `departamentos`
--

CREATE TABLE `departamentos` (
  `departamentos_id` int NOT NULL,
  `nombre` char(17) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `departamentos`
--

INSERT INTO `departamentos` (`departamentos_id`, `nombre`) VALUES
(1, 'Atlántida'),
(2, 'Colón'),
(3, 'Comayagua'),
(4, 'Copán'),
(5, 'Cortés'),
(6, 'Choluteca'),
(7, 'El Paraíso'),
(8, 'Francisco Morazán'),
(9, 'Gracias a Dios'),
(10, 'Intibucá'),
(11, 'Islas de la Bahía'),
(12, 'La Paz'),
(13, 'Lempira'),
(14, 'Ocotepeque'),
(15, 'Olancho'),
(16, 'Santa Bárbara'),
(17, 'Valle'),
(18, 'Yoro');

-- --------------------------------------------------------

--
-- Table structure for table `diarios`
--

CREATE TABLE `diarios` (
  `diarios_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `cuentas_id` int NOT NULL,
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `diarios`
--

INSERT INTO `diarios` (`diarios_id`, `nombre`, `cuentas_id`, `estado`, `fecha_registro`) VALUES
(1, 'Clientes', 4, 1, '2025-06-30 20:03:06'),
(2, 'Proveedores', 5, 1, '2025-06-30 20:03:06'),
(3, 'Planilla', 3, 1, '2025-06-30 19:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `documento`
--

CREATE TABLE `documento` (
  `documento_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `documento`
--

INSERT INTO `documento` (`documento_id`, `nombre`, `estado`) VALUES
(1, 'Factura Electronica', 1),
(2, 'Nota de Credito', 2),
(3, 'Nota de Debito', 2),
(4, 'Factura Proforma', 1);

-- --------------------------------------------------------

--
-- Table structure for table `egresos`
--

CREATE TABLE `egresos` (
  `egresos_id` int NOT NULL,
  `cuentas_id` int NOT NULL,
  `proveedores_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `tipo_egreso` int NOT NULL COMMENT '1. Compras 2. Gastos	',
  `fecha` date NOT NULL,
  `factura` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `factura_pdf` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `subtotal` float(12,2) NOT NULL,
  `descuento` float(12,2) NOT NULL,
  `nc` float(12,2) NOT NULL COMMENT 'Nota de Credito',
  `impuesto` float(12,2) NOT NULL,
  `total` float(12,2) NOT NULL,
  `observacion` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 0. Inactivo',
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `categoria_gastos_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `empresa`
--

CREATE TABLE `empresa` (
  `empresa_id` int NOT NULL,
  `razon_social` char(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `nombre` char(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `otra_informacion` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `eslogan` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `celular` char(8) COLLATE utf8mb4_spanish_ci NOT NULL,
  `telefono` char(8) COLLATE utf8mb4_spanish_ci NOT NULL,
  `correo` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `logotipo` char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
  `rtn` char(14) COLLATE utf8mb4_spanish_ci NOT NULL,
  `ubicacion` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `facebook` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `sitioweb` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `horario` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `firma_documento` char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
  `MostrarFirma` int NOT NULL COMMENT '0. No 1. Si'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facturas`
--

CREATE TABLE `facturas` (
  `facturas_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `secuencia_facturacion_id` int NOT NULL,
  `apertura_id` int NOT NULL,
  `number` int NOT NULL,
  `tipo_factura` int NOT NULL COMMENT '1. Contado 2. Crédito',
  `colaboradores_id` int NOT NULL,
  `importe` float(12,2) NOT NULL,
  `notas` text COLLATE utf8mb4_spanish_ci,
  `fecha` date NOT NULL,
  `estado` int NOT NULL COMMENT '1. Borrador 2. Pagada 3. Crédito 4. Cancelada',
  `usuario` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `fecha_dolar` date NOT NULL,
  `no_orden` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `constancia` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `identificativo_sag` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `numero_interno` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `numero_fiscal_unico` int GENERATED ALWAYS AS ((case when (`number` > 0) then `number` else NULL end)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facturas_detalles`
--

CREATE TABLE `facturas_detalles` (
  `facturas_detalle_id` int NOT NULL,
  `facturas_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio` float(12,4) NOT NULL,
  `costo_unitario` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `isv_valor` float(12,4) NOT NULL,
  `isv_valor1` float(12,4) NOT NULL DEFAULT '0.0000',
  `descuento` float(12,4) NOT NULL,
  `medida` char(12) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facturas_modificaciones`
--

CREATE TABLE `facturas_modificaciones` (
  `facturas_modificaciones_id` bigint UNSIGNED NOT NULL,
  `facturas_id` bigint NOT NULL,
  `empresa_id` bigint NOT NULL,
  `campo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_anterior` text COLLATE utf8mb4_unicode_ci,
  `valor_nuevo` text COLLATE utf8mb4_unicode_ci,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` bigint NOT NULL,
  `autorizado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facturas_proforma`
--

CREATE TABLE `facturas_proforma` (
  `facturas_proforma_id` int NOT NULL,
  `facturas_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `secuencia_facturacion_id` int NOT NULL,
  `numero` int NOT NULL,
  `importe` float(12,2) NOT NULL,
  `usuario` int NOT NULL,
  `empresa_id` int NOT NULL,
  `estado` int NOT NULL COMMENT '0. Pendiente 1. Pagada',
  `fecha_creacion` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facturas_recurrentes`
--

CREATE TABLE `facturas_recurrentes` (
  `rec_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `tipo_documento` tinyint NOT NULL COMMENT '0=Normal, 1=Proforma',
  `tipo_factura` tinyint NOT NULL COMMENT '1=Contado, 2=Credito',
  `notas` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha_dolar` date NOT NULL,
  `exoneracion_orden` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `exoneracion_constancia` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `exoneracion_sag` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `exoneracion_orden_interno` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `periodicidad` enum('once','daily','weekly','monthly') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'monthly',
  `dia_mes` tinyint DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `next_run_at` datetime NOT NULL,
  `until_at` date DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '1' COMMENT '1=Activa, 2=Cancelada',
  `enviar_correo` tinyint NOT NULL DEFAULT '1',
  `ultimo_facturas_id` int DEFAULT NULL,
  `last_run_at` datetime DEFAULT NULL,
  `ultimo_error` text COLLATE utf8mb4_spanish_ci,
  `usuario_crea` int NOT NULL,
  `fecha_crea` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facturas_recurrentes_detalle`
--

CREATE TABLE `facturas_recurrentes_detalle` (
  `rec_detalle_id` int NOT NULL,
  `rec_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `producto` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `descuento` decimal(12,2) NOT NULL,
  `isv_valor` decimal(12,2) NOT NULL,
  `isv_valor1` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `medida` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `almacen_id` int DEFAULT NULL,
  `precio_real` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `referencia_producto` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facturas_recurrentes_ejecuciones`
--

CREATE TABLE `facturas_recurrentes_ejecuciones` (
  `ejecucion_id` bigint NOT NULL,
  `rec_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `facturas_id` int DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '0' COMMENT '0=Procesando, 1=Generada, 2=Error',
  `correo_estado` tinyint NOT NULL DEFAULT '0' COMMENT '0=Pendiente, 1=Enviado, 2=Error, 3=No solicitado',
  `mensaje` text COLLATE utf8mb4_spanish_ci,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `factura_comanda`
--

CREATE TABLE `factura_comanda` (
  `id` int NOT NULL,
  `factura_id` int NOT NULL,
  `mesa_id` int DEFAULT NULL,
  `comentarios_cocina` text COLLATE utf8mb4_spanish_ci,
  `estado` enum('pendiente','en_preparacion','preparada','urgente','completada') COLLATE utf8mb4_spanish_ci DEFAULT 'pendiente',
  `servicio_tipo` enum('mesa','llevar') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'llevar',
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `factura_comanda_items`
--

CREATE TABLE `factura_comanda_items` (
  `comanda_item_id` int NOT NULL,
  `factura_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `cantidad` decimal(12,3) NOT NULL DEFAULT '0.000',
  `estacion` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cocina',
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `factura_restaurante_cuentas`
--

CREATE TABLE `factura_restaurante_cuentas` (
  `factura_id` int NOT NULL,
  `mesa_id` int NOT NULL DEFAULT '0',
  `servicio_tipo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'llevar',
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'abierta',
  `usuario_id` int NOT NULL DEFAULT '0',
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historial`
--

CREATE TABLE `historial` (
  `historial_id` int NOT NULL,
  `modulo` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `colaboradores_id` int NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Estado del movimiento: Transferencia, Aprobado, Rechazado, etc.',
  `observacion` char(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historial_acceso`
--

CREATE TABLE `historial_acceso` (
  `historial_acceso_id` int NOT NULL,
  `fecha` datetime NOT NULL,
  `colaboradores_id` int NOT NULL,
  `ip` char(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `acceso` char(150) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historial_puntos`
--

CREATE TABLE `historial_puntos` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `programa_puntos_id` int NOT NULL,
  `tipo_movimiento` enum('acumulacion','redencion') COLLATE utf8mb4_spanish_ci NOT NULL,
  `puntos` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `impresora`
--

CREATE TABLE `impresora` (
  `impresora_id` int NOT NULL,
  `descripcion` char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `tipo` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `impresora`
--

INSERT INTO `impresora` (`impresora_id`, `descripcion`, `estado`, `tipo`, `fecha_registro`) VALUES
(0, 'Comprobante Carta', 0, 3, '2025-06-30 18:37:22'),
(1, 'Factura Carta', 1, 1, '2026-05-09 11:07:18'),
(2, 'Factura Ticket', 0, 2, '2026-05-09 11:05:34'),
(3, 'Comprobante Ticket', 0, 4, '2025-06-30 05:51:17'),
(4, 'Factura Media Carta', 0, 5, '2025-06-30 13:58:11'),
(6, 'Nota de Crédito Carta', 1, 6, '2026-10-11 09:17:05'),
(7, 'Nota de Crédito Ticket', 0, 7, '2026-10-11 09:17:05');

-- --------------------------------------------------------

--
-- Table structure for table `ingresos`
--

CREATE TABLE `ingresos` (
  `ingresos_id` int NOT NULL,
  `cuentas_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `tipo_ingreso` int NOT NULL COMMENT '1. Ingresos Ventas 2. Otros Ingresos',
  `fecha` date NOT NULL,
  `factura` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `subtotal` float(12,2) NOT NULL,
  `descuento` float(12,2) NOT NULL,
  `nc` float(12,2) NOT NULL COMMENT 'Nota de Credito',
  `impuesto` float(12,2) NOT NULL,
  `total` float(12,2) NOT NULL,
  `observacion` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 0. Inactivo',
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_ajustes`
--

CREATE TABLE `inventario_ajustes` (
  `inventario_ajustes_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `almacen_id` int NOT NULL,
  `lote_id` int DEFAULT NULL,
  `saldo_sistema` decimal(15,2) NOT NULL DEFAULT '0.00',
  `conteo_fisico` decimal(15,2) NOT NULL DEFAULT '0.00',
  `diferencia` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tipo_ajuste` varchar(20) NOT NULL,
  `movimientos_id` int DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `empresa_id` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `isv`
--

CREATE TABLE `isv` (
  `isv_id` int NOT NULL,
  `isv_tipo_id` int NOT NULL,
  `valor` float(12,2) NOT NULL,
  `activar` int NOT NULL COMMENT '0. No 1. Si',
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `isv`
--

INSERT INTO `isv` (`isv_id`, `isv_tipo_id`, `valor`, `activar`, `fecha_registro`) VALUES
(1, 1, 15.00, 1, '2025-06-30 18:04:31'),
(2, 1, 18.00, 1, '2025-06-30 18:04:31'),
(3, 2, 15.00, 1, '2026-06-07 16:16:19'),
(4, 2, 18.00, 1, '2026-06-07 16:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `isv_tipo`
--

CREATE TABLE `isv_tipo` (
  `isv_tipo_id` int NOT NULL,
  `nombre` char(11) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `isv_tipo`
--

INSERT INTO `isv_tipo` (`isv_tipo_id`, `nombre`) VALUES
(1, 'Facturas'),
(2, 'Compras');

-- --------------------------------------------------------

--
-- Table structure for table `jobs_queue`
--

CREATE TABLE `jobs_queue` (
  `id` int NOT NULL,
  `job_type` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `data` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_spanish_ci DEFAULT 'pending',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `error_message` text COLLATE utf8mb4_spanish_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lotes`
--

CREATE TABLE `lotes` (
  `lote_id` int NOT NULL,
  `numero_lote` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `productos_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `almacen_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `estado` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medida`
--

CREATE TABLE `medida` (
  `medida_id` int NOT NULL,
  `nombre` char(10) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `medida`
--

INSERT INTO `medida` (`medida_id`, `nombre`, `descripcion`, `estado`, `fecha_registro`) VALUES
(1, 'Und', 'Unidad(es)', 1, '2025-06-30 14:58:41'),
(2, 'Lbs', 'Libra(s)', 1, '2025-06-30 14:58:57'),
(3, 'Hora', 'Hora(s)', 1, '2025-06-30 10:22:56'),
(4, 'Kg', 'Kilogramo(s)', 1, '2025-06-30 23:33:49'),
(5, 'g', 'Gramo(s)', 1, '2025-06-30 23:33:49'),
(6, 'mg', 'Miligramo(s)', 1, '2025-06-30 23:33:49'),
(7, 'L', 'Litro(s)', 1, '2025-06-30 23:33:49'),
(8, 'ml', 'Mililitro(s)', 1, '2025-06-30 23:33:49'),
(9, 'm', 'Metro(s)', 1, '2025-06-30 23:33:49'),
(10, 'cm', 'Centímetro(s)', 1, '2025-06-30 23:33:49'),
(11, 'mm', 'Milímetro(s)', 1, '2025-06-30 23:33:49'),
(12, 'Und', 'Unidad(es)', 1, '2025-06-30 23:33:49'),
(13, 'Doc', 'Docena(s)', 1, '2025-06-30 23:33:49'),
(14, 'Paq', 'Paquete(s)', 1, '2025-06-30 23:33:49'),
(15, 'Caja', 'Caja(s)', 1, '2025-06-30 23:33:49'),
(16, 'Saco', 'Saco(s)', 1, '2025-06-30 23:33:49'),
(17, 'Gal', 'Galón(es)', 1, '2025-06-30 23:33:49'),
(18, 'Onz', 'Onza(s)', 1, '2025-06-30 23:33:49'),
(19, 'Lb', 'Libra(s)', 1, '2025-06-30 23:33:49'),
(20, 'Par', 'Par(es)', 1, '2025-06-30 23:33:49'),
(21, 'Rollo', 'Rollo(s)', 1, '2025-06-30 23:33:49'),
(22, 'Bolsa', 'Bolsa(s)', 1, '2025-06-30 23:33:49'),
(23, 'Blister', 'Blister(s)', 1, '2025-06-30 23:33:49'),
(24, 'Hora', 'Hora(s)', 1, '2025-06-30 23:33:49'),
(25, 'Min', 'Minuto(s)', 1, '2025-06-30 23:33:49'),
(26, 'Seg', 'Segundo(s)', 1, '2025-06-30 23:33:49'),
(27, 'Día', 'Día(s)', 1, '2025-06-30 23:33:49'),
(28, 'Sem', 'Semana(s)', 1, '2025-06-30 23:33:49'),
(29, 'Mes', 'Mes(es)', 1, '2025-06-30 23:33:49'),
(30, 'Año', 'Año(s)', 1, '2025-06-30 23:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `menu_id` int NOT NULL,
  `name` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `fecha_registro` timestamp NOT NULL,
  `visible` tinyint(1) DEFAULT '1' COMMENT '1. Si 0. No'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Lista de todos los menús disponibles en el sistema';

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`menu_id`, `name`, `descripcion`, `icon`, `orden`, `fecha_registro`, `visible`) VALUES
(1, 'dashboard', 'Dashboard', 'fas fa-tachometer-alt fa-lg', 0, '2025-06-30 09:07:15', 1),
(2, 'ventas', 'Ventas', 'fab fa-sellsy fa-lg', 1, '2025-06-30 21:07:38', 1),
(3, 'compras', 'Compras', 'fas fa-store-alt fa-lg', 2, '2025-06-30 21:07:38', 1),
(4, 'almacen', 'Almacen', 'fas fa-warehouse fa-lg', 3, '2025-06-30 21:07:38', 1),
(6, 'reportes', 'Reportes', 'fas fa-chart-pie fa-lg', 6, '2025-06-30 21:07:38', 1),
(7, 'configuracion', 'Configuración', 'fas fa-cogs fa-lg', 7, '2025-06-30 21:07:38', 1),
(5, 'contabilidad', 'Contabilidad', 'fas fa-calculator fa-lg', 4, '2025-06-30 21:07:38', 1),
(8, 'recursosHumanos', 'Recursos Humanos', 'fas fa-users fa-lg', 5, '2025-06-30 21:07:38', 1),
(9, 'sistema', 'Sistema', 'fa-solid fa-screwdriver-wrench', 8, '2025-06-30 20:18:24', 1),
(10, 'DetallesFactura', 'Detalles de Facturación', 'fa-solid fa-file-invoice fa-lg', 9, '2025-07-01 03:19:45', 0),
(11, 'facturasRestaurante', 'Restaurante', 'fa-solid fa-utensils fa-lg', 10, '2025-09-05 15:39:46', 1),
(12, 'cocina', 'Cocina', 'fa-solid fa-kitchen-set fa-lg', 11, '2025-09-06 03:03:06', 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu_plan`
--

CREATE TABLE `menu_plan` (
  `menu_plan_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `planes_id` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Mostrar 0. Ocultar'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Contiene los menús disponibles por plan para mostrar al cliente según el plan activado';

--
-- Dumping data for table `menu_plan`
--

INSERT INTO `menu_plan` (`menu_plan_id`, `menu_id`, `planes_id`, `estado`) VALUES
(42, 2, 6, 1),
(44, 6, 6, 1),
(45, 1, 6, 1),
(59, 4, 6, 1),
(60, 3, 6, 1),
(61, 7, 6, 1),
(62, 5, 6, 1),
(63, 8, 6, 1),
(64, 9, 6, 0),
(65, 10, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `mesas`
--

CREATE TABLE `mesas` (
  `mesa_id` int NOT NULL,
  `numero` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `capacidad` int NOT NULL DEFAULT '4',
  `ubicacion` enum('Interior','Terraza','Barra') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Interior',
  `estado` enum('disponible','ocupada','reservada','mantenimiento') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'disponible',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `colaborador_id` int NOT NULL COMMENT 'Quien creó/registró la mesa',
  `empresa_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mesas_reservas`
--

CREATE TABLE `mesas_reservas` (
  `mesa_reserva_id` int NOT NULL,
  `mesa_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_reserva` time NOT NULL,
  `personas` int NOT NULL DEFAULT '1',
  `notas` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `usuario_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movimientos`
--

CREATE TABLE `movimientos` (
  `movimientos_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `documento` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `cantidad_entrada` int NOT NULL,
  `cantidad_salida` int NOT NULL,
  `saldo` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `clientes_id` int NOT NULL,
  `comentario` char(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `almacen_id` int NOT NULL,
  `lote_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movimientos_cuentas`
--

CREATE TABLE `movimientos_cuentas` (
  `movimientos_cuentas_id` int NOT NULL,
  `cuentas_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha` date NOT NULL,
  `ingreso` float(12,2) NOT NULL,
  `egreso` float(12,2) NOT NULL,
  `saldo` float(12,2) NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `municipios`
--

CREATE TABLE `municipios` (
  `municipios_id` int NOT NULL,
  `departamentos_id` int NOT NULL,
  `nombre` char(35) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `municipios`
--

INSERT INTO `municipios` (`municipios_id`, `departamentos_id`, `nombre`) VALUES
(1, 1, 'La Ceiba'),
(2, 1, 'El Porvenir'),
(3, 1, 'Esparta'),
(4, 1, 'Jutiapa'),
(5, 1, 'La Masica'),
(6, 1, 'San Francisco'),
(7, 1, 'Tela'),
(8, 1, 'Arizona'),
(9, 2, 'Colón'),
(10, 2, 'Chagres'),
(11, 2, 'Donoso'),
(12, 2, 'Portobelo'),
(13, 2, 'Santa Isabel'),
(14, 2, 'Trujillo'),
(15, 2, 'Balfate'),
(16, 2, 'Iriona'),
(17, 2, 'Limón'),
(18, 2, 'Saba'),
(19, 2, 'Santa Fé'),
(20, 2, 'Santa Rosa de Aguan'),
(21, 2, 'Sonaguera'),
(22, 2, 'Tocoa'),
(23, 2, 'Bonito Oriental'),
(24, 3, 'Comayagua'),
(25, 3, 'Ajuterique'),
(26, 3, 'El Rosario'),
(27, 3, 'Esquias'),
(28, 3, 'Humuya'),
(29, 3, 'La Libertad'),
(30, 3, 'Lamani'),
(31, 3, 'La Trinidad'),
(32, 3, 'Lejamani'),
(33, 3, 'Meambar'),
(34, 3, 'Minas de Oro'),
(35, 3, 'Ojos de Agua'),
(36, 3, 'San Jerónimo'),
(37, 3, 'San José Comayagua'),
(38, 3, 'San José del Potrero'),
(39, 3, 'San Luis'),
(40, 3, 'San Sebastián'),
(41, 3, 'Siguatepeque'),
(42, 3, 'Villa de San Antonio'),
(43, 3, 'Las Lajas'),
(44, 3, 'Taulabe'),
(45, 4, 'Santa Rosa de Copán'),
(46, 4, 'Cabañas'),
(47, 4, 'Concepción'),
(48, 4, 'Copán Ruinas'),
(49, 4, 'Corquin'),
(50, 4, 'Cucuyagua'),
(51, 4, 'Dolores'),
(52, 4, 'Dulce Nombre'),
(53, 4, 'El Paraíso'),
(54, 4, 'Florida'),
(55, 4, 'La Jigua'),
(56, 4, 'La Unión'),
(57, 4, 'Nueva Arcadia'),
(58, 4, 'San Agustín'),
(59, 4, 'San Antonio'),
(60, 4, 'San Jerónimo'),
(61, 4, 'San José'),
(62, 4, 'San Juan de Opoa'),
(63, 4, 'San Nicolás'),
(64, 4, 'San Pedro'),
(65, 4, 'Santa Rita'),
(66, 4, 'Trinidad'),
(67, 4, 'Veracruz'),
(68, 5, 'San Pedro Sula'),
(69, 5, 'Choloma'),
(70, 5, 'Omoa'),
(71, 5, 'Pimienta'),
(72, 5, 'Potrerillos'),
(73, 5, 'Puerto Cortés'),
(74, 5, 'San Antonio de Cortés'),
(75, 5, 'San Francisco de Yojoa'),
(76, 5, 'San Manuel'),
(77, 5, 'Santa Cruz de Yojoa'),
(78, 5, 'Villanueva'),
(79, 5, 'La Lima'),
(80, 6, 'Choluteca'),
(81, 6, 'Apacilagua'),
(82, 6, 'Concepción de María'),
(83, 6, 'Duyure'),
(84, 6, 'El Corpus'),
(85, 6, 'El Triunfo'),
(86, 6, 'Marcovia'),
(87, 6, 'Morolica'),
(88, 6, 'Namasigue'),
(89, 6, 'Orocuina'),
(90, 6, 'Pespire'),
(91, 6, 'San Antonio de Flores'),
(92, 6, 'San Isidro'),
(93, 6, 'San José'),
(94, 6, 'San Marcos de Colón'),
(95, 6, 'Santa Ana de Yusguare'),
(96, 7, 'Yuscaran'),
(97, 7, 'Alauca'),
(98, 7, 'Danli'),
(99, 7, 'El Paraíso'),
(100, 7, 'Guinope'),
(101, 7, 'Jacaleapa'),
(102, 7, 'Liure'),
(103, 7, 'Moroceli'),
(104, 7, 'Oropoli'),
(105, 7, 'Potrerillos'),
(106, 7, 'San Antonio de Flores'),
(107, 7, 'San Lucas'),
(108, 7, 'San Matías'),
(109, 7, 'Soledad'),
(110, 7, 'Teupasenti'),
(111, 7, 'Texiguat'),
(112, 7, 'Vado Ancho'),
(113, 7, 'Yauyupe'),
(114, 7, 'Trojes'),
(115, 8, 'Tegucigalpa'),
(116, 8, 'Distrito Central'),
(117, 8, 'Alubaren'),
(118, 8, 'Cedros'),
(119, 8, 'Curaren'),
(120, 8, 'El Porvenir'),
(121, 8, 'Guaimaca'),
(122, 8, 'La Libertad'),
(123, 8, 'La Venta'),
(124, 8, 'Lepaterique'),
(125, 8, 'Maraita'),
(126, 8, 'Marale'),
(127, 8, 'Nueva Armenia'),
(128, 8, 'Ojojona'),
(129, 8, 'Orica'),
(130, 8, 'Reitoca'),
(131, 8, 'Sabanagrande San Antonio de Oriente'),
(132, 8, 'San Buenaventura'),
(133, 8, 'San Ignacio'),
(134, 8, 'San Juan de Flores'),
(135, 8, 'San Miguelito'),
(136, 8, 'Santa Ana'),
(137, 8, 'Santa Lucía'),
(138, 8, 'Talanga'),
(139, 8, 'Tatumbla'),
(140, 8, 'Valle de Angeles'),
(141, 8, 'Villa de San Francisco'),
(142, 8, 'Vallecillo'),
(143, 9, 'Puerto Lempira'),
(144, 9, 'Brus Laguna'),
(145, 9, 'Ahuas'),
(146, 9, 'Juan Francisco Bulnes'),
(147, 9, 'Villeda Morales'),
(148, 9, 'Wampusirpi'),
(149, 10, 'La Esperanza'),
(150, 10, 'Camasca'),
(151, 10, 'Colomoncagua'),
(152, 10, 'Concepción'),
(153, 10, 'Dolores'),
(154, 10, 'Intibuca'),
(155, 10, 'Jesús de Otoro'),
(156, 10, 'Magdalena'),
(157, 10, 'Masaguara'),
(158, 10, 'San Antonio'),
(159, 10, 'San Isidro'),
(160, 10, 'San Juan'),
(161, 10, 'San Marcos de La Sierra'),
(162, 10, 'San Miguelito'),
(163, 10, 'Santa Lucía'),
(164, 10, 'Yamaranguila'),
(165, 10, 'San Francisco de Opalaca'),
(166, 11, 'Roatán'),
(167, 11, 'Guanaja'),
(168, 11, 'José Santos Guardiola'),
(169, 11, 'Utila'),
(170, 12, 'La Paz'),
(171, 12, 'Aguantequerique'),
(172, 12, 'Cabañas'),
(173, 12, 'Cane'),
(174, 12, 'Chinacla'),
(175, 12, 'Guajiquiro'),
(176, 12, 'Lauterique'),
(177, 12, 'Marcala'),
(178, 12, 'Mercedes de Oriente'),
(179, 12, 'Opatoro'),
(180, 12, 'San Antonio del Norte'),
(181, 12, 'San José'),
(182, 12, 'San Juan'),
(183, 12, 'San pedro de Tutule Santa Ana'),
(184, 12, 'Santa Elena'),
(185, 12, 'Santa María'),
(186, 12, 'Santiago de Puringla'),
(187, 12, 'Yarula'),
(188, 13, 'Gracias'),
(189, 13, 'Belén'),
(190, 13, 'Candelaria'),
(191, 13, 'Cololaca'),
(192, 13, 'Erandique'),
(193, 13, 'Gualcince'),
(194, 13, 'Guarita'),
(195, 13, 'La Campa'),
(196, 13, 'La Iguala'),
(197, 13, 'Las Flores'),
(198, 13, 'La Unión'),
(199, 13, 'La Virtud'),
(200, 13, 'Lepaera'),
(201, 13, 'Mapulaca'),
(202, 13, 'Piraera'),
(203, 13, 'San Andrés'),
(204, 13, 'San Francisco'),
(205, 13, 'San Juan Guarita'),
(206, 13, 'San Manuel de Colohete'),
(207, 13, 'San Rafael'),
(208, 13, 'San Sebastián'),
(209, 13, 'Santa Cruz'),
(210, 13, 'Talgua'),
(211, 13, 'Tambla'),
(212, 13, 'Tomala'),
(213, 13, 'Valladolid'),
(214, 13, 'Virginia'),
(215, 13, 'San Marcos de Caiquin'),
(216, 14, 'Ocotepeque'),
(217, 14, 'Belén Gualcho'),
(218, 14, 'Concepción'),
(219, 14, 'Dolores Merendón'),
(220, 14, 'Fraternidad'),
(221, 14, 'La Encarnación'),
(222, 14, 'La Labor'),
(223, 14, 'Lucema'),
(224, 14, 'Mercedes'),
(225, 14, 'San Fernando'),
(226, 14, 'San Francisco del Valle'),
(227, 14, 'San Jorge'),
(228, 14, 'San Marcos'),
(229, 14, 'Santa Fé'),
(230, 14, 'Sensenti'),
(231, 14, 'Sinuapa'),
(232, 15, 'Juticalpa'),
(233, 15, 'Campamento'),
(234, 15, 'Catacamas'),
(235, 15, 'Concordia'),
(236, 15, 'Dulce Nombre de Culmi'),
(237, 15, 'El Rosario'),
(238, 15, 'Esquipulas del Norte'),
(239, 15, 'Gualaco'),
(240, 15, 'Guarizama'),
(241, 15, 'Guata'),
(242, 15, 'Guayape'),
(243, 15, 'Jano'),
(244, 15, 'La Unión'),
(245, 15, 'Mangulile'),
(246, 15, 'Manto'),
(247, 15, 'Salama'),
(248, 15, 'San Esteban'),
(249, 15, 'San Francisco de Becerra'),
(250, 15, 'San Francisco de La Paz'),
(251, 15, 'Santa María del Real'),
(252, 15, 'Silca'),
(253, 15, 'Yocon'),
(254, 15, 'Patuca'),
(255, 16, 'Santa Bárbara'),
(256, 16, 'Arada'),
(257, 16, 'Atima'),
(258, 16, 'Azacualpa'),
(259, 16, 'Ceguaca'),
(260, 16, 'San José de Colinas'),
(261, 16, 'Concepción del Norte'),
(262, 16, 'Concepción del Sur'),
(263, 16, 'Chinda'),
(264, 16, 'El Níspero'),
(265, 16, 'Gualala'),
(266, 16, 'Ilama'),
(267, 16, 'Macuelizo'),
(268, 16, 'Naranjito'),
(269, 16, 'Nuevo Celilac'),
(270, 16, 'Petoa'),
(271, 16, 'Protección'),
(272, 16, 'Quimistan'),
(273, 16, 'San Francisco de Ojuera'),
(274, 16, 'San Luis'),
(275, 16, 'San Marcos'),
(276, 16, 'San Nicolás'),
(277, 16, 'San Pedro de Zacapa'),
(278, 16, 'Santa Rita'),
(279, 16, 'San Vicente Centenario'),
(280, 16, 'Trinidad'),
(281, 16, 'Las Vegas'),
(282, 16, 'Nueva Frontera'),
(283, 17, 'Nacaome'),
(284, 17, 'Alianza'),
(285, 17, 'Amapala'),
(286, 17, 'Aramecina'),
(287, 17, 'Caridad'),
(288, 17, 'Goascoran'),
(289, 17, 'Langue'),
(290, 17, 'San Francisco de Coray'),
(291, 17, 'San Lorenzo'),
(292, 18, 'Yoro'),
(293, 18, 'Arenal'),
(294, 18, 'El Negrito'),
(295, 18, 'El Progreso'),
(296, 18, 'Jocon'),
(297, 18, 'Morazan'),
(298, 18, 'Olanchito'),
(299, 18, 'Santa Rita'),
(300, 18, 'Sulaco'),
(301, 18, 'Victoria'),
(302, 18, 'Yorito');

-- --------------------------------------------------------

--
-- Table structure for table `nomina`
--

CREATE TABLE `nomina` (
  `nomina_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `pago_planificado_id` int NOT NULL,
  `tipo_nomina_id` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `detalle` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `importe` decimal(12,2) NOT NULL,
  `notas` varchar(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `usuario` int NOT NULL,
  `estado` int NOT NULL COMMENT '0. No Generada 1. Generada',
  `fecha_registro` datetime NOT NULL,
  `cuentas_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nomina_detalles`
--

CREATE TABLE `nomina_detalles` (
  `nomina_detalles_id` int NOT NULL,
  `nomina_id` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `salario_mensual` decimal(12,2) NOT NULL,
  `dias_trabajados` decimal(12,2) NOT NULL,
  `hrse25` decimal(12,2) NOT NULL,
  `hrse50` decimal(12,2) NOT NULL,
  `hrse75` decimal(12,2) NOT NULL,
  `hrse100` decimal(12,2) NOT NULL,
  `retroactivo` decimal(12,2) NOT NULL,
  `bono` decimal(12,2) NOT NULL,
  `otros_ingresos` decimal(12,2) NOT NULL,
  `deducciones` decimal(12,2) NOT NULL,
  `prestamo` decimal(12,2) NOT NULL,
  `ihss` decimal(12,2) NOT NULL,
  `rap` decimal(12,2) NOT NULL,
  `isr` decimal(12,2) NOT NULL,
  `vales` decimal(12,2) NOT NULL,
  `incapacidad_ihss` decimal(12,2) NOT NULL,
  `neto_ingresos` decimal(12,2) NOT NULL,
  `neto_egresos` decimal(12,2) NOT NULL,
  `neto` decimal(12,2) NOT NULL,
  `usuario` int NOT NULL,
  `estado` int NOT NULL,
  `notas` varchar(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `hrse25_valor` decimal(12,2) NOT NULL,
  `hrse50_valor` decimal(12,2) NOT NULL,
  `hrse75_valor` decimal(12,2) NOT NULL,
  `hrse100_valor` decimal(12,2) NOT NULL,
  `salario` decimal(12,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notas_credito`
--

CREATE TABLE `notas_credito` (
  `nota_credito_id` bigint UNSIGNED NOT NULL,
  `empresa_id` int NOT NULL,
  `facturas_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `secuencia_facturacion_id` int NOT NULL,
  `documento_id` int NOT NULL DEFAULT '2',
  `number` int NOT NULL,
  `prefijo` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT '',
  `relleno` int NOT NULL DEFAULT '0',
  `numero_completo` varchar(80) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha` date NOT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_spanish_ci NOT NULL,
  `base_acreditada` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv15_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `isv18_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `total_acreditado` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `importe_factura_original` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `total_acreditado_anterior` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `cxc_aplicada` tinyint(1) NOT NULL DEFAULT '0',
  `cxc_saldo_antes` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `cxc_saldo_despues` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `credito_favor` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `secuencia_actualizada` tinyint(1) NOT NULL DEFAULT '0',
  `estado` tinyint NOT NULL DEFAULT '1' COMMENT '1=Emitida, 2=Anulada',
  `colaboradores_id` int NOT NULL,
  `usuario` int NOT NULL,
  `origen` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'escritorio',
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notas_credito_aplicaciones`
--

CREATE TABLE `notas_credito_aplicaciones` (
  `nota_credito_aplicacion_id` bigint UNSIGNED NOT NULL,
  `nota_credito_id` bigint UNSIGNED NOT NULL,
  `empresa_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `facturas_id_destino` int NOT NULL,
  `cobrar_clientes_id` int NOT NULL,
  `importe` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `saldo_antes` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `saldo_despues` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `estado` tinyint NOT NULL DEFAULT '0' COMMENT '0=Pendiente, 1=Aplicada, 2=Requiere revisión',
  `usuario` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notas_credito_detalle`
--

CREATE TABLE `notas_credito_detalle` (
  `nota_credito_detalle_id` bigint UNSIGNED NOT NULL,
  `nota_credito_id` bigint UNSIGNED NOT NULL,
  `facturas_detalle_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `producto` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
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
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notas_credito_inventario`
--

CREATE TABLE `notas_credito_inventario` (
  `nota_credito_inventario_id` bigint UNSIGNED NOT NULL,
  `nota_credito_id` bigint UNSIGNED NOT NULL,
  `nota_credito_detalle_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `movimiento_origen_id` int NOT NULL,
  `movimiento_devolucion_id` int NOT NULL DEFAULT '0',
  `empresa_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `almacen_id` int NOT NULL,
  `lote_id` int NOT NULL DEFAULT '0',
  `cantidad_devuelta` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `estado` tinyint NOT NULL DEFAULT '0' COMMENT '0=Pendiente, 1=Aplicada',
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notificaciones`
--

CREATE TABLE `notificaciones` (
  `notificaciones_id` int NOT NULL,
  `correo` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `nombre` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagar_proveedores`
--

CREATE TABLE `pagar_proveedores` (
  `pagar_proveedores_id` int NOT NULL,
  `proveedores_id` int NOT NULL,
  `compras_id` int NOT NULL,
  `fecha` date NOT NULL,
  `saldo` float(12,2) NOT NULL,
  `estado` int NOT NULL COMMENT '1. Pendiente de Cobrar 2. Pago Realizado',
  `usuario` int NOT NULL,
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

CREATE TABLE `pagos` (
  `pagos_id` int NOT NULL,
  `facturas_id` int NOT NULL,
  `tipo_pago` int NOT NULL COMMENT '1. Contado 2. Crédito',
  `fecha` date NOT NULL,
  `importe` float(12,2) NOT NULL,
  `efectivo` float(12,2) NOT NULL,
  `cambio` float(12,2) NOT NULL,
  `tarjeta` float(12,2) NOT NULL,
  `usuario` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Pagado, 2. Cancelado',
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `contabilizado` tinyint(1) NOT NULL DEFAULT '0',
  `referencia_ingreso_id` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagoscompras`
--

CREATE TABLE `pagoscompras` (
  `pagoscompras_id` int NOT NULL,
  `compras_id` int NOT NULL,
  `tipo_pago` int NOT NULL COMMENT '1. Contado 2. Crédito',
  `fecha` date NOT NULL,
  `importe` float(12,2) NOT NULL,
  `efectivo` float(12,2) NOT NULL,
  `cambio` int NOT NULL,
  `tarjeta` float(12,2) NOT NULL,
  `usuario` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Pagado, 2. Cancelado',
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagoscompras_detalles`
--

CREATE TABLE `pagoscompras_detalles` (
  `pagoscompras_detalles_id` int NOT NULL,
  `pagoscompras_id` int NOT NULL,
  `tipo_pago_id` int NOT NULL,
  `banco_id` int NOT NULL,
  `efectivo` float(12,2) NOT NULL,
  `descripcion1` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion2` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion3` char(30) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagos_detalles`
--

CREATE TABLE `pagos_detalles` (
  `pagos_detalles_id` int NOT NULL,
  `pagos_id` int NOT NULL,
  `tipo_pago_id` int NOT NULL,
  `banco_id` int NOT NULL,
  `efectivo` float(12,4) NOT NULL,
  `descripcion1` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion2` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion3` char(30) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pago_planificado`
--

CREATE TABLE `pago_planificado` (
  `pago_planificado_id` int NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `pago_planificado`
--

INSERT INTO `pago_planificado` (`pago_planificado_id`, `nombre`) VALUES
(1, 'Semanal'),
(2, 'Quincenal'),
(3, 'Mensual');

-- --------------------------------------------------------

--
-- Table structure for table `permisos`
--

CREATE TABLE `permisos` (
  `permisos_id` int NOT NULL,
  `tipo_user_id` int NOT NULL,
  `tipo_permiso` char(11) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `permisos`
--

INSERT INTO `permisos` (`permisos_id`, `tipo_user_id`, `tipo_permiso`, `estado`, `fecha_registro`) VALUES
(1, 1, 'guardar', 1, '2025-06-30 08:40:35'),
(2, 1, 'editar', 1, '2025-06-30 08:49:50'),
(3, 1, 'eliminar', 1, '2025-06-30 08:49:50'),
(4, 1, 'consultar', 1, '2025-06-30 08:49:50'),
(5, 1, 'imprimir', 1, '2025-06-30 08:49:50'),
(6, 1, 'crear', 1, '2025-06-30 10:23:43'),
(7, 1, 'reportes', 1, '2025-06-30 10:23:43'),
(8, 1, 'actualizar', 1, '2025-06-30 10:33:20'),
(9, 2, 'guardar', 1, '2025-06-30 12:32:41'),
(10, 2, 'editar', 1, '2025-06-30 12:32:41'),
(11, 2, 'eliminar', 1, '2025-06-30 12:32:41'),
(12, 2, 'consultar', 1, '2025-06-30 12:32:41'),
(13, 2, 'imprimir', 1, '2025-06-30 12:32:41'),
(14, 2, 'crear', 1, '2025-06-30 12:32:41'),
(15, 2, 'reportes', 1, '2025-06-30 12:32:41'),
(16, 2, 'actualizar', 1, '2025-06-30 12:32:41'),
(17, 1, 'view', 1, '2025-06-30 12:05:24'),
(18, 1, 'pay', 1, '2025-06-30 12:05:24'),
(19, 3, 'guardar', 1, '2025-06-30 14:27:14'),
(20, 3, 'editar', 1, '2025-06-30 14:27:14'),
(21, 3, 'eliminar', 1, '2025-06-30 14:27:14'),
(22, 3, 'consultar', 1, '2025-06-30 14:27:14'),
(23, 3, 'imprimir', 1, '2025-06-30 14:27:14'),
(24, 3, 'crear', 1, '2025-06-30 14:27:14'),
(25, 3, 'reportes', 1, '2025-06-30 14:27:14'),
(26, 3, 'actualizar', 1, '2025-06-30 14:27:14'),
(27, 3, 'view', 1, '2025-06-30 14:27:14'),
(28, 3, 'pay', 2, '2025-06-30 14:27:14'),
(29, 3, 'cambiar', 1, '2025-06-30 15:17:32'),
(30, 2, 'view', 1, '2025-06-30 15:17:40'),
(31, 2, 'pay', 1, '2025-06-30 15:17:40'),
(32, 2, 'cambiar', 1, '2025-06-30 15:17:40'),
(33, 1, 'cambiar', 1, '2025-06-30 15:17:54'),
(34, 3, 'cancelar', 1, '2025-06-30 15:32:16'),
(35, 1, 'cancelar', 1, '2025-06-30 15:32:53'),
(36, 2, 'cancelar', 1, '2025-06-30 17:36:25'),
(37, 5, 'guardar', 1, '2025-06-30 08:46:42'),
(38, 5, 'editar', 1, '2025-06-30 08:46:42'),
(39, 5, 'eliminar', 2, '2025-06-30 08:46:42'),
(40, 5, 'consultar', 1, '2025-06-30 08:46:42'),
(41, 5, 'imprimir', 1, '2025-06-30 08:46:42'),
(42, 5, 'crear', 1, '2025-06-30 08:46:42'),
(43, 5, 'reportes', 1, '2025-06-30 08:46:42'),
(44, 5, 'actualizar', 1, '2025-06-30 08:46:42'),
(45, 5, 'view', 1, '2025-06-30 08:46:42'),
(46, 5, 'pay', 2, '2025-06-30 08:46:42'),
(47, 5, 'cambiar', 1, '2025-06-30 08:46:42'),
(48, 5, 'cancelar', 1, '2025-06-30 08:46:42');

-- --------------------------------------------------------

--
-- Table structure for table `pin`
--

CREATE TABLE `pin` (
  `pin_id` int NOT NULL,
  `server_customers_id` int NOT NULL,
  `codigo_cliente` int NOT NULL,
  `pin` int NOT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

CREATE TABLE `plan` (
  `plan_id` int NOT NULL,
  `planes_id` int NOT NULL,
  `user_extra` int NOT NULL COMMENT 'Cantidad de Usuarios Extras',
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `plan`
--

INSERT INTO `plan` (`plan_id`, `planes_id`, `user_extra`, `fecha_registro`) VALUES
(1, 6, 0, '2025-06-30 22:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `planes`
--

CREATE TABLE `planes` (
  `planes_id` int NOT NULL,
  `nombre` char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
  `configuraciones` longtext COLLATE utf8mb4_spanish_ci,
  `estado` int NOT NULL COMMENT '1. Mostrar 0. Ocultar',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Tabla de planes del sistema';

--
-- Dumping data for table `planes`
--

INSERT INTO `planes` (`planes_id`, `nombre`, `configuraciones`, `estado`, `fecha_registro`) VALUES
(1, 'Emprendedor', '{\"usuarios\":\"2\",\"perfiles\":\"1\"}', 1, '2025-07-01 04:36:14'),
(2, 'Básico', '{\"usuarios\":\"3\",\"perfiles\":\"1\"}', 1, '2025-07-01 04:36:14'),
(3, 'Regular', '{\"usuarios\":\"4\",\"perfiles\":\"2\"}', 1, '2025-07-01 04:36:45'),
(4, 'Estandar', '{\"usuarios\":\"5\",\"perfiles\":\"3\"}', 1, '2025-07-01 04:36:57'),
(5, 'Premium', '{\"usuarios\":\"11\",\"perfiles\":\"4\"}', 1, '2025-06-30 14:51:07'),
(6, 'Gratis', '{\"usuarios\":\"1\",\"facturas\":\"100\",\"productos\":\"50\",\"clientes\":\"50\",\"perfiles\":\"1\",\"proveedores\":\"0\",\"compras\":\"0\",\"cotizaciones\":\"0\",\"almacenes\":\"1\",\"categorias\":\"1\",\"colaboradores\":\"1\",\"ubicaciones\":\"1\",\"contratos\":\"0\",\"cuentas\":\"1\",\"ingresos\":\"0\",\"egresos\":\"0\",\"secuencia\":\"1\"}', 1, '2025-06-30 10:03:57'),
(7, 'IZZY', '[]', 1, '2025-06-30 09:50:34'),
(8, 'Basico+', '{\"usuarios\":\"3\",\"perfiles\":\"1\"}', 1, '2026-05-01 16:14:34');

-- --------------------------------------------------------

--
-- Table structure for table `precio_factura`
--

CREATE TABLE `precio_factura` (
  `precio_factura_id` int NOT NULL,
  `facturas_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `fecha` date NOT NULL,
  `referencia` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `precio_anterior` float(12,2) NOT NULL,
  `precio_nuevo` float(12,2) NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_list`
--

CREATE TABLE `price_list` (
  `price_list_id` int NOT NULL,
  `compras_id` int NOT NULL,
  `productos_id` int NOT NULL,
  `prices` float(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `privilegio`
--

CREATE TABLE `privilegio` (
  `privilegio_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `privilegio`
--

INSERT INTO `privilegio` (`privilegio_id`, `nombre`, `estado`, `fecha_registro`) VALUES
(1, 'Super Administrador', 1, '2025-06-30 14:48:54'),
(2, 'Administrador', 1, '2025-06-30 10:05:40'),
(3, 'Reseller', 1, '2025-06-30 18:18:51'),
(4, 'Clientes', 1, '2025-06-30 18:18:51'),
(5, 'Contabilidad', 1, '2025-08-24 07:47:29');

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `productos_id` int NOT NULL,
  `barCode` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `almacen_id` int NOT NULL,
  `medida_id` int NOT NULL,
  `categoria_id` int NOT NULL,
  `nombre` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `tipo_producto_id` int NOT NULL,
  `precio_compra` float(12,2) NOT NULL,
  `porcentaje_venta` float(12,2) NOT NULL COMMENT 'Porcentaje de Ganacia',
  `precio_venta` float(12,2) NOT NULL,
  `cantidad_mayoreo` float(12,2) NOT NULL,
  `precio_mayoreo` float(12,2) NOT NULL,
  `cantidad_minima` int NOT NULL,
  `cantidad_maxima` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo	',
  `isv_venta` int NOT NULL COMMENT '1. Sí 2. No',
  `isv_compra` int NOT NULL COMMENT '1. Sí 2. No',
  `colaborador_id` int NOT NULL,
  `file_name` char(100) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Nombre de la Imagen',
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `id_producto_superior` int NOT NULL,
  `restaurante` tinyint NOT NULL DEFAULT '0',
  `estacion` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `isv1` tinyint NOT NULL DEFAULT '0',
  `isv2` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programa_puntos`
--

CREATE TABLE `programa_puntos` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `tipo_calculo` enum('monto','porcentaje') COLLATE utf8mb4_spanish_ci NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones`
--

CREATE TABLE `promociones` (
  `promo_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `tipo_descuento` enum('PORC','MONTO') COLLATE utf8mb4_spanish_ci NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `dias_semana` set('mon','tue','wed','thu','fri','sat','sun') COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `prioridad` int NOT NULL DEFAULT '0',
  `aplica_a` enum('PRODUCTO','CATEGORIA','TODOS') COLLATE utf8mb4_spanish_ci NOT NULL,
  `acumula_con_mayoreo` tinyint(1) NOT NULL DEFAULT '0',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `creado_por` int DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_categorias`
--

CREATE TABLE `promo_categorias` (
  `promo_id` int NOT NULL,
  `categoria_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_productos`
--

CREATE TABLE `promo_productos` (
  `promo_id` int NOT NULL,
  `producto_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proveedores`
--

CREATE TABLE `proveedores` (
  `proveedores_id` int NOT NULL,
  `nombre` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `rtn` char(14) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha` date NOT NULL,
  `departamentos_id` int NOT NULL,
  `municipios_id` int NOT NULL,
  `localidad` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `telefono` char(8) COLLATE utf8mb4_spanish_ci NOT NULL,
  `correo` char(70) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `proveedores`
--

INSERT INTO `proveedores` (`proveedores_id`, `nombre`, `rtn`, `fecha`, `departamentos_id`, `municipios_id`, `localidad`, `telefono`, `correo`, `estado`, `colaboradores_id`, `fecha_registro`) VALUES
(1, 'IZZY', '', '2025-06-30', 18, 295, '', '26340107', '', 1, 1, '2025-06-30 12:15:42');

-- --------------------------------------------------------

--
-- Table structure for table `puestos`
--

CREATE TABLE `puestos` (
  `puestos_id` int NOT NULL,
  `nombre` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `puestos`
--

INSERT INTO `puestos` (`puestos_id`, `nombre`, `estado`, `fecha_registro`) VALUES
(1, 'Administrador', 1, '2025-06-30 14:51:14'),
(2, 'Contabilidad', 1, '2025-06-30 14:51:14'),
(3, 'Caja', 1, '2025-06-30 16:59:14'),
(4, 'Reseller', 1, '2025-06-30 20:56:46'),
(5, 'Clientes', 1, '2025-06-30 14:08:39'),
(6, 'Vendedores', 1, '2025-06-30 14:08:39'),
(7, 'Psicologo', 1, '2025-06-30 11:05:58');

-- --------------------------------------------------------

--
-- Table structure for table `puntos_cliente`
--

CREATE TABLE `puntos_cliente` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `programa_puntos_id` int NOT NULL,
  `total_puntos` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurante_configuracion`
--

CREATE TABLE `restaurante_configuracion` (
  `empresa_id` int NOT NULL,
  `usar_mesas` tinyint(1) NOT NULL DEFAULT '1',
  `usar_comandas` tinyint(1) NOT NULL DEFAULT '1',
  `etiqueta_cocina` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Cocina',
  `etiqueta_barra` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Barra',
  `destino_comanda` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pantalla',
  `momento_ticket` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'enviar',
  `flujo_cocina` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pasos',
  `pantalla_cocina_activa` tinyint(1) NOT NULL DEFAULT '0',
  `solicitar_clave_gestion` tinyint(1) NOT NULL DEFAULT '1',
  `permitir_facturas_credito` tinyint(1) NOT NULL DEFAULT '0',
  `usuario_id` int NOT NULL DEFAULT '0',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurante_configuracion_historial`
--

CREATE TABLE `restaurante_configuracion_historial` (
  `historial_id` bigint NOT NULL,
  `server_customers_id` int NOT NULL DEFAULT '0',
  `empresa_id` int NOT NULL,
  `users_id` int NOT NULL DEFAULT '0',
  `colaborador_id` int NOT NULL DEFAULT '0',
  `categoria` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general',
  `resumen` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `cambios_json` longtext COLLATE utf8mb4_general_ci,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurante_pantalla_accesos`
--

CREATE TABLE `restaurante_pantalla_accesos` (
  `acceso_id` bigint NOT NULL,
  `server_customers_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cocina',
  `token_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `token_cifrado` text COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL,
  `fecha_regeneracion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurante_pantalla_dispositivos`
--

CREATE TABLE `restaurante_pantalla_dispositivos` (
  `dispositivo_id` bigint NOT NULL,
  `acceso_id` bigint NOT NULL,
  `dispositivo_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `oculto_vista` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_vinculacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_conexion` datetime DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL,
  `fecha_desvinculacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurante_pantalla_pruebas`
--

CREATE TABLE `restaurante_pantalla_pruebas` (
  `prueba_id` bigint NOT NULL,
  `acceso_id` bigint NOT NULL,
  `mensaje` varchar(160) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Prueba de conexión IZZY',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expira` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurante_pantalla_vinculos`
--

CREATE TABLE `restaurante_pantalla_vinculos` (
  `vinculo_id` bigint NOT NULL,
  `codigo` char(6) COLLATE utf8mb4_general_ci NOT NULL,
  `dispositivo_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `acceso_id` bigint DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `fecha_expira` datetime NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_vinculacion` datetime DEFAULT NULL,
  `fecha_consumo` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `secuencia_facturacion`
--

CREATE TABLE `secuencia_facturacion` (
  `secuencia_facturacion_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `cai` char(37) COLLATE utf8mb4_spanish_ci NOT NULL,
  `prefijo` char(15) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Número Inicial de la Factura',
  `relleno` int NOT NULL COMMENT 'Relleno de Numero',
  `incremento` int NOT NULL COMMENT 'Incremento del Numero',
  `siguiente` int NOT NULL COMMENT 'Número Siguiente',
  `rango_inicial` char(11) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Rango Autorizado Inicial',
  `rango_final` char(11) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Rango Autorizado Final',
  `fecha_activacion` date NOT NULL,
  `fecha_limite` date NOT NULL COMMENT 'Fecha Limite de Emisión',
  `activo` int NOT NULL COMMENT '1. Sí 2. No',
  `colaboradores_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `documento_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `secuencia_factura_fallida`
--

CREATE TABLE `secuencia_factura_fallida` (
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `documento_id` int NOT NULL,
  `numero` int NOT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_customers`
--

CREATE TABLE `server_customers` (
  `server_customers_id` int NOT NULL,
  `clientes_id` int NOT NULL,
  `codigo_cliente` int NOT NULL,
  `db` char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
  `planes_id` int NOT NULL,
  `sistema_id` int NOT NULL,
  `validar` int NOT NULL COMMENT '1 Sí 2. No',
  `estado` int NOT NULL COMMENT '1. Activo 0. Inactivo',
  `db_imported` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sistema`
--

CREATE TABLE `sistema` (
  `sistema_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `sistema`
--

INSERT INTO `sistema` (`sistema_id`, `nombre`, `estado`) VALUES
(1, 'IZZY', 1),
(2, 'CAMI', 1),
(3, 'Monitoring', 1);

-- --------------------------------------------------------

--
-- Table structure for table `submenu`
--

CREATE TABLE `submenu` (
  `submenu_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `name` char(25) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `fecha_registro` timestamp NOT NULL,
  `visible` tinyint(1) DEFAULT '1' COMMENT '1. Si 0. No'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Lista de todos los submenús de nivel 1 disponibles en el sistema';

--
-- Dumping data for table `submenu`
--

INSERT INTO `submenu` (`submenu_id`, `menu_id`, `name`, `descripcion`, `icon`, `orden`, `fecha_registro`, `visible`) VALUES
(1, 2, 'clientes', 'Clientes', 'fas fa-user fa-lg', 0, '2025-06-30 21:50:36', 1),
(2, 2, 'facturas', 'Facturas', 'fas fa-file-invoice fa-lg', 1, '2025-06-30 21:50:36', 1),
(4, 3, 'proveedores', 'Proveedores', 'fas fa-user-tie fa-lg', 0, '2025-06-30 21:50:36', 1),
(5, 3, 'facturaCompras', 'Compras', 'fas fa-store fa-lg', 1, '2025-06-30 21:50:36', 1),
(6, 4, 'productos', 'Productos', 'fab fa-product-hunt fa-lg', 0, '2025-06-30 21:50:36', 1),
(7, 4, 'inventario', 'Movimientos', 'fas fa-exchange-alt fa-lg', 1, '2025-06-30 21:50:36', 1),
(13, 6, 'reporte_historial', 'Historial', 'fas fa-history', 0, '2025-06-30 21:50:36', 1),
(14, 6, 'reporte_ventas', 'Ventas', 'fab fa-sellsy fa-lg', 1, '2025-06-30 21:50:36', 1),
(15, 6, 'reporte_compras', 'Compras', 'fas fa-store-alt fa-lg', 2, '2025-06-30 21:50:36', 1),
(16, 8, 'colaboradores', 'Colaboradores', 'fas fa-user-plus fa-lg', 0, '2025-06-30 21:50:36', 1),
(17, 7, 'puestos', 'Puestos', 'fas fa-briefcase fa-lg', 0, '2025-06-30 21:50:36', 1),
(18, 7, 'users', 'Usuarios', 'fas fa-user-cog fa-lg', 1, '2025-06-30 21:50:36', 1),
(19, 7, 'secuencia', 'Secuencia', 'fas fa-sliders-h fa-lg', 2, '2025-06-30 21:50:36', 1),
(20, 7, 'empresa', 'Empresa', 'fas fa-building fa-lg', 3, '2025-06-30 21:50:36', 1),
(21, 7, 'confAlmacen', 'Almacén', 'fa-solid fa-warehouse fa-lg', 4, '2025-06-30 21:50:36', 1),
(22, 7, 'confUbicacion', 'Ubicación', 'fas fa-search-location fa-lg', 5, '2025-06-30 21:50:36', 1),
(23, 7, 'confMedida', 'Medidas', 'fas fa-balance-scale-left fa-lg', 6, '2025-06-30 21:50:36', 1),
(3, 2, 'cajas', 'Cajas', 'fas fa-cash-register fa-lg', 2, '2025-06-30 21:50:36', 1),
(24, 7, 'privilegio', 'Privilegios', 'fas fa-key fa-lg', 7, '2025-06-30 21:50:36', 1),
(25, 7, 'tipoUser', 'Permisos', 'fas fa-user-lock fa-lg', 8, '2025-06-30 21:50:36', 1),
(8, 5, 'cuentasContabilidad', 'Cuentas', 'fas fa-receipt fa-lg', 0, '2025-06-30 21:50:36', 1),
(9, 5, 'movimientosContabilidad', 'Movimientos', 'fas fa-exchange-alt fa-lg', 1, '2025-06-30 21:50:36', 1),
(10, 5, 'ingresosContabilidad', 'Ingresos', 'fas fa-hand-holding-usd fa-lg', 2, '2025-06-30 21:50:36', 1),
(11, 5, 'gastosContabilidad', 'Gastos', 'fas fa-file-invoice-dollar fa-lg', 3, '2025-06-30 21:50:36', 1),
(12, 5, 'chequesContabilidad', 'Cheques', 'fas fa-money-check fa-lg', 4, '2025-06-30 21:50:36', 1),
(26, 7, 'confCategoria', 'Categoría', 'fas fa-layer-group fa-lg', 9, '2025-06-30 21:50:36', 1),
(27, 2, 'cotizacion', 'Cotización', 'fas fa-file-invoice-dollar fa-lg', 3, '2025-06-30 21:50:36', 1),
(28, 5, 'confCtaContabilidad', 'Conf Cuentas', 'fas fa-tasks fa-lg', 5, '2025-06-30 21:50:36', 1),
(29, 7, 'confEmail', 'Correo', 'fas fa-envelope fa-lg', 10, '2025-06-30 21:50:36', 1),
(30, 5, 'confTipoPago', 'Tipo de Pago', 'fab fa-bitcoin fa-lg', 6, '2025-06-30 21:50:36', 1),
(31, 5, 'confBancos', 'Bancos', 'fas fa-university fa-lg', 7, '2025-06-30 21:50:36', 1),
(32, 5, 'confImpuestos', 'Impuestos', 'fas fa-percentage fa-lg', 8, '2025-06-30 21:50:36', 1),
(33, 4, 'transferencia', 'Inventario', 'fas fa-boxes fa-lg', 2, '2025-06-30 21:50:36', 1),
(34, 7, 'confImpresora', 'Impresora', 'fa-solid fa-print fa-lg', 11, '2025-06-30 21:50:36', 1),
(35, 8, 'contrato', 'Contrato', 'fas fa-file-signature fa-lg', 1, '2025-06-30 21:50:36', 1),
(36, 8, 'nomina', 'Nomina', 'fas fa-money-check-alt fa-lg', 2, '2025-06-30 21:50:36', 1),
(37, 8, 'asistencia', 'Asistencia', 'fas fa-user-clock fa-lg', 3, '2025-06-30 21:50:36', 1),
(38, 7, 'programaPuntos', 'Puntos', 'fa-solid fa-money-bill-trend-up fa-lg', 12, '2025-06-30 20:24:10', 1),
(39, 9, 'registarMenus', 'Menus', 'fa-solid fa-bars fa-lg', 0, '2025-06-30 20:24:41', 1),
(40, 9, 'registrarPlanes', 'Planes', 'fa-solid fa-ranking-star fa-lg', 1, '2025-06-30 20:25:05', 1),
(41, 9, 'asignacionPlanes', 'Asignar Planes', 'fas fa-user-clock fa-lg', 2, '2025-06-30 20:25:30', 1),
(42, 2, 'facturaMovil', 'Factura Movil', 'fa-solid fa-file-invoice', 1, '2025-07-01 00:16:18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `submenu1`
--

CREATE TABLE `submenu1` (
  `submenu1_id` int NOT NULL,
  `submenu_id` int NOT NULL,
  `name` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `fecha_registro` timestamp NOT NULL,
  `visible` tinyint(1) DEFAULT '1' COMMENT '1. Si 0. No'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Lista de todos los submenús de nivel 2 disponibles en el sistema';

--
-- Dumping data for table `submenu1`
--

INSERT INTO `submenu1` (`submenu1_id`, `submenu_id`, `name`, `descripcion`, `icon`, `orden`, `fecha_registro`, `visible`) VALUES
(1, 13, 'historialAccesos', 'Accesos', 'fas fa-history fa-lg', 0, '2025-04-06 21:50:36', 1),
(2, 13, 'bitacora', 'Bitacora', 'fas fa-history fa-lg', 1, '2025-04-06 21:50:36', 1),
(3, 14, 'reporteVentas', 'Ventas', 'fas fa-file-invoice-dollar fa-lg', 0, '2025-04-06 21:50:36', 1),
(4, 14, 'cobrarClientes', 'CXC Clientes', 'fas fa-hand-holding-usd fa-lg', 1, '2025-04-06 21:50:36', 1),
(5, 15, 'reporteCompras', 'Compras', 'fas fa-shopping-cart fa-lg', 0, '2025-04-06 21:50:36', 1),
(6, 15, 'pagarProveedores', 'CXP Proveedores', 'fas fa-file-invoice fa-lg', 1, '2025-04-06 21:50:36', 1),
(7, 14, 'reporteCotizacion', 'Cotización', 'fas fa-file-signature fa-lg', 2, '2025-04-06 21:50:36', 1);

-- --------------------------------------------------------

--
-- Table structure for table `submenu1_plan`
--

CREATE TABLE `submenu1_plan` (
  `submenu1_plan_id` int NOT NULL,
  `submenu1_id` int NOT NULL,
  `planes_id` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Mostrar 0. Ocultar'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Contiene los submenús de nivel 2 disponibles por plan para mostrar al cliente según el plan activado';

--
-- Dumping data for table `submenu1_plan`
--

INSERT INTO `submenu1_plan` (`submenu1_plan_id`, `submenu1_id`, `planes_id`, `estado`) VALUES
(26, 1, 6, 1),
(35, 3, 6, 1),
(36, 5, 6, 1),
(37, 2, 6, 1),
(38, 4, 6, 1),
(39, 6, 6, 1),
(40, 7, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `submenu_plan`
--

CREATE TABLE `submenu_plan` (
  `submenu_plan_id` int NOT NULL,
  `submenu_id` int NOT NULL,
  `planes_id` int NOT NULL,
  `estado` int NOT NULL COMMENT '1. Mostrar 0. Ocultar'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Contiene los submenús de nivel 1 disponibles por plan para mostrar al cliente según el plan activado';

--
-- Dumping data for table `submenu_plan`
--

INSERT INTO `submenu_plan` (`submenu_plan_id`, `submenu_id`, `planes_id`, `estado`) VALUES
(164, 1, 6, 1),
(212, 2, 6, 1),
(213, 4, 6, 1),
(214, 5, 6, 1),
(215, 6, 6, 1),
(216, 7, 6, 1),
(217, 13, 6, 1),
(218, 14, 6, 1),
(219, 15, 6, 1),
(220, 16, 6, 1),
(221, 17, 6, 1),
(222, 18, 6, 1),
(223, 19, 6, 1),
(224, 20, 6, 1),
(225, 21, 6, 1),
(226, 22, 6, 1),
(227, 23, 6, 1),
(228, 3, 6, 1),
(229, 24, 6, 1),
(230, 25, 6, 1),
(231, 8, 6, 1),
(232, 9, 6, 1),
(233, 10, 6, 1),
(234, 11, 6, 1),
(235, 26, 6, 1),
(236, 27, 6, 1),
(237, 28, 6, 1),
(238, 29, 6, 1),
(239, 30, 6, 1),
(240, 31, 6, 1),
(241, 32, 6, 1),
(242, 33, 6, 1),
(243, 34, 6, 1),
(244, 35, 6, 1),
(245, 36, 6, 1),
(246, 37, 6, 1),
(247, 38, 6, 1),
(248, 39, 6, 0),
(249, 12, 6, 1),
(259, 42, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tipo_contrato`
--

CREATE TABLE `tipo_contrato` (
  `tipo_contrato_id` int NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `tipo_contrato`
--

INSERT INTO `tipo_contrato` (`tipo_contrato_id`, `nombre`) VALUES
(1, 'Permanente'),
(2, 'Temporal'),
(3, 'A Termino');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_cuenta`
--

CREATE TABLE `tipo_cuenta` (
  `tipo_cuenta_id` int NOT NULL,
  `nombre` varchar(40) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `tipo_cuenta`
--

INSERT INTO `tipo_cuenta` (`tipo_cuenta_id`, `nombre`, `estado`) VALUES
(1, 'Efectivo', 1),
(2, 'Tarjeta', 1),
(3, 'Banco', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tipo_empleado`
--

CREATE TABLE `tipo_empleado` (
  `tipo_empleado_id` int NOT NULL,
  `nombre` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `tipo_empleado`
--

INSERT INTO `tipo_empleado` (`tipo_empleado_id`, `nombre`) VALUES
(1, 'Normal'),
(2, 'Medico');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_nomina`
--

CREATE TABLE `tipo_nomina` (
  `tipo_nomina_id` int NOT NULL,
  `nombre` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `tipo_nomina`
--

INSERT INTO `tipo_nomina` (`tipo_nomina_id`, `nombre`) VALUES
(1, 'Nomina'),
(2, 'Catorceavo'),
(3, 'Treceavo');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_pago`
--

CREATE TABLE `tipo_pago` (
  `tipo_pago_id` int NOT NULL,
  `tipo_cuenta_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `cuentas_id` int NOT NULL COMMENT 'Asignación de Cuenta contable',
  `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
  `fecha_registro` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `tipo_pago`
--

INSERT INTO `tipo_pago` (`tipo_pago_id`, `tipo_cuenta_id`, `nombre`, `cuentas_id`, `estado`, `fecha_registro`) VALUES
(0, 0, 'Sin Pago', 0, 1, '2025-06-30 14:59:50'),
(1, 1, 'Efectivo', 2, 1, '2025-06-30 14:59:50'),
(2, 2, 'Tarjeta', 7, 1, '2025-06-30 14:59:50'),
(3, 3, 'Transferencia', 3, 1, '2025-06-30 14:59:50'),
(4, 4, 'Cheque', 3, 1, '2025-06-30 14:59:50');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_producto`
--

CREATE TABLE `tipo_producto` (
  `tipo_producto_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `tipo_producto`
--

INSERT INTO `tipo_producto` (`tipo_producto_id`, `nombre`) VALUES
(1, 'Producto'),
(2, 'Servicio'),
(3, 'Insumos');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_user`
--

CREATE TABLE `tipo_user` (
  `tipo_user_id` int NOT NULL,
  `nombre` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `tipo_user`
--

INSERT INTO `tipo_user` (`tipo_user_id`, `nombre`, `estado`, `fecha_registro`) VALUES
(1, 'Super Administrador', 1, '2025-06-30 00:30:57'),
(2, 'Administrador', 1, '2025-06-30 00:30:57'),
(3, 'Reseller', 1, '2025-06-30 17:32:21'),
(4, 'Clientes', 1, '2025-06-30 17:32:21'),
(5, 'Contabilidad', 1, '2025-08-24 07:50:36');

-- --------------------------------------------------------

--
-- Table structure for table `ubicacion`
--

CREATE TABLE `ubicacion` (
  `ubicacion_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `nombre` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `ubicacion`
--

INSERT INTO `ubicacion` (`ubicacion_id`, `empresa_id`, `nombre`, `estado`, `fecha_registro`) VALUES
(1, 1, 'San Pedro Sula', 1, '2025-06-30 15:19:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `users_id` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `privilegio_id` int NOT NULL,
  `username` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `password` char(48) COLLATE utf8mb4_spanish_ci NOT NULL,
  `email` char(45) COLLATE utf8mb4_spanish_ci NOT NULL,
  `tipo_user_id` int NOT NULL COMMENT 'Permisos según el tipo de usuario que ingresa al sistema',
  `estado` int NOT NULL COMMENT '1. Activo. 2. Inactivo',
  `fecha_registro` datetime NOT NULL,
  `empresa_id` int NOT NULL,
  `server_customers_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`users_id`, `colaboradores_id`, `privilegio_id`, `username`, `password`, `email`, `tipo_user_id`, `estado`, `fecha_registro`, `empresa_id`, `server_customers_id`) VALUES
(1, 1, 1, 'admin', 'ZGlaeUpvaXNOeHhuSjl4Q0NHb3FLQT09', '', 1, 1, '2025-06-30 16:39:41', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vale`
--

CREATE TABLE `vale` (
  `vale_id` int NOT NULL,
  `nomina_id` int NOT NULL,
  `colaboradores_id` int NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `nota` varchar(254) COLLATE utf8mb4_spanish_ci NOT NULL,
  `usuario` int NOT NULL,
  `estado` int NOT NULL COMMENT '0. Pendiente 1. Pagado 2. Anulado',
  `empresa_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vigencia_cotizacion`
--

CREATE TABLE `vigencia_cotizacion` (
  `vigencia_cotizacion_id` int NOT NULL,
  `valor` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha_registro` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Dumping data for table `vigencia_cotizacion`
--

INSERT INTO `vigencia_cotizacion` (`vigencia_cotizacion_id`, `valor`, `fecha_registro`) VALUES
(0, 'Sin Vigencia', '2025-06-30 23:57:53'),
(1, '5 Días', '2025-06-30 23:57:53'),
(2, '10 Días', '2025-06-30 23:57:53'),
(3, '15 Días', '2025-06-30 23:57:53'),
(4, '20 Días', '2025-06-30 23:57:53'),
(5, '25 Días', '2025-06-30 23:57:53'),
(6, '30 Días', '2025-06-30 23:57:53'),
(7, '35 Días', '2025-06-30 23:57:53'),
(8, '40 Días', '2025-06-30 23:57:53'),
(9, '45 Días', '2025-06-30 23:57:53'),
(10, '50 Días', '2025-06-30 23:57:53'),
(11, '55 Días', '2025-06-30 23:57:53');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_promos_vigentes_producto`
-- (See below for the actual view)
--
CREATE TABLE `v_promos_vigentes_producto` (
`promo_id` int
,`empresa_id` int
,`producto_id` int
,`tipo_descuento` varchar(5)
,`valor` decimal(12,2)
,`prioridad` int
,`acumula_con_mayoreo` tinyint
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `acceso_menu`
--
ALTER TABLE `acceso_menu`
  ADD PRIMARY KEY (`acceso_menu_id`);

--
-- Indexes for table `acceso_submenu`
--
ALTER TABLE `acceso_submenu`
  ADD PRIMARY KEY (`acceso_submenu_id`);

--
-- Indexes for table `acceso_submenu1`
--
ALTER TABLE `acceso_submenu1`
  ADD PRIMARY KEY (`acceso_submenu1_id`);

--
-- Indexes for table `almacen`
--
ALTER TABLE `almacen`
  ADD PRIMARY KEY (`almacen_id`),
  ADD KEY `ubicacion_id` (`ubicacion_id`);

--
-- Indexes for table `apertura`
--
ALTER TABLE `apertura`
  ADD PRIMARY KEY (`apertura_id`);

--
-- Indexes for table `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`asistencia_id`);

--
-- Indexes for table `auditoria_admin_autorizaciones`
--
ALTER TABLE `auditoria_admin_autorizaciones`
  ADD PRIMARY KEY (`auditoria_admin_id`),
  ADD KEY `idx_empresa_fecha` (`empresa_id`,`fecha_registro`),
  ADD KEY `idx_admin_users` (`admin_users_id`),
  ADD KEY `idx_usuario_sesion` (`usuario_sesion_id`),
  ADD KEY `idx_modulo_accion` (`modulo`,`accion`),
  ADD KEY `idx_permitido` (`permitido`);

--
-- Indexes for table `banco`
--
ALTER TABLE `banco`
  ADD PRIMARY KEY (`banco_id`);

--
-- Indexes for table `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`bitacora_id`),
  ADD KEY `colaborador_id` (`colaboradores_id`);

--
-- Indexes for table `caja_retiros`
--
ALTER TABLE `caja_retiros`
  ADD PRIMARY KEY (`caja_retiros_id`);

--
-- Indexes for table `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`categoria_id`);

--
-- Indexes for table `categoria_gastos`
--
ALTER TABLE `categoria_gastos`
  ADD PRIMARY KEY (`categoria_gastos_id`);

--
-- Indexes for table `cheque`
--
ALTER TABLE `cheque`
  ADD PRIMARY KEY (`cheque_id`),
  ADD UNIQUE KEY `ux_cheque_empresa_numero` (`empresa_id`,`numero_cheque`),
  ADD KEY `idx_cheque_empresa_fecha` (`empresa_id`,`fecha`),
  ADD KEY `idx_cheque_cuenta` (`cuentas_id`),
  ADD KEY `idx_cheque_proveedor` (`proveedores_id`),
  ADD KEY `idx_cheque_categoria` (`categoria_gastos_id`),
  ADD KEY `idx_cheque_egreso` (`egresos_id`),
  ADD KEY `idx_cheque_estado` (`estado`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`clientes_id`),
  ADD KEY `departamentos_id` (`departamentos_id`),
  ADD KEY `municipios_id` (`municipios_id`);

--
-- Indexes for table `cobrar_clientes`
--
ALTER TABLE `cobrar_clientes`
  ADD PRIMARY KEY (`cobrar_clientes_id`);

--
-- Indexes for table `colaboradores`
--
ALTER TABLE `colaboradores`
  ADD PRIMARY KEY (`colaboradores_id`),
  ADD KEY `FK_puestos_id` (`puestos_id`) USING BTREE;

--
-- Indexes for table `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`combo_id`),
  ADD UNIQUE KEY `uq_combo_producto` (`productos_id`);

--
-- Indexes for table `combo_categoria_regla`
--
ALTER TABLE `combo_categoria_regla`
  ADD PRIMARY KEY (`combo_categoria_regla_id`),
  ADD UNIQUE KEY `uq_combo_categoria` (`combo_id`,`categoria_id`),
  ADD KEY `idx_ccr_combo` (`combo_id`),
  ADD KEY `idx_ccr_categoria` (`categoria_id`);

--
-- Indexes for table `combo_detalle`
--
ALTER TABLE `combo_detalle`
  ADD PRIMARY KEY (`combo_detalle_id`),
  ADD KEY `idx_detalle_combo` (`combo_id`),
  ADD KEY `idx_detalle_producto` (`productos_id`);

--
-- Indexes for table `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`compras_id`);

--
-- Indexes for table `compras_detalles`
--
ALTER TABLE `compras_detalles`
  ADD PRIMARY KEY (`compras_detalles_id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`config_id`);

--
-- Indexes for table `config_lista_blanca`
--
ALTER TABLE `config_lista_blanca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_config` (`nombre_config`);

--
-- Indexes for table `contrato`
--
ALTER TABLE `contrato`
  ADD PRIMARY KEY (`contrato_id`);

--
-- Indexes for table `correo`
--
ALTER TABLE `correo`
  ADD PRIMARY KEY (`correo_id`);

--
-- Indexes for table `correo_tipo`
--
ALTER TABLE `correo_tipo`
  ADD PRIMARY KEY (`correo_tipo_id`);

--
-- Indexes for table `cotizacion`
--
ALTER TABLE `cotizacion`
  ADD PRIMARY KEY (`cotizacion_id`),
  ADD KEY `clientes_id` (`clientes_id`),
  ADD KEY `colaborador_id` (`colaboradores_id`),
  ADD KEY `usuario` (`usuario`);

--
-- Indexes for table `cotizacion_detalles`
--
ALTER TABLE `cotizacion_detalles`
  ADD PRIMARY KEY (`cotizacion_detalle_id`),
  ADD KEY `productos_id` (`productos_id`),
  ADD KEY `facturas_id` (`cotizacion_id`);

--
-- Indexes for table `cuentas`
--
ALTER TABLE `cuentas`
  ADD PRIMARY KEY (`cuentas_id`);

--
-- Indexes for table `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`departamentos_id`);

--
-- Indexes for table `diarios`
--
ALTER TABLE `diarios`
  ADD PRIMARY KEY (`diarios_id`);

--
-- Indexes for table `documento`
--
ALTER TABLE `documento`
  ADD PRIMARY KEY (`documento_id`);

--
-- Indexes for table `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`egresos_id`);

--
-- Indexes for table `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`empresa_id`);

--
-- Indexes for table `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`facturas_id`),
  ADD UNIQUE KEY `uq_facturas_numero_fiscal_emitido` (`empresa_id`,`secuencia_facturacion_id`,`numero_fiscal_unico`),
  ADD KEY `clientes_id` (`clientes_id`),
  ADD KEY `colaborador_id` (`colaboradores_id`),
  ADD KEY `secuencia_facturacion_id` (`secuencia_facturacion_id`),
  ADD KEY `usuario` (`usuario`);

--
-- Indexes for table `facturas_detalles`
--
ALTER TABLE `facturas_detalles`
  ADD PRIMARY KEY (`facturas_detalle_id`),
  ADD KEY `productos_id` (`productos_id`),
  ADD KEY `facturas_id` (`facturas_id`);

--
-- Indexes for table `facturas_modificaciones`
--
ALTER TABLE `facturas_modificaciones`
  ADD PRIMARY KEY (`facturas_modificaciones_id`),
  ADD KEY `idx_facturas_modificaciones_factura` (`facturas_id`),
  ADD KEY `idx_facturas_modificaciones_empresa` (`empresa_id`),
  ADD KEY `idx_facturas_modificaciones_usuario` (`usuario_id`),
  ADD KEY `idx_facturas_modificaciones_fecha` (`fecha_registro`);

--
-- Indexes for table `facturas_proforma`
--
ALTER TABLE `facturas_proforma`
  ADD PRIMARY KEY (`facturas_proforma_id`);

--
-- Indexes for table `facturas_recurrentes`
--
ALTER TABLE `facturas_recurrentes`
  ADD PRIMARY KEY (`rec_id`),
  ADD UNIQUE KEY `empresa_id` (`empresa_id`,`rec_id`);

--
-- Indexes for table `facturas_recurrentes_detalle`
--
ALTER TABLE `facturas_recurrentes_detalle`
  ADD PRIMARY KEY (`rec_detalle_id`),
  ADD KEY `rec_id` (`rec_id`);

--
-- Indexes for table `facturas_recurrentes_ejecuciones`
--
ALTER TABLE `facturas_recurrentes_ejecuciones`
  ADD PRIMARY KEY (`ejecucion_id`),
  ADD UNIQUE KEY `uq_rec_fecha` (`rec_id`,`scheduled_at`),
  ADD KEY `idx_ejecucion_factura` (`facturas_id`);

--
-- Indexes for table `factura_comanda`
--
ALTER TABLE `factura_comanda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `mesa_id` (`mesa_id`);

--
-- Indexes for table `factura_comanda_items`
--
ALTER TABLE `factura_comanda_items`
  ADD PRIMARY KEY (`comanda_item_id`),
  ADD KEY `idx_fci_factura_estado` (`factura_id`,`estado`),
  ADD KEY `idx_fci_estacion_estado` (`estacion`,`estado`),
  ADD KEY `idx_fci_factura_producto` (`factura_id`,`productos_id`);

--
-- Indexes for table `factura_restaurante_cuentas`
--
ALTER TABLE `factura_restaurante_cuentas`
  ADD PRIMARY KEY (`factura_id`),
  ADD KEY `idx_frc_empresa_estado` (`empresa_id`,`estado`),
  ADD KEY `idx_frc_mesa_estado` (`mesa_id`,`estado`);

--
-- Indexes for table `historial`
--
ALTER TABLE `historial`
  ADD PRIMARY KEY (`historial_id`);

--
-- Indexes for table `historial_acceso`
--
ALTER TABLE `historial_acceso`
  ADD PRIMARY KEY (`historial_acceso_id`),
  ADD KEY `FK_colaborador_id` (`colaboradores_id`);

--
-- Indexes for table `historial_puntos`
--
ALTER TABLE `historial_puntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `programa_puntos_id` (`programa_puntos_id`);

--
-- Indexes for table `impresora`
--
ALTER TABLE `impresora`
  ADD PRIMARY KEY (`impresora_id`);

--
-- Indexes for table `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`ingresos_id`);

--
-- Indexes for table `inventario_ajustes`
--
ALTER TABLE `inventario_ajustes`
  ADD PRIMARY KEY (`inventario_ajustes_id`);

--
-- Indexes for table `isv`
--
ALTER TABLE `isv`
  ADD PRIMARY KEY (`isv_id`);

--
-- Indexes for table `isv_tipo`
--
ALTER TABLE `isv_tipo`
  ADD PRIMARY KEY (`isv_tipo_id`);

--
-- Indexes for table `jobs_queue`
--
ALTER TABLE `jobs_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`lote_id`),
  ADD UNIQUE KEY `numero_lote` (`numero_lote`),
  ADD KEY `productos_id` (`productos_id`),
  ADD KEY `almacen_id` (`almacen_id`),
  ADD KEY `fk_empresa_lotes` (`empresa_id`);

--
-- Indexes for table `medida`
--
ALTER TABLE `medida`
  ADD PRIMARY KEY (`medida_id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `menu_plan`
--
ALTER TABLE `menu_plan`
  ADD PRIMARY KEY (`menu_plan_id`);

--
-- Indexes for table `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`mesa_id`),
  ADD UNIQUE KEY `numero` (`numero`,`empresa_id`);

--
-- Indexes for table `mesas_reservas`
--
ALTER TABLE `mesas_reservas`
  ADD PRIMARY KEY (`mesa_reserva_id`),
  ADD KEY `idx_mr_mesa_estado` (`mesa_id`,`estado`),
  ADD KEY `idx_mr_cliente` (`clientes_id`),
  ADD KEY `idx_mr_empresa_estado` (`empresa_id`,`estado`),
  ADD KEY `idx_mr_fecha` (`fecha_reserva`,`hora_reserva`);

--
-- Indexes for table `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`movimientos_id`),
  ADD KEY `productos_id` (`productos_id`),
  ADD KEY `lote_id` (`lote_id`);

--
-- Indexes for table `movimientos_cuentas`
--
ALTER TABLE `movimientos_cuentas`
  ADD PRIMARY KEY (`movimientos_cuentas_id`);

--
-- Indexes for table `municipios`
--
ALTER TABLE `municipios`
  ADD PRIMARY KEY (`municipios_id`),
  ADD KEY `departamentos_id` (`departamentos_id`);

--
-- Indexes for table `nomina`
--
ALTER TABLE `nomina`
  ADD PRIMARY KEY (`nomina_id`);

--
-- Indexes for table `nomina_detalles`
--
ALTER TABLE `nomina_detalles`
  ADD PRIMARY KEY (`nomina_detalles_id`);

--
-- Indexes for table `notas_credito`
--
ALTER TABLE `notas_credito`
  ADD PRIMARY KEY (`nota_credito_id`),
  ADD UNIQUE KEY `uq_nc_numero_fiscal` (`empresa_id`,`secuencia_facturacion_id`,`number`),
  ADD KEY `idx_nc_factura` (`empresa_id`,`facturas_id`,`estado`),
  ADD KEY `idx_nc_cliente` (`empresa_id`,`clientes_id`),
  ADD KEY `idx_nc_fecha` (`empresa_id`,`fecha`);

--
-- Indexes for table `notas_credito_aplicaciones`
--
ALTER TABLE `notas_credito_aplicaciones`
  ADD PRIMARY KEY (`nota_credito_aplicacion_id`),
  ADD UNIQUE KEY `uq_nc_aplicacion_factura` (`nota_credito_id`,`facturas_id_destino`),
  ADD KEY `idx_nc_aplicacion_cliente` (`empresa_id`,`clientes_id`,`estado`),
  ADD KEY `idx_nc_aplicacion_destino` (`empresa_id`,`facturas_id_destino`);

--
-- Indexes for table `notas_credito_detalle`
--
ALTER TABLE `notas_credito_detalle`
  ADD PRIMARY KEY (`nota_credito_detalle_id`),
  ADD KEY `idx_ncd_nota` (`nota_credito_id`),
  ADD KEY `idx_ncd_factura_detalle` (`facturas_detalle_id`);

--
-- Indexes for table `notas_credito_inventario`
--
ALTER TABLE `notas_credito_inventario`
  ADD PRIMARY KEY (`nota_credito_inventario_id`),
  ADD UNIQUE KEY `uq_nc_inventario_mov` (`nota_credito_id`,`movimiento_origen_id`),
  ADD KEY `idx_nc_inv_origen` (`movimiento_origen_id`,`estado`),
  ADD KEY `idx_nc_inv_factura` (`empresa_id`,`nota_credito_id`);

--
-- Indexes for table `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`notificaciones_id`);

--
-- Indexes for table `pagar_proveedores`
--
ALTER TABLE `pagar_proveedores`
  ADD PRIMARY KEY (`pagar_proveedores_id`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`pagos_id`),
  ADD KEY `facturas_id` (`facturas_id`);

--
-- Indexes for table `pagoscompras`
--
ALTER TABLE `pagoscompras`
  ADD PRIMARY KEY (`pagoscompras_id`),
  ADD KEY `facturas_id` (`compras_id`);

--
-- Indexes for table `pagoscompras_detalles`
--
ALTER TABLE `pagoscompras_detalles`
  ADD PRIMARY KEY (`pagoscompras_detalles_id`),
  ADD KEY `pagos_id` (`pagoscompras_id`),
  ADD KEY `tipo_pago_id` (`tipo_pago_id`),
  ADD KEY `banco_id` (`banco_id`);

--
-- Indexes for table `pagos_detalles`
--
ALTER TABLE `pagos_detalles`
  ADD PRIMARY KEY (`pagos_detalles_id`),
  ADD KEY `pagos_id` (`pagos_id`),
  ADD KEY `tipo_pago_id` (`tipo_pago_id`),
  ADD KEY `banco_id` (`banco_id`);

--
-- Indexes for table `pago_planificado`
--
ALTER TABLE `pago_planificado`
  ADD PRIMARY KEY (`pago_planificado_id`);

--
-- Indexes for table `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`permisos_id`);

--
-- Indexes for table `pin`
--
ALTER TABLE `pin`
  ADD PRIMARY KEY (`pin_id`),
  ADD KEY `idx_pin_login` (`codigo_cliente`,`pin`,`fecha_hora_fin`),
  ADD KEY `idx_pin_cliente` (`server_customers_id`,`codigo_cliente`,`fecha_hora_fin`);

--
-- Indexes for table `plan`
--
ALTER TABLE `plan`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`planes_id`);

--
-- Indexes for table `precio_factura`
--
ALTER TABLE `precio_factura`
  ADD PRIMARY KEY (`precio_factura_id`);

--
-- Indexes for table `price_list`
--
ALTER TABLE `price_list`
  ADD PRIMARY KEY (`price_list_id`);

--
-- Indexes for table `privilegio`
--
ALTER TABLE `privilegio`
  ADD PRIMARY KEY (`privilegio_id`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`productos_id`),
  ADD KEY `almacen_id` (`almacen_id`),
  ADD KEY `medida_id` (`medida_id`),
  ADD KEY `colaborador_id` (`colaborador_id`),
  ADD KEY `idx_productos_restaurante` (`restaurante`),
  ADD KEY `idx_productos_isv1` (`isv1`),
  ADD KEY `idx_productos_isv2` (`isv2`);

--
-- Indexes for table `programa_puntos`
--
ALTER TABLE `programa_puntos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`promo_id`),
  ADD KEY `ix_promos_vigencia` (`empresa_id`,`estado`,`fecha_inicio`,`fecha_fin`,`prioridad`);

--
-- Indexes for table `promo_categorias`
--
ALTER TABLE `promo_categorias`
  ADD PRIMARY KEY (`promo_id`,`categoria_id`),
  ADD KEY `ix_pc_cat` (`categoria_id`);

--
-- Indexes for table `promo_productos`
--
ALTER TABLE `promo_productos`
  ADD PRIMARY KEY (`promo_id`,`producto_id`),
  ADD KEY `ix_pp_prod` (`producto_id`);

--
-- Indexes for table `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`proveedores_id`),
  ADD KEY `departamentos_id` (`departamentos_id`),
  ADD KEY `municipios_id` (`municipios_id`);

--
-- Indexes for table `puestos`
--
ALTER TABLE `puestos`
  ADD PRIMARY KEY (`puestos_id`);

--
-- Indexes for table `puntos_cliente`
--
ALTER TABLE `puntos_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `programa_puntos_id` (`programa_puntos_id`);

--
-- Indexes for table `restaurante_configuracion`
--
ALTER TABLE `restaurante_configuracion`
  ADD PRIMARY KEY (`empresa_id`);

--
-- Indexes for table `restaurante_configuracion_historial`
--
ALTER TABLE `restaurante_configuracion_historial`
  ADD PRIMARY KEY (`historial_id`),
  ADD KEY `idx_rest_cfg_hist_empresa` (`server_customers_id`,`empresa_id`,`fecha_registro`);

--
-- Indexes for table `restaurante_pantalla_accesos`
--
ALTER TABLE `restaurante_pantalla_accesos`
  ADD PRIMARY KEY (`acceso_id`),
  ADD UNIQUE KEY `uq_rest_pantalla_empresa_tipo` (`server_customers_id`,`empresa_id`,`tipo`),
  ADD UNIQUE KEY `uq_rest_pantalla_token_hash` (`token_hash`),
  ADD KEY `idx_rest_pantalla_resolver` (`token_hash`,`tipo`,`activo`),
  ADD KEY `idx_rest_pantalla_cliente` (`server_customers_id`,`empresa_id`);

--
-- Indexes for table `restaurante_pantalla_dispositivos`
--
ALTER TABLE `restaurante_pantalla_dispositivos`
  ADD PRIMARY KEY (`dispositivo_id`),
  ADD UNIQUE KEY `uq_rest_pantalla_dispositivo` (`acceso_id`,`dispositivo_hash`),
  ADD KEY `idx_rest_pantalla_disp_acceso` (`acceso_id`,`activo`),
  ADD KEY `idx_rest_pantalla_disp_ultima` (`ultima_conexion`);

--
-- Indexes for table `restaurante_pantalla_pruebas`
--
ALTER TABLE `restaurante_pantalla_pruebas`
  ADD PRIMARY KEY (`prueba_id`),
  ADD KEY `idx_rest_pantalla_prueba` (`acceso_id`,`fecha_expira`);

--
-- Indexes for table `restaurante_pantalla_vinculos`
--
ALTER TABLE `restaurante_pantalla_vinculos`
  ADD PRIMARY KEY (`vinculo_id`),
  ADD UNIQUE KEY `uq_rest_pantalla_vinculo_codigo` (`codigo`),
  ADD KEY `idx_rest_pantalla_vinculo_disp` (`dispositivo_hash`,`estado`),
  ADD KEY `idx_rest_pantalla_vinculo_acceso` (`acceso_id`,`estado`),
  ADD KEY `idx_rest_pantalla_vinculo_expira` (`fecha_expira`);

--
-- Indexes for table `secuencia_facturacion`
--
ALTER TABLE `secuencia_facturacion`
  ADD PRIMARY KEY (`secuencia_facturacion_id`),
  ADD KEY `FK_empresa_id` (`empresa_id`);

--
-- Indexes for table `secuencia_factura_fallida`
--
ALTER TABLE `secuencia_factura_fallida`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `empresa_id` (`empresa_id`,`documento_id`,`numero`);

--
-- Indexes for table `server_customers`
--
ALTER TABLE `server_customers`
  ADD PRIMARY KEY (`server_customers_id`);

--
-- Indexes for table `sistema`
--
ALTER TABLE `sistema`
  ADD PRIMARY KEY (`sistema_id`);

--
-- Indexes for table `submenu`
--
ALTER TABLE `submenu`
  ADD PRIMARY KEY (`submenu_id`);

--
-- Indexes for table `submenu1`
--
ALTER TABLE `submenu1`
  ADD PRIMARY KEY (`submenu1_id`);

--
-- Indexes for table `submenu1_plan`
--
ALTER TABLE `submenu1_plan`
  ADD PRIMARY KEY (`submenu1_plan_id`);

--
-- Indexes for table `submenu_plan`
--
ALTER TABLE `submenu_plan`
  ADD PRIMARY KEY (`submenu_plan_id`);

--
-- Indexes for table `tipo_contrato`
--
ALTER TABLE `tipo_contrato`
  ADD PRIMARY KEY (`tipo_contrato_id`);

--
-- Indexes for table `tipo_cuenta`
--
ALTER TABLE `tipo_cuenta`
  ADD PRIMARY KEY (`tipo_cuenta_id`);

--
-- Indexes for table `tipo_empleado`
--
ALTER TABLE `tipo_empleado`
  ADD PRIMARY KEY (`tipo_empleado_id`);

--
-- Indexes for table `tipo_nomina`
--
ALTER TABLE `tipo_nomina`
  ADD PRIMARY KEY (`tipo_nomina_id`);

--
-- Indexes for table `tipo_pago`
--
ALTER TABLE `tipo_pago`
  ADD PRIMARY KEY (`tipo_pago_id`);

--
-- Indexes for table `tipo_producto`
--
ALTER TABLE `tipo_producto`
  ADD PRIMARY KEY (`tipo_producto_id`);

--
-- Indexes for table `tipo_user`
--
ALTER TABLE `tipo_user`
  ADD PRIMARY KEY (`tipo_user_id`);

--
-- Indexes for table `ubicacion`
--
ALTER TABLE `ubicacion`
  ADD PRIMARY KEY (`ubicacion_id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`users_id`),
  ADD KEY `FK_colaborador_id` (`colaboradores_id`),
  ADD KEY `FK_empresa_id` (`empresa_id`),
  ADD KEY `FK_tipo_user_id` (`tipo_user_id`);

--
-- Indexes for table `vale`
--
ALTER TABLE `vale`
  ADD PRIMARY KEY (`vale_id`);

--
-- Indexes for table `vigencia_cotizacion`
--
ALTER TABLE `vigencia_cotizacion`
  ADD PRIMARY KEY (`vigencia_cotizacion_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `acceso_menu`
--
ALTER TABLE `acceso_menu`
  MODIFY `acceso_menu_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `acceso_submenu`
--
ALTER TABLE `acceso_submenu`
  MODIFY `acceso_submenu_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=206;

--
-- AUTO_INCREMENT for table `acceso_submenu1`
--
ALTER TABLE `acceso_submenu1`
  MODIFY `acceso_submenu1_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `asistencia_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_admin_autorizaciones`
--
ALTER TABLE `auditoria_admin_autorizaciones`
  MODIFY `auditoria_admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `caja_retiros`
--
ALTER TABLE `caja_retiros`
  MODIFY `caja_retiros_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `combos`
--
ALTER TABLE `combos`
  MODIFY `combo_id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del combo';

--
-- AUTO_INCREMENT for table `combo_categoria_regla`
--
ALTER TABLE `combo_categoria_regla`
  MODIFY `combo_categoria_regla_id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la regla de categoría';

--
-- AUTO_INCREMENT for table `combo_detalle`
--
ALTER TABLE `combo_detalle`
  MODIFY `combo_detalle_id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del detalle del combo';

--
-- AUTO_INCREMENT for table `config_lista_blanca`
--
ALTER TABLE `config_lista_blanca`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `facturas_modificaciones`
--
ALTER TABLE `facturas_modificaciones`
  MODIFY `facturas_modificaciones_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facturas_recurrentes`
--
ALTER TABLE `facturas_recurrentes`
  MODIFY `rec_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `facturas_recurrentes_detalle`
--
ALTER TABLE `facturas_recurrentes_detalle`
  MODIFY `rec_detalle_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `facturas_recurrentes_ejecuciones`
--
ALTER TABLE `facturas_recurrentes_ejecuciones`
  MODIFY `ejecucion_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `factura_comanda`
--
ALTER TABLE `factura_comanda`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `factura_comanda_items`
--
ALTER TABLE `factura_comanda_items`
  MODIFY `comanda_item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historial_puntos`
--
ALTER TABLE `historial_puntos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_ajustes`
--
ALTER TABLE `inventario_ajustes`
  MODIFY `inventario_ajustes_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs_queue`
--
ALTER TABLE `jobs_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lotes`
--
ALTER TABLE `lotes`
  MODIFY `lote_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `menu_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `menu_plan`
--
ALTER TABLE `menu_plan`
  MODIFY `menu_plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `mesas`
--
ALTER TABLE `mesas`
  MODIFY `mesa_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mesas_reservas`
--
ALTER TABLE `mesas_reservas`
  MODIFY `mesa_reserva_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `movimientos_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notas_credito`
--
ALTER TABLE `notas_credito`
  MODIFY `nota_credito_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notas_credito_aplicaciones`
--
ALTER TABLE `notas_credito_aplicaciones`
  MODIFY `nota_credito_aplicacion_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notas_credito_detalle`
--
ALTER TABLE `notas_credito_detalle`
  MODIFY `nota_credito_detalle_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notas_credito_inventario`
--
ALTER TABLE `notas_credito_inventario`
  MODIFY `nota_credito_inventario_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pin`
--
ALTER TABLE `pin`
  MODIFY `pin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62785;

--
-- AUTO_INCREMENT for table `plan`
--
ALTER TABLE `plan`
  MODIFY `plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `planes`
--
ALTER TABLE `planes`
  MODIFY `planes_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `programa_puntos`
--
ALTER TABLE `programa_puntos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones`
--
ALTER TABLE `promociones`
  MODIFY `promo_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `puntos_cliente`
--
ALTER TABLE `puntos_cliente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurante_configuracion_historial`
--
ALTER TABLE `restaurante_configuracion_historial`
  MODIFY `historial_id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurante_pantalla_accesos`
--
ALTER TABLE `restaurante_pantalla_accesos`
  MODIFY `acceso_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restaurante_pantalla_dispositivos`
--
ALTER TABLE `restaurante_pantalla_dispositivos`
  MODIFY `dispositivo_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restaurante_pantalla_pruebas`
--
ALTER TABLE `restaurante_pantalla_pruebas`
  MODIFY `prueba_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restaurante_pantalla_vinculos`
--
ALTER TABLE `restaurante_pantalla_vinculos`
  MODIFY `vinculo_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `secuencia_factura_fallida`
--
ALTER TABLE `secuencia_factura_fallida`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submenu`
--
ALTER TABLE `submenu`
  MODIFY `submenu_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `submenu1`
--
ALTER TABLE `submenu1`
  MODIFY `submenu1_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `submenu1_plan`
--
ALTER TABLE `submenu1_plan`
  MODIFY `submenu1_plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `submenu_plan`
--
ALTER TABLE `submenu_plan`
  MODIFY `submenu_plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=284;

-- --------------------------------------------------------

--
-- Structure for view `v_promos_vigentes_producto`
--
DROP TABLE IF EXISTS `v_promos_vigentes_producto`;

CREATE ALGORITHM=UNDEFINED DEFINER=`esmultiservicios`@`localhost` SQL SECURITY DEFINER VIEW `v_promos_vigentes_producto`  AS SELECT `pr`.`promo_id` AS `promo_id`, `pr`.`empresa_id` AS `empresa_id`, `p`.`productos_id` AS `producto_id`, `pr`.`tipo_descuento` AS `tipo_descuento`, `pr`.`valor` AS `valor`, `pr`.`prioridad` AS `prioridad`, `pr`.`acumula_con_mayoreo` AS `acumula_con_mayoreo` FROM ((`promociones` `pr` join `promo_productos` `pp` on((`pp`.`promo_id` = `pr`.`promo_id`))) join `productos` `p` on((`p`.`productos_id` = `pp`.`producto_id`))) WHERE ((`pr`.`estado` = 1) AND (now() between `pr`.`fecha_inicio` and `pr`.`fecha_fin`) AND ((`pr`.`hora_inicio` is null) OR (`pr`.`hora_fin` is null) OR (cast(now() as time) between `pr`.`hora_inicio` and `pr`.`hora_fin`)) AND ((`pr`.`dias_semana` is null) OR (find_in_set(lower(dayname(now())),`pr`.`dias_semana`) > 0)))union all select `pr`.`promo_id` AS `promo_id`,`pr`.`empresa_id` AS `empresa_id`,`p`.`productos_id` AS `producto_id`,`pr`.`tipo_descuento` AS `tipo_descuento`,`pr`.`valor` AS `valor`,`pr`.`prioridad` AS `prioridad`,`pr`.`acumula_con_mayoreo` AS `acumula_con_mayoreo` from ((`promociones` `pr` join `promo_categorias` `pc` on((`pc`.`promo_id` = `pr`.`promo_id`))) join `productos` `p` on((`p`.`categoria_id` = `pc`.`categoria_id`))) where ((`pr`.`estado` = 1) and (now() between `pr`.`fecha_inicio` and `pr`.`fecha_fin`) and ((`pr`.`hora_inicio` is null) or (`pr`.`hora_fin` is null) or (cast(now() as time) between `pr`.`hora_inicio` and `pr`.`hora_fin`)) and ((`pr`.`dias_semana` is null) or (find_in_set(lower(dayname(now())),`pr`.`dias_semana`) > 0))) union all select `pr`.`promo_id` AS `promo_id`,`pr`.`empresa_id` AS `empresa_id`,`p`.`productos_id` AS `producto_id`,`pr`.`tipo_descuento` AS `tipo_descuento`,`pr`.`valor` AS `valor`,`pr`.`prioridad` AS `prioridad`,`pr`.`acumula_con_mayoreo` AS `acumula_con_mayoreo` from (`promociones` `pr` join `productos` `p` on((`p`.`empresa_id` = `pr`.`empresa_id`))) where ((`pr`.`estado` = 1) and (`pr`.`aplica_a` = 'TODOS') and (now() between `pr`.`fecha_inicio` and `pr`.`fecha_fin`) and ((`pr`.`hora_inicio` is null) or (`pr`.`hora_fin` is null) or (cast(now() as time) between `pr`.`hora_inicio` and `pr`.`hora_fin`)) and ((`pr`.`dias_semana` is null) or (find_in_set(lower(dayname(now())),`pr`.`dias_semana`) > 0)))  ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `combos`
--
ALTER TABLE `combos`
  ADD CONSTRAINT `fk_combos_producto_padre` FOREIGN KEY (`productos_id`) REFERENCES `productos` (`productos_id`) ON UPDATE CASCADE;

--
-- Constraints for table `combo_categoria_regla`
--
ALTER TABLE `combo_categoria_regla`
  ADD CONSTRAINT `fk_ccr_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`categoria_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccr_combo` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`combo_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `combo_detalle`
--
ALTER TABLE `combo_detalle`
  ADD CONSTRAINT `fk_detalle_combo` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`combo_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto_hijo` FOREIGN KEY (`productos_id`) REFERENCES `productos` (`productos_id`) ON UPDATE CASCADE;

--
-- Constraints for table `facturas_recurrentes_detalle`
--
ALTER TABLE `facturas_recurrentes_detalle`
  ADD CONSTRAINT `facturas_recurrentes_detalle_ibfk_1` FOREIGN KEY (`rec_id`) REFERENCES `facturas_recurrentes` (`rec_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recurrente_detalle` FOREIGN KEY (`rec_id`) REFERENCES `facturas_recurrentes` (`rec_id`) ON DELETE CASCADE;

--
-- Constraints for table `facturas_recurrentes_ejecuciones`
--
ALTER TABLE `facturas_recurrentes_ejecuciones`
  ADD CONSTRAINT `fk_recurrente_ejecucion` FOREIGN KEY (`rec_id`) REFERENCES `facturas_recurrentes` (`rec_id`) ON DELETE CASCADE;

--
-- Constraints for table `factura_comanda`
--
ALTER TABLE `factura_comanda`
  ADD CONSTRAINT `factura_comanda_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`facturas_id`),
  ADD CONSTRAINT `factura_comanda_ibfk_2` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`mesa_id`);

--
-- Constraints for table `historial_puntos`
--
ALTER TABLE `historial_puntos`
  ADD CONSTRAINT `historial_puntos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`clientes_id`),
  ADD CONSTRAINT `historial_puntos_ibfk_2` FOREIGN KEY (`programa_puntos_id`) REFERENCES `programa_puntos` (`id`);

--
-- Constraints for table `notas_credito_detalle`
--
ALTER TABLE `notas_credito_detalle`
  ADD CONSTRAINT `fk_ncd_nota` FOREIGN KEY (`nota_credito_id`) REFERENCES `notas_credito` (`nota_credito_id`) ON UPDATE CASCADE;

--
-- Constraints for table `promo_categorias`
--
ALTER TABLE `promo_categorias`
  ADD CONSTRAINT `fk_pc_promo` FOREIGN KEY (`promo_id`) REFERENCES `promociones` (`promo_id`) ON DELETE CASCADE;

--
-- Constraints for table `promo_productos`
--
ALTER TABLE `promo_productos`
  ADD CONSTRAINT `fk_pp_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`productos_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pp_promo` FOREIGN KEY (`promo_id`) REFERENCES `promociones` (`promo_id`) ON DELETE CASCADE;

--
-- Constraints for table `puntos_cliente`
--
ALTER TABLE `puntos_cliente`
  ADD CONSTRAINT `puntos_cliente_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`clientes_id`),
  ADD CONSTRAINT `puntos_cliente_ibfk_2` FOREIGN KEY (`programa_puntos_id`) REFERENCES `programa_puntos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
