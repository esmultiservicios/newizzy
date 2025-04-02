<?php
$peticionAjax = true;

require_once 'configAPP.php';
require_once 'configGenerales.php';
require_once 'mainModel.php';
require_once 'Database.php';
require_once 'sendEmail.php';
require_once 'cPanelAPI.php';

if (!isset($_SESSION['user_sd'])) {
  session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

$database = new Database();
$sendEmail = new sendEmail();

// BASE DE DATOS SELECCIONADA
$databaseCliente = $_POST['db'];

// DATOS QUE RECIBIMOS DEL CLIENTE NUEVO
$clientes_id = $_POST['clientes_id'];
$validar = $_POST['validar'];
$sistema_id = $_POST['sistema_id'];
$planes_id = $_POST['planes_id'];
$estado = $_POST['estado'];

$empresa_id = 1;
$razon_social = $_POST['razon_social'];
$empresa = $_POST['empresa'];
$otra_informacion = $_POST['otra_informacion'];
$eslogan = $_POST['eslogan'];
$celular = $_POST['celular'];
$telefono = $_POST['telefono'];
$correo = $_POST['correo'];
$clientes_correo = $_POST['correo'];
$logotipo = '';
$rtn = $_POST['rtn'];
$ubicacion = $_POST['ubicacion'];
$facebook = '';
$sitioweb = '';
$horario = '';
$estado = $_POST['estado'];
$fecha_registro = date('y-m-d H:i:s');
$fecha_ingreso = date('y-m-d H:i:s');
$fecha_egreso = '';
$pass = generar_password_complejoScript();
$contraseña_generada = encryptionScript($pass);
$contraseña_generadaAdmin = encryptionScript('C@M1Cl1n1c@r3');
$privilegio_id = 2;  // ADMINISTRADOR
$tipo_user_id = 2;  // ADMINISTRADOR
$username = '';
$usuarios_extras_plan = 0;

// CONSULTAMOS EL NOMBRE DEL SISTEMA QUE SELECCIONO EL CLIENTE
$tablaSistema = 'sistema';
$camposSistema = ['nombre'];
$condicionesSistema = ['sistema_id' => $sistema_id];
$orderBy = '';
$tablaJoin = '';
$condicionesJoin = [];
$resultadoSistema = $database->consultarTabla($tablaSistema, $camposSistema, $condicionesSistema, $orderBy, $tablaJoin, $condicionesJoin);

$nombre_sistema = '';

if (!empty($resultadoSistema)) {
  $nombre_sistema = $resultadoSistema[0]['nombre'];
}

// CONSULTAMOS LOS USUARIOS DEL PLAN Y EL NOMBRE DEL PLAN QUE SELECCIONO EL CLIENTE
$tablaPlanes = 'planes';
$camposPlanes = ['nombre', 'usuarios'];
$condicionesPlanes = ['planes_id' => $planes_id];
$orderBy = '';
$tablaJoin = '';
$condicionesJoin = [];
$resultadoPlanes = $database->consultarTabla($tablaPlanes, $camposPlanes, $condicionesPlanes, $orderBy, $tablaJoin, $condicionesJoin);

$nombre_plan = '';
$usuarios_plan = '';

if (!empty($resultadoPlanes)) {
  $nombre_plan = $resultadoPlanes[0]['nombre'];
  $usuarios_plan = $resultadoPlanes[0]['usuarios'];
}

// CONSULTAMOS SI EL PLAN Y EL SISTEMA DEL CLIENTE NO ESTAN REGISTRADOS
// CONSULTAMOS LOS USUARIOS DEL PLAN Y EL NOMBRE DEL PLAN QUE SELECCIONO EL CLIENTE
$tablaServercustomers = 'server_customers';
$camposServercustomers = ['server_customers_id'];
$condicionesServercustomers = ['planes_id' => $planes_id, 'sistema_id' => $sistema_id, 'clientes_id' => $clientes_id];
$orderBy = '';
$tablaJoin = '';
$condicionesJoin = [];
$resultadoServercustomers = $database->consultarTabla($tablaServercustomers, $camposServercustomers, $condicionesServercustomers, $orderBy, $tablaJoin, $condicionesJoin);

// VALIDAMOS SI NO EXISTE EL CORREO DEL USUARIO
$tablaUsers = 'users';
$camposUsers = ['users_id'];
$condicionesUsers = ['email' => $correo];
$orderBy = '';
$tablaJoin = '';
$condicionesJoin = [];
$resultadoUsers = $database->consultarTabla($tablaUsers, $camposUsers, $condicionesUsers, $orderBy, $tablaJoin, $condicionesJoin);

if (empty($resultadoUsers)) {  // CORREO NO EXISTE SE PROCEDE CON EL SIGUIENTE PASO
  if (empty($resultadoServercustomers)) {  // BASE DE DATOS DEL CLIENTE NO EXISTE. SE PROCEDE CON EL REGISTRO

    // Crear una conexión a MySQL
    $conn = new mysqli(SERVER, USER, PASS);

    // Verificar la conexión
    if ($conn->connect_error) {
      echo 'Error de conexión: Lo sentimos existe un error de conexión al servidor, ' . $conn->connect_error;
    }

    // Crear la base de datos
    $cpanel = new cPanelAPI(tokencPanel, usernamecPanel, passwordcPanel);
    $instruction = 'create_database?name=' . $databaseCliente;  // Instrucción dinámica
    $result = $cpanel->execute($instruction);

    // Seleccionar la base de datos
    if (!$conn->select_db($databaseCliente)) {
      echo 'Error al seleccionar la base de datos: Lo sentimos existe un error al intentar seleccionar la base de datos, ' . $conn->error;
    }

    // Define el contenido del archivo SQL
    $sql = "

    DROP TABLE IF EXISTS `acceso_menu`;
    CREATE TABLE IF NOT EXISTS `acceso_menu` (
      `acceso_menu_id` int NOT NULL,
      `menu_id` int NOT NULL,
      `privilegio_id` int NOT NULL,
      `estado` int NOT NULL COMMENT '1. Mostrar 2. Ocultar',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`acceso_menu_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `acceso_submenu`;
    CREATE TABLE IF NOT EXISTS `acceso_submenu` (
      `acceso_submenu_id` int NOT NULL,
      `submenu_id` int NOT NULL,
      `privilegio_id` int NOT NULL,
      `estado` int NOT NULL COMMENT '1. Mostrar 2. Ocultar',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`acceso_submenu_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `acceso_submenu1`;
    CREATE TABLE IF NOT EXISTS `acceso_submenu1` (
      `acceso_submenu1_id` int NOT NULL,
      `submenu1_id` int NOT NULL,
      `privilegio_id` int NOT NULL,
      `estado` int NOT NULL COMMENT '1. Mostrar 2. Ocultar',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`acceso_submenu1_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    DROP TABLE IF EXISTS `almacen`;
    CREATE TABLE IF NOT EXISTS `almacen` (
      `almacen_id` int NOT NULL,
      `ubicacion_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `facturar_cero` int NOT NULL,
      PRIMARY KEY (`almacen_id`),
      KEY `ubicacion_id` (`ubicacion_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `almacen` (`almacen_id`, `ubicacion_id`, `nombre`, `estado`, `empresa_id`, `fecha_registro`, `facturar_cero`) VALUES
    (1, 1, 'Almacén Principal', 1, 1, NOW(), 1);
  
  CREATE TABLE `apertura` (
    `apertura_id` int(11) NOT NULL,
    `colaboradores_id` int(11) NOT NULL,
    `fecha` date NOT NULL,
    `factura_inicial` char(20) NOT NULL,
    `factura_final` char(20) NOT NULL,
    `apertura` float(12,2) NOT NULL COMMENT 'Monto de Apertura',
    `neto` float(12,2) NOT NULL,
    `estado` int(11) NOT NULL COMMENT '1. Activo 2. Inactivo',
    `fecha_registro` datetime NOT NULL,
    `empresa_id` int(11) NOT NULL
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;  
   
    DROP TABLE IF EXISTS `asistencia`;
    CREATE TABLE IF NOT EXISTS `asistencia` (
      `asistencia_id` int NOT NULL,
      `colaboradores_id` int NOT NULL,
      `fecha` date NOT NULL,
      `horai` time NOT NULL,
      `horaf` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `comentario` char(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL COMMENT '0. Sin pagar 1. Pagado',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`asistencia_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `banco`;
    CREATE TABLE IF NOT EXISTS `banco` (
      `banco_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`banco_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `banco` (`banco_id`, `nombre`, `estado`, `fecha_registro`) VALUES
    (0, 'Sin Banco', 1,NOW()),
    (1, 'Ficohsa', 1, NOW()),
    (2, 'BAC', 1,  NOW()),
    (3, 'Occidente', 1,  NOW()),
    (4, 'Lafise', 1, NOW()),
    (5, 'Promerica', 1, NOW()),
    (6, 'Bancafe', 1, NOW()),
    (7, 'Banpais', 1, NOW()),
    (8, 'Banrural', 1, NOW()),
    (9, 'Banco Popular', 1, NOW()),
    (10, 'Cooperativa Elga', 1, NOW());
  
    DROP TABLE IF EXISTS `bitacora`;
    CREATE TABLE IF NOT EXISTS `bitacora` (
      `bitacora_id` int NOT NULL,
      `bitacoraCodigo` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `bitacoraFecha` date NOT NULL,
      `bitacoraHoraInicio` time NOT NULL,
      `bitacoraHoraFinal` char(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `bitacoraTipo` int NOT NULL,
      `bitacoraYear` char(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`bitacora_id`),
      KEY `colaborador_id` (`colaboradores_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `categoria`;
    CREATE TABLE IF NOT EXISTS `categoria` (
      `categoria_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`categoria_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `categoria` (`categoria_id`, `nombre`, `estado`, `fecha_registro`) VALUES
    (1, 'Prueba', 1, NOW());  

    DROP TABLE IF EXISTS `categoria_gastos`;
    CREATE TABLE IF NOT EXISTS `categoria_gastos` (
      `categoria_gastos_id` int(11) NOT NULL,
      `nombre` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int(1) NOT NULL COMMENT '0 Inactivo 1. Activo',
      `usuario` int(11) NOT NULL,
      `date_write` datetime NOT NULL,
      PRIMARY KEY (`categoria_gastos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    COMMIT;

    DROP TABLE IF EXISTS `cheque`;
    CREATE TABLE IF NOT EXISTS `cheque` (
      `cheque_id` int NOT NULL,
      `cuentas_id` int NOT NULL,
      `proveedores_id` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha` date NOT NULL,
      `factura` char(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `importe` float(12,2) NOT NULL,
      `observacion` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`cheque_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `clientes`;
    CREATE TABLE IF NOT EXISTS `clientes` (
      `clientes_id` int NOT NULL,
      `nombre` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `rtn` char(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha` date NOT NULL,
      `departamentos_id` int NOT NULL,
      `municipios_id` int NOT NULL,
      `localidad` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `telefono` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `correo` char(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `empresa` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
      `eslogan` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
      `otra_informacion` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
      `whatsapp` char(8) COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`clientes_id`),
      KEY `departamentos_id` (`departamentos_id`),
      KEY `municipios_id` (`municipios_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `clientes` (`clientes_id`, `nombre`, `rtn`, `fecha`, `departamentos_id`, `municipios_id`, `localidad`, `telefono`, `correo`, `estado`, `colaboradores_id`, `fecha_registro`, `empresa`, `eslogan`, `otra_informacion`, `whatsapp`) VALUES
    (1, 'Consumidor Final', '999999999', CAST(NOW() AS DATE), 18, 295, '.', '0', 'alguien@algo.com', 1, 1, NOW(), '$empresa', '$eslogan', '$otra_informacion', '$celular');

    DROP TABLE IF EXISTS `cobrar_clientes`;
    CREATE TABLE IF NOT EXISTS `cobrar_clientes` (
      `cobrar_clientes_id` int NOT NULL,
      `clientes_id` int NOT NULL,
      `facturas_id` int NOT NULL,
      `fecha` date NOT NULL,
      `saldo` float(12,4) NOT NULL,
      `estado` int NOT NULL COMMENT '1. Pendiente de Cobrar 2. Pago Realizado',
      `usuario` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`cobrar_clientes_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    DROP TABLE IF EXISTS `colaboradores`;
    CREATE TABLE IF NOT EXISTS `colaboradores` (
      `colaboradores_id` int NOT NULL,
      `puestos_id` int NOT NULL,
      `nombre` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `apellido` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `identidad` char(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
      `telefono` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `fecha_ingreso` date NOT NULL,
      `fecha_egreso` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`colaboradores_id`),
      KEY `FK_puestos_id` (`puestos_id`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `colaboradores` (`colaboradores_id`, `puestos_id`, `nombre`, `apellido`, `identidad`, `estado`, `telefono`, `empresa_id`, `fecha_registro`, `fecha_ingreso`, `fecha_egreso`) VALUES 
    ('1', '1', 'CLINICARE', 'S DE RL', '05019021318813', '1', '32273380', '$empresa_id', '$fecha_registro', '$fecha_ingreso', '$fecha_egreso'),
    ('2', '1', '$razon_social', '', '$rtn', '$estado', '$telefono', '$empresa_id', '$fecha_registro', '$fecha_ingreso', '$fecha_egreso');    
  
    DROP TABLE IF EXISTS `compras`;
    CREATE TABLE IF NOT EXISTS `compras` (
      `compras_id` int(11) NOT NULL,
      `proveedores_id` int(11) NOT NULL,
      `number` char(30) COLLATE utf8mb4_spanish_ci NOT NULL,
      `tipo_compra` int(11) NOT NULL COMMENT '1. Contado 2. Crédito',
      `colaboradores_id` int(11) NOT NULL,
      `importe` float(12,2) NOT NULL,
      `notas` char(255) COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha` date NOT NULL,
      `estado` int(11) NOT NULL COMMENT '1. Borrador 2. Pagada 3. Crédito 4. Cancelada',
      `usuario` int(11) NOT NULL,
      `empresa_id` int(11) NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `cuentas_id` int(11) NOT NULL,
      `recordatorio` int(11) NOT NULL,
      PRIMARY KEY (`compras_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `compras_detalles`;
    CREATE TABLE IF NOT EXISTS `compras_detalles` (
      `compras_detalles_id` int NOT NULL,
      `compras_id` int NOT NULL,
      `productos_id` int NOT NULL,
      `cantidad` int NOT NULL,
      `precio` float(12,2) NOT NULL,
      `isv_valor` float(12,2) NOT NULL,
      `descuento` float(12,2) NOT NULL,
      `medida` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`compras_detalles_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;    

    DROP TABLE IF EXISTS `config`;
    CREATE TABLE IF NOT EXISTS `config` (
      `config_id` int(11) NOT NULL,
      `accion` char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
      `activar` int(11) NOT NULL COMMENT '1. Si 0.No',
      PRIMARY KEY (`config_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    INSERT INTO `config` (`config_id`, `accion`, `activar`) VALUES
    (1, 'Mostrar detalle facturas - Caja', 0),
    (2, 'Validar Apertura Caja', 1);

    DROP TABLE IF EXISTS `contrato`;
    CREATE TABLE IF NOT EXISTS `contrato` (
      `contrato_id` int(11) NOT NULL,
      `colaborador_id` int(11) NOT NULL,
      `tipo_contrato_id` int(11) NOT NULL,
      `pago_planificado_id` int(11) NOT NULL,
      `tipo_empleado_id` int(11) NOT NULL,
      `salario_mensual` decimal(12,2) NOT NULL,
      `salario` decimal(12,2) NOT NULL,
      `fecha_inicio` date NOT NULL,
      `fecha_fin` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL,
      `notas` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
      `usuario` int(11) NOT NULL,
      `estado` int(11) NOT NULL COMMENT '1. Activo 2.Inactivo',
      `fecha_registro` datetime NOT NULL,
      `semanal` int(11) NOT NULL COMMENT '0. No 1. Sí',
      PRIMARY KEY (`contrato_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `correo`;
    CREATE TABLE IF NOT EXISTS `correo` (
      `correo_id` int NOT NULL,
      `correo_tipo_id` int NOT NULL,
      `server` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Servidor de Correo SMTP',
      `correo` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Correo de la cuenta',
      `password` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Contraseña de la Cuenta',
      `port` int NOT NULL,
      `smtp_secure` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'tls o ssl',
      `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`correo_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `correo` (`correo_id`, `correo_tipo_id`, `server`, `correo`, `password`, `port`, `smtp_secure`, `estado`, `fecha_registro`) VALUES
    (1, 1, 'smtp.office365.com', 'clinicare@clinicarehn.com', 'MDJ2dkIvREJhM1BZQkIyLy8yVmMwQT09', 587, 'tls', 1, NOW()),
    (2, 2, 'smtp.office365.com', 'clinicare@clinicarehn.com', 'MDJ2dkIvREJhM1BZQkIyLy8yVmMwQT09', 587, 'tls', 1, NOW()),
    (3, 3, 'smtp.office365.com', 'clinicare@clinicarehn.com', 'MDJ2dkIvREJhM1BZQkIyLy8yVmMwQT09', 587, 'tls', 1, NOW()),
    (4, 4, 'smtp.office365.com', 'clinicare@clinicarehn.com', 'MDJ2dkIvREJhM1BZQkIyLy8yVmMwQT09', 587, 'tls', 1, NOW());
  
    DROP TABLE IF EXISTS `correo_tipo`;
    CREATE TABLE IF NOT EXISTS `correo_tipo` (
      `correo_tipo_id` int NOT NULL,
      `nombre` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`correo_tipo_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `correo_tipo` (`correo_tipo_id`, `nombre`) VALUES
    (1, 'Notificaciones'),
    (2, 'Soporte'),
    (3, 'Facturas'),
    (4, 'No Reply');
  
    DROP TABLE IF EXISTS `cotizacion`;
    CREATE TABLE IF NOT EXISTS `cotizacion` (
      `cotizacion_id` int NOT NULL,
      `clientes_id` int NOT NULL,
      `number` int NOT NULL,
      `tipo_factura` int NOT NULL COMMENT '1. Contado 2. Crédito',
      `colaboradores_id` int NOT NULL,
      `importe` float(12,2) NOT NULL,
      `notas` char(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha` date NOT NULL,
      `estado` int NOT NULL COMMENT '1. Activa 2. Cancelada',
      `vigencia_cotizacion_id` int NOT NULL,
      `usuario` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `fecha_dolar` date NOT NULL,
      PRIMARY KEY (`cotizacion_id`),
      KEY `clientes_id` (`clientes_id`),
      KEY `colaborador_id` (`colaboradores_id`),
      KEY `usuario` (`usuario`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `cotizacion_detalles`;
    CREATE TABLE IF NOT EXISTS `cotizacion_detalles` (
      `cotizacion_detalle_id` int NOT NULL,
      `cotizacion_id` int NOT NULL,
      `productos_id` int NOT NULL,
      `cantidad` int NOT NULL,
      `precio` float(12,2) NOT NULL,
      `isv_valor` float(12,2) NOT NULL,
      `descuento` float(12,2) NOT NULL,
      PRIMARY KEY (`cotizacion_detalle_id`),
      KEY `productos_id` (`productos_id`),
      KEY `facturas_id` (`cotizacion_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    DROP TABLE IF EXISTS `cuentas`;
    CREATE TABLE IF NOT EXISTS `cuentas` (
      `cuentas_id` int NOT NULL,
      `codigo` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`cuentas_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `cuentas` (`cuentas_id`, `codigo`, `nombre`, `estado`, `fecha_registro`) VALUES
    (1, '1010', 'Apertura', 1, NOW()),
    (2, '1011', 'Caja', 1, NOW()),
    (3, '1012', 'Banco Bac', 1, NOW()),
    (4, '1013', 'CxC Clientes', 1, NOW()),
    (5, '1014', 'CxP Proveedores', 1, NOW()),
    (6, '1015', 'CxP Socios', 1, NOW());

    DROP TABLE IF EXISTS `departamentos`;
    CREATE TABLE IF NOT EXISTS `departamentos` (
      `departamentos_id` int NOT NULL,
      `nombre` char(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`departamentos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
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
  
    DROP TABLE IF EXISTS `diarios`;
    CREATE TABLE IF NOT EXISTS `diarios` (
      `diarios_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `cuentas_id` int NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`diarios_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `diarios` (`diarios_id`, `nombre`, `cuentas_id`, `estado`, `fecha_registro`) VALUES
    (1, 'Clientes', 4, 1, NOW()),
    (2, 'Proveedores', 5, 1, NOW()),
    (3, 'Planilla', 3, 1, NOW());
  
    DROP TABLE IF EXISTS `documento`;
    CREATE TABLE IF NOT EXISTS `documento` (
      `documento_id` int(11) NOT NULL,
      `nombre` char(35) COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int(11) NOT NULL,
      PRIMARY KEY (`documento_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `documento` (`documento_id`, `nombre`, `estado`) VALUES
    (1, 'Factura Electronica', 1),
    (2, 'Nota de Credito', 0),
    (3, 'Nota de Debito', 0),
    (4, 'Factura Proforma', 1);
  
    DROP TABLE IF EXISTS `egresos`;
    CREATE TABLE IF NOT EXISTS `egresos` (
      `egresos_id` int(11) NOT NULL,
      `cuentas_id` int(11) NOT NULL,
      `proveedores_id` int(11) NOT NULL,
      `empresa_id` int(11) NOT NULL,
      `tipo_egreso` int(11) NOT NULL COMMENT '1. Compras 2. Gastos\t',
      `fecha` date NOT NULL,
      `factura` char(20) COLLATE utf8mb4_spanish_ci NOT NULL,
      `subtotal` float(12,2) NOT NULL,
      `descuento` float(12,2) NOT NULL,
      `nc` float(12,2) NOT NULL COMMENT 'Nota de Credito',
      `impuesto` float(12,2) NOT NULL,
      `total` float(12,2) NOT NULL,
      `observacion` char(150) COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int(11) NOT NULL COMMENT '1. Activo 2. Inactivo',
      `colaboradores_id` int(11) NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `categoria_gastos_id` int(11) NOT NULL,
      PRIMARY KEY (`egresos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `empresa`;
    CREATE TABLE `empresa` (
      `empresa_id` int NOT NULL,
      `razon_social` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
      `nombre` char(50) COLLATE utf8mb4_spanish_ci NOT NULL,
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
  
    INSERT INTO `empresa`(`empresa_id`, `razon_social`, `nombre`, `otra_informacion`, `eslogan`, `celular`, `telefono`, `correo`, `logotipo`, `rtn`, `ubicacion`, `facebook`, `sitioweb`, `horario`, `estado`, `colaboradores_id`, `fecha_registro`) VALUES ('$empresa_id','$razon_social','$empresa','$otra_informacion','$eslogan','$celular','$telefono','$correo','$logotipo','$rtn','$ubicacion','$facebook','$sitioweb','$horario','$estado','$colaboradores_id','$fecha_registro','','0');
  
    DROP TABLE IF EXISTS `facturas`;
    CREATE TABLE `facturas` (
      `facturas_id` int NOT NULL,
      `clientes_id` int NOT NULL,
      `secuencia_facturacion_id` int NOT NULL,
      `apertura_id` int NOT NULL,
      `number` int NOT NULL,
      `tipo_factura` int NOT NULL COMMENT '1. Contado 2. Crédito',
      `colaboradores_id` int NOT NULL,
      `importe` float(12,2) NOT NULL,
      `notas` char(255) COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha` date NOT NULL,
      `estado` int NOT NULL COMMENT '1. Borrador 2. Pagada 3. Crédito 4. Cancelada',
      `usuario` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `fecha_dolar` date NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    ALTER TABLE `facturas`
      ADD PRIMARY KEY (`facturas_id`),
      ADD KEY `clientes_id` (`clientes_id`),
      ADD KEY `colaborador_id` (`colaboradores_id`),
      ADD KEY `secuencia_facturacion_id` (`secuencia_facturacion_id`),
      ADD KEY `usuario` (`usuario`);
    COMMIT;    
  
    DROP TABLE IF EXISTS `facturas_detalles`;
    CREATE TABLE IF NOT EXISTS `facturas_detalles` (
      `facturas_detalle_id` int NOT NULL,
      `facturas_id` int NOT NULL,
      `productos_id` int NOT NULL,
      `cantidad` int NOT NULL,
      `precio` float(12,4) NOT NULL,
      `isv_valor` float(12,4) NOT NULL,
      `descuento` float(12,4) NOT NULL,
      `medida` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`facturas_detalle_id`),
      KEY `productos_id` (`productos_id`),
      KEY `facturas_id` (`facturas_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    DROP TABLE IF EXISTS `facturas_proforma`;
    CREATE TABLE IF NOT EXISTS `facturas_proforma` (
      `facturas_proforma_id` int(11) NOT NULL,
      `facturas_id` int(11) NOT NULL,
      `clientes_id` int(11) NOT NULL,
      `secuencia_facturacion_id` int(11) NOT NULL,
      `numero` int(11) NOT NULL,
      `importe` float(12,2) NOT NULL,
      `usuario` int(11) NOT NULL,
      `empresa_id` int(11) NOT NULL,
      `estado` int(11) NOT NULL COMMENT '0. Pendiente 1. Pagada',
      `fecha_creacion` datetime NOT NULL,
      PRIMARY KEY (`facturas_proforma_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    DROP TABLE IF EXISTS `historial`;
    CREATE TABLE IF NOT EXISTS `historial` (
      `historial_id` int NOT NULL,
      `modulo` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `colaboradores_id` int NOT NULL,
      `status` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `observacion` char(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`historial_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `historial_acceso`;
    CREATE TABLE IF NOT EXISTS `historial_acceso` (
      `historial_acceso_id` int NOT NULL,
      `fecha` datetime NOT NULL,
      `colaboradores_id` int NOT NULL,
      `ip` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `acceso` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`historial_acceso_id`),
      KEY `FK_colaborador_id` (`colaboradores_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `impresora`;
    CREATE TABLE IF NOT EXISTS `impresora` (
      `impresora_id` int NOT NULL,
      `descripcion` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `tipo` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`impresora_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `impresora` (`impresora_id`, `descripcion`, `estado`, `tipo`, `fecha_registro`) VALUES
    (0, 'Comprobante Carta', 0, 3, NOW()),
    (1, 'Factura Carta', 1, 1, NOW()),
    (2, 'Factura Ticket', 0, 2, NOW()),
    (3, 'Comprobante Ticket', 1, 4, NOW()),
    (4, 'Factura Media Carta', 0, 5, NOW());

    DROP TABLE IF EXISTS `ingresos`;
    CREATE TABLE IF NOT EXISTS `ingresos` (
      `ingresos_id` int NOT NULL,
      `cuentas_id` int NOT NULL,
      `clientes_id` int NOT NULL,
      `empresa_id` int NOT NULL,
      `tipo_ingreso` int NOT NULL COMMENT '1. Ingresos Ventas 2. Otros Ingresos',
      `fecha` date NOT NULL,
      `factura` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `subtotal` float(12,2) NOT NULL,
      `descuento` float(12,2) NOT NULL,
      `nc` float(12,2) NOT NULL COMMENT 'Nota de Credito',
      `impuesto` float(12,2) NOT NULL,
      `total` float(12,2) NOT NULL,
      `observacion` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`ingresos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    DROP TABLE IF EXISTS `isv`;
    CREATE TABLE IF NOT EXISTS `isv` (
      `isv_id` int(11) NOT NULL,
      `isv_tipo_id` int(11) NOT NULL,
      `valor` float(12,2) NOT NULL,
      `activar` int(11) NOT NULL COMMENT '0. No 1. Si',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`isv_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    INSERT INTO `isv` (`isv_id`, `isv_tipo_id`, `valor`, `activar`, `fecha_registro`) VALUES
    (1, 1, 15.00, 1, NOW()),
    (2, 2, 15.00, 1, NOW());
  
    DROP TABLE IF EXISTS `isv_tipo`;
    CREATE TABLE IF NOT EXISTS `isv_tipo` (
      `isv_tipo_id` int NOT NULL,
      `nombre` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`isv_tipo_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `isv_tipo` (`isv_tipo_id`, `nombre`) VALUES
    (1, 'Facturas'),
    (2, 'Compras');

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
    
    ALTER TABLE `lotes`
      ADD PRIMARY KEY (`lote_id`),
      ADD UNIQUE KEY `numero_lote` (`numero_lote`),
      ADD KEY `productos_id` (`productos_id`),
      ADD KEY `almacen_id` (`almacen_id`),
      ADD KEY `fk_empresa_lotes` (`empresa_id`);
    --
    ALTER TABLE `lotes`
      MODIFY `lote_id` int NOT NULL AUTO_INCREMENT;
    COMMIT;  

    DROP TABLE IF EXISTS `medida`;
    CREATE TABLE IF NOT EXISTS `medida` (
      `medida_id` int NOT NULL,
      `nombre` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `descripcion` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`medida_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `medida` (`medida_id`, `nombre`, `descripcion`, `estado`, `fecha_registro`) VALUES
    (1, 'Und', '', 1, NOW()),
    (2, 'Lbs', '', 1, NOW()),
    (3, 'Hora', 'Hora(s)', 1, NOW());
  
    DROP TABLE IF EXISTS `menu`;
    CREATE TABLE IF NOT EXISTS `menu` (
      `menu_id` int NOT NULL,
      `name` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`menu_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `menu` (`menu_id`, `name`) VALUES
    (1, 'dashboard'),
    (2, 'ventas'),
    (3, 'compras'),
    (4, 'almacen'),
    (6, 'reportes'),
    (7, 'configuracion'),
    (5, 'contabilidad'),
    (8, 'recursosHumanos');  

    DROP TABLE IF EXISTS `menu_plan`;
    CREATE TABLE `menu_plan` (
      `menu_plan_id` int NOT NULL,
      `menu_id` int NOT NULL,
      `planes_id` int NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    
    INSERT INTO `menu_plan` (`menu_plan_id`, `menu_id`, `planes_id`) VALUES
    (1, 7, 1),
    (2, 2, 1),
    (4, 6, 1),
    (6, 8, 1),
    (7, 1, 2),
    (8, 2, 2),
    (9, 3, 2),
    (10, 4, 2),
    (11, 6, 2),
    (12, 7, 2),
    (13, 8, 2),
    (14, 1, 3),
    (15, 2, 3),
    (16, 3, 3),
    (17, 4, 3),
    (18, 5, 3),
    (19, 6, 3),
    (20, 7, 3),
    (21, 8, 3),
    (22, 1, 4),
    (23, 2, 4),
    (24, 3, 4),
    (25, 4, 4),
    (26, 5, 4),
    (27, 6, 4),
    (28, 7, 4),
    (29, 8, 4),
    (30, 1, 5),
    (31, 2, 5),
    (32, 3, 5),
    (33, 4, 5),
    (34, 5, 5),
    (35, 6, 5),
    (36, 7, 5),
    (37, 8, 5); 

    DROP TABLE IF EXISTS `movimientos`;
    CREATE TABLE IF NOT EXISTS `movimientos` (
      `movimientos_id` int NOT NULL,
      `productos_id` int NOT NULL,
      `documento` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `cantidad_entrada` int NOT NULL,
      `cantidad_salida` int NOT NULL,
      `saldo` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `clientes_id` int NOT NULL,
      `comentario` char(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `almacen_id` int NOT NULL,
      `lote_id` int NOT NULL
      PRIMARY KEY (`movimientos_id`),
      KEY `productos_id` (`productos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `movimientos_cuentas`;
    CREATE TABLE IF NOT EXISTS `movimientos_cuentas` (
      `movimientos_cuentas_id` int NOT NULL,
      `cuentas_id` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha` date NOT NULL,
      `ingreso` float(12,2) NOT NULL,
      `egreso` float(12,2) NOT NULL,
      `saldo` float(12,2) NOT NULL,
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`movimientos_cuentas_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `municipios`;
    CREATE TABLE IF NOT EXISTS `municipios` (
      `municipios_id` int NOT NULL,
      `departamentos_id` int NOT NULL,
      `nombre` char(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`municipios_id`),
      KEY `departamentos_id` (`departamentos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
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
  
    DROP TABLE IF EXISTS `nomina`;
    CREATE TABLE `nomina` (
      `nomina_id` int NOT NULL,
      `empresa_id` int NOT NULL,
      `pago_planificado_id` int NOT NULL,
      `tipo_nomina_id` int NOT NULL,
      `fecha_inicio` date NOT NULL,
      `fecha_fin` date NOT NULL,
      `detalle` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `importe` decimal(12,2) NOT NULL,
      `notas` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `usuario` int NOT NULL,
      `estado` int NOT NULL COMMENT '0. No Generada 1. Generada',
      `fecha_registro` datetime NOT NULL,
      `cuentas_id` int NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `nomina_detalles`;
    CREATE TABLE IF NOT EXISTS `nomina_detalles` (
      `nomina_detalles_id` int(11) NOT NULL,
      `nomina_id` int(11) NOT NULL,
      `colaboradores_id` int(11) NOT NULL,
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
      `usuario` int(11) NOT NULL,
      `estado` int(11) NOT NULL,
      `notas` varchar(254) COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `hrse25_valor` decimal(12,2) NOT NULL,
      `hrse50_valor` decimal(12,2) NOT NULL,
      `hrse75_valor` decimal(12,2) NOT NULL,
      `hrse100_valor` decimal(12,2) NOT NULL,
      `salario` decimal(12,2) NOT NULL,
      PRIMARY KEY (`nomina_detalles_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    CREATE TABLE `notificaciones` (
      `notificaciones_id` int NOT NULL,
      `correo` char(100) COLLATE utf8mb4_spanish_ci NOT NULL,
      `nombre` char(100) COLLATE utf8mb4_spanish_ci NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    ALTER TABLE `notificaciones`
      ADD PRIMARY KEY (`notificaciones_id`);
    COMMIT;

    DROP TABLE IF EXISTS `pagar_proveedores`;
    CREATE TABLE IF NOT EXISTS `pagar_proveedores` (
      `pagar_proveedores_id` int NOT NULL,
      `proveedores_id` int NOT NULL,
      `compras_id` int NOT NULL,
      `fecha` date NOT NULL,
      `saldo` float(12,2) NOT NULL,
      `estado` int NOT NULL COMMENT '1. Pendiente de Cobrar 2. Pago Realizado',
      `usuario` int NOT NULL,
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`pagar_proveedores_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `pagos`;
    CREATE TABLE IF NOT EXISTS `pagos` (
      `pagos_id` int NOT NULL,
      `facturas_id` int NOT NULL,
      `tipo_pago` int NOT NULL COMMENT '1. Contado 2. Crédito',
      `fecha` date NOT NULL,
      `importe` float(12,4) NOT NULL,
      `efectivo` float(12,4) NOT NULL,
      `cambio` float(12,4) NOT NULL,
      `tarjeta` float(12,4) NOT NULL,
      `usuario` int NOT NULL,
      `estado` int NOT NULL COMMENT '1. Pagado, 2. Cancelado',
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`pagos_id`),
      KEY `facturas_id` (`facturas_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `pagoscompras`;
    CREATE TABLE IF NOT EXISTS `pagoscompras` (
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
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`pagoscompras_id`),
      KEY `facturas_id` (`compras_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `pagoscompras_detalles`;
    CREATE TABLE IF NOT EXISTS `pagoscompras_detalles` (
      `pagoscompras_detalles_id` int NOT NULL,
      `pagoscompras_id` int NOT NULL,
      `tipo_pago_id` int NOT NULL,
      `banco_id` int NOT NULL,
      `efectivo` float(12,2) NOT NULL,
      `descripcion1` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `descripcion2` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `descripcion3` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`pagoscompras_detalles_id`),
      KEY `pagos_id` (`pagoscompras_id`),
      KEY `tipo_pago_id` (`tipo_pago_id`),
      KEY `banco_id` (`banco_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `pagos_detalles`;
    CREATE TABLE IF NOT EXISTS `pagos_detalles` (
      `pagos_detalles_id` int NOT NULL,
      `pagos_id` int NOT NULL,
      `tipo_pago_id` int NOT NULL,
      `banco_id` int NOT NULL,
      `efectivo` float(12,4) NOT NULL,
      `descripcion1` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `descripcion2` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `descripcion3` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`pagos_detalles_id`),
      KEY `pagos_id` (`pagos_id`),
      KEY `tipo_pago_id` (`tipo_pago_id`),
      KEY `banco_id` (`banco_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
  
    DROP TABLE IF EXISTS `pago_planificado`;
    CREATE TABLE IF NOT EXISTS `pago_planificado` (
      `pago_planificado_id` int NOT NULL,
      `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`pago_planificado_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `pago_planificado` (`pago_planificado_id`, `nombre`) VALUES
    (1, 'Semanal'),
    (2, 'Quincenal'),
    (3, 'Mensual');
  
    DROP TABLE IF EXISTS `permisos`;
    CREATE TABLE IF NOT EXISTS `permisos` (
      `permisos_id` int NOT NULL,
      `tipo_user_id` int NOT NULL,
      `tipo_permiso` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`permisos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `permisos` (`permisos_id`, `tipo_user_id`, `tipo_permiso`, `estado`, `fecha_registro`) VALUES
    (1, 2, 'guardar', 1, NOW()),
    (2, 2, 'editar', 1, NOW()),
    (3, 2, 'eliminar', 1, NOW()),
    (4, 2, 'consultar', 1, NOW()),
    (5, 2, 'imprimir', 1, NOW()),
    (6, 2, 'crear', 1, NOW()),
    (7, 2, 'reportes', 1, NOW()),
    (8, 2, 'actualizar', 1, NOW());

    DROP TABLE IF EXISTS `pin`;
    CREATE TABLE IF NOT EXISTS `pin` (
      `pin_id` int NOT NULL,
      `server_customers_id` int NOT NULL,
      `codigo_cliente` int NOT NULL,
      `pin` int NOT NULL,
      `fecha_hora_inicio` datetime NOT NULL,
      `fecha_hora_fin` datetime NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `plan`;
    CREATE TABLE IF NOT EXISTS `plan` (
      `plan_id` int NOT NULL,
      `planes_id` int NOT NULL,
      `users` int NOT NULL COMMENT 'Cantidad Usuarios en el Plan Si el valor esta en 0 no hay límite de usuarios',
      `user_extra` int NOT NULL COMMENT 'Cantidad de Usuarios Extras',
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`plan_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `plan`(`plan_id`, `planes_id`, `users`, `user_extra`, `fecha_registro`) VALUES 
    (1,'$planes_id','$usuarios_plan','$usuarios_extras_plan',NOW());

    DROP TABLE IF EXISTS `planes`;
    CREATE TABLE `planes` (
      `planes_id` int NOT NULL,
      `nombre` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `usuarios` int NOT NULL COMMENT 'Cantidad Usuarios en el plan',
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    
    INSERT INTO `planes` (`planes_id`, `nombre`, `usuarios`, `estado`, `fecha_registro`) VALUES
    (1, 'Emprendedor', 2, 1, NOW()),
    (2, 'Básico', 3, 1, NOW()),
    (3, 'Regular', 4, 1, NOW()),
    (4, 'Estandar', 6, 1, NOW()),
    (5, 'Premium', 10, 1, NOW());    
  
    DROP TABLE IF EXISTS `precio_factura`;
    CREATE TABLE IF NOT EXISTS `precio_factura` (
      `precio_factura_id` int NOT NULL,
      `facturas_id` int NOT NULL,
      `productos_id` int NOT NULL,
      `clientes_id` int NOT NULL,
      `fecha` date NOT NULL,
      `referencia` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `precio_anterior` float(12,2) NOT NULL,
      `precio_nuevo` float(12,2) NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`precio_factura_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `price_list`;
    CREATE TABLE IF NOT EXISTS `price_list` (
      `price_list_id` int NOT NULL,
      `compras_id` int NOT NULL,
      `productos_id` int NOT NULL,
      `prices` float(12,2) NOT NULL,
      `fecha` date NOT NULL,
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`price_list_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `privilegio`;
    CREATE TABLE IF NOT EXISTS `privilegio` (
      `privilegio_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`privilegio_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `privilegio` (`privilegio_id`, `nombre`, `estado`, `fecha_registro`) VALUES
    (1, 'Super Administrador', 1, NOW()),
    (2, 'Administrador', 1, NOW());

    DROP TABLE IF EXISTS `productos`;
    CREATE TABLE IF NOT EXISTS `productos` (
      `productos_id` int NOT NULL,
      `barCode` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `almacen_id` int NOT NULL,
      `medida_id` int NOT NULL,
      `categoria_id` int NOT NULL,
      `nombre` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `descripcion` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `tipo_producto_id` int NOT NULL,
      `precio_compra` float(12,2) NOT NULL,
      `porcentaje_venta` float(12,2) NOT NULL COMMENT 'Porcentaje de Ganacia',
      `precio_venta` float(12,2) NOT NULL,
      `cantidad_mayoreo` float(12,2) NOT NULL,
      `precio_mayoreo` float(12,2) NOT NULL,
      `cantidad_minima` int NOT NULL,
      `cantidad_maxima` int NOT NULL,
      `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo\t',
      `isv_venta` int NOT NULL COMMENT '1. Sí 2. No',
      `isv_compra` int NOT NULL COMMENT '1. Sí 2. No',
      `colaborador_id` int NOT NULL,
      `file_name` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Nombre de la Imagen',
      `empresa_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `id_producto_superior` int NOT NULL,
      PRIMARY KEY (`productos_id`),
      KEY `almacen_id` (`almacen_id`),
      KEY `medida_id` (`medida_id`),
      KEY `colaborador_id` (`colaborador_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `proveedores`;
    CREATE TABLE IF NOT EXISTS `proveedores` (
      `proveedores_id` int NOT NULL,
      `nombre` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `rtn` char(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha` date NOT NULL,
      `departamentos_id` int NOT NULL,
      `municipios_id` int NOT NULL,
      `localidad` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `telefono` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `correo` char(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`proveedores_id`),
      KEY `departamentos_id` (`departamentos_id`),
      KEY `municipios_id` (`municipios_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    DROP TABLE IF EXISTS `puestos`;
    CREATE TABLE IF NOT EXISTS `puestos` (
      `puestos_id` int NOT NULL,
      `nombre` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`puestos_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `puestos` (`puestos_id`, `nombre`, `estado`, `fecha_registro`) VALUES
    (1, 'Administrador', 1, NOW());

DROP TABLE IF EXISTS `secuencia_facturacion`;
    CREATE TABLE IF NOT EXISTS `secuencia_facturacion` (
      `secuencia_facturacion_id` int NOT NULL,
      `empresa_id` int NOT NULL,
      `cai` char(37) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `prefijo` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Número Inicial de la Factura',
      `relleno` int NOT NULL COMMENT 'Relleno de Numero',
      `incremento` int NOT NULL COMMENT 'Incremento del Numero',
      `siguiente` int NOT NULL COMMENT 'Número Siguiente',
      `rango_inicial` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Rango Autorizado Inicial',
      `rango_final` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Rango Autorizado Final',
      `fecha_activacion` date NOT NULL,
      `fecha_limite` date NOT NULL COMMENT 'Fecha Limite de Emisión',
      `comentario` char(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `activo` int NOT NULL COMMENT '1. Sí 2. No',
      `colaboradores_id` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      `documento_id` int NOT NULL,
      PRIMARY KEY (`secuencia_facturacion_id`),
      KEY `FK_empresa_id` (`empresa_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    DROP TABLE IF EXISTS `server_customers`;
    CREATE TABLE IF NOT EXISTS `server_customers` (
      `server_customers_id` int NOT NULL,
      `clientes_id` int NOT NULL,
      `codigo_cliente` int NOT NULL,
      `db` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `planes_id` int NOT NULL,
      `sistema_id` int NOT NULL,
      `validar` int NOT NULL COMMENT '1 Sí 2. No',
      `estado` int NOT NULL,
      PRIMARY KEY (`server_customers_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;  

    DROP TABLE IF EXISTS `sistema`;
    CREATE TABLE IF NOT EXISTS `sistema` (
      `sistema_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      PRIMARY KEY (`sistema_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
        
    INSERT INTO `sistema` (`sistema_id`, `nombre`, `estado`) VALUES
    (1, 'IZZY', 1),
    (2, 'CAMI', 1),
    (3, 'Monitoring', 1);  

DROP TABLE IF EXISTS `submenu`;
    CREATE TABLE IF NOT EXISTS `submenu` (
      `submenu_id` int NOT NULL,
      `menu_id` int NOT NULL,
      `name` char(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`submenu_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `submenu` (`submenu_id`, `menu_id`, `name`) VALUES
    (1, 2, 'clientes'),
    (2, 2, 'facturas'),
    (4, 3, 'proveedores'),
    (5, 3, 'facturaCompras'),
    (6, 4, 'productos'),
    (7, 4, 'inventario'),
    (13, 6, 'reporte_historial'),
    (14, 6, 'reporte_ventas'),
    (15, 6, 'reporte_compras'),
    (16, 8, 'colaboradores'),
    (17, 7, 'puestos'),
    (18, 7, 'users'),
    (19, 7, 'secuencia'),
    (20, 7, 'empresa'),
    (21, 7, 'confAlmacen'),
    (22, 7, 'confUbicacion'),
    (23, 7, 'confMedida'),
    (3, 2, 'cajas'),
    (24, 7, 'privilegio'),
    (25, 7, 'tipoUser'),
    (8, 5, 'cuentasContabilidad'),
    (9, 5, 'movimientosContabilidad'),
    (10, 5, 'ingresosContabilidad'),
    (11, 5, 'gastosContabilidad'),
    (12, 5, 'chequesContabilidad'),
    (26, 7, 'confCategoria'),
    (27, 2, 'cotizacion'),
    (28, 5, 'confCtaContabilidad'),
    (29, 7, 'confEmail'),
    (30, 5, 'confTipoPago'),
    (31, 5, 'confBancos'),
    (32, 5, 'confImpuestos'),
    (33, 4, 'transferencia'),
    (34, 7, 'confImpresora'),
    (35, 8, 'contrato'),
    (36, 8, 'nomina'),
    (37, 8, 'asistencia');
  
    DROP TABLE IF EXISTS `submenu1`;
    CREATE TABLE IF NOT EXISTS `submenu1` (
      `submenu1_id` int NOT NULL,
      `submenu_id` int NOT NULL,
      `name` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`submenu1_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `submenu1` (`submenu1_id`, `submenu_id`, `name`) VALUES
    (1, 13, 'historialAccesos'),
    (2, 13, 'bitacora'),
    (3, 14, 'reporteVentas'),
    (4, 14, 'cobrarClientes'),
    (5, 15, 'reporteCompras'),
    (6, 15, 'pagarProveedores'),
    (7, 14, 'reporteCotizacion');

    DROP TABLE IF EXISTS `submenu1_plan`;
    CREATE TABLE `submenu1_plan` (
      `submenu1_plan_id` int NOT NULL,
      `submenu1_id` int NOT NULL,
      `planes_id` int NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

    INSERT INTO `submenu1_plan` (`submenu1_plan_id`, `submenu1_id`, `planes_id`) VALUES
    (2, 3, 2),
    (1, 3, 1),
    (3, 4, 2),
    (4, 7, 2),
    (5, 5, 2),
    (6, 3, 3),
    (7, 4, 3),
    (8, 7, 3),
    (9, 5, 3),
    (10, 3, 4),
    (11, 4, 4),
    (12, 7, 4),
    (13, 5, 4),
    (14, 3, 2),
    (15, 4, 2),
    (16, 7, 2),
    (17, 5, 2),
    (18, 1, 5),
    (19, 2, 5),
    (20, 3, 5),
    (21, 4, 5),
    (22, 5, 5),
    (23, 6, 5),
    (24, 7, 5),
    (25, 6, 4);

    DROP TABLE IF EXISTS `submenu_plan`;
    CREATE TABLE `submenu_plan` (
      `submenu_plan_id` int NOT NULL,
      `submenu_id` int NOT NULL,
      `planes_id` int NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    
    INSERT INTO `submenu_plan` (`submenu_plan_id`, `submenu_id`, `planes_id`) VALUES
    (1, 1, 1),
    (2, 2, 1),
    (3, 6, 1),
    (4, 14, 1),
    (5, 16, 1),
    (6, 17, 1),
    (7, 18, 1),
    (8, 19, 1),
    (9, 20, 1),
    (10, 23, 1),
    (11, 24, 1),
    (12, 25, 1),
    (13, 34, 1),
    (14, 1, 2),
    (15, 2, 2),
    (16, 3, 2),
    (17, 4, 2),
    (18, 5, 2),
    (19, 6, 2),
    (20, 7, 2),
    (21, 33, 2),
    (22, 14, 2),
    (23, 15, 2),
    (24, 16, 2),
    (25, 17, 2),
    (26, 18, 2),
    (27, 19, 2),
    (28, 20, 2),
    (29, 21, 2),
    (30, 22, 2),
    (31, 23, 2),
    (32, 24, 2),
    (33, 25, 2),
    (34, 26, 2),
    (35, 29, 2),
    (36, 34, 2),
    (37, 1, 3),
    (38, 2, 3),
    (39, 3, 3),
    (40, 4, 3),
    (41, 5, 3),
    (42, 6, 3),
    (43, 7, 3),
    (44, 11, 3),
    (45, 33, 3),
    (46, 14, 3),
    (47, 15, 3),
    (48, 16, 3),
    (49, 17, 3),
    (50, 18, 3),
    (51, 19, 3),
    (52, 20, 3),
    (53, 21, 3),
    (54, 22, 3),
    (55, 23, 3),
    (56, 24, 3),
    (57, 25, 3),
    (58, 26, 3),
    (59, 29, 3),
    (60, 34, 3),
    (61, 1, 4),
    (62, 2, 4),
    (63, 27, 4),
    (64, 3, 4),
    (65, 4, 4),
    (66, 5, 4),
    (67, 6, 4),
    (68, 7, 4),
    (69, 8, 4),
    (70, 9, 4),
    (71, 10, 4),
    (72, 11, 4),
    (73, 31, 4),
    (74, 33, 4),
    (75, 14, 4),
    (76, 15, 4),
    (77, 16, 4),
    (78, 17, 4),
    (79, 18, 4),
    (80, 19, 4),
    (81, 20, 4),
    (82, 21, 4),
    (83, 22, 4),
    (84, 23, 4),
    (85, 24, 4),
    (86, 25, 4),
    (87, 26, 4),
    (88, 29, 4),
    (89, 34, 4),
    (90, 36, 4),
    (91, 37, 4),
    (92, 1, 4),
    (93, 2, 4),
    (94, 27, 4),
    (95, 3, 4),
    (96, 4, 4),
    (97, 5, 4),
    (98, 6, 4),
    (99, 7, 4),
    (100, 8, 4),
    (101, 9, 4),
    (102, 10, 4),
    (103, 11, 4),
    (104, 31, 4),
    (105, 33, 4),
    (106, 14, 4),
    (107, 15, 4),
    (108, 16, 4),
    (109, 35, 4),
    (110, 36, 4),
    (111, 37, 4),
    (112, 17, 4),
    (113, 18, 4),
    (114, 19, 4),
    (115, 20, 4),
    (116, 21, 4),
    (117, 22, 4),
    (118, 23, 4),
    (119, 24, 4),
    (120, 25, 4),
    (121, 26, 4),
    (122, 29, 4),
    (123, 34, 4),
    (124, 1, 5),
    (125, 2, 5),
    (126, 3, 5),
    (127, 4, 5),
    (128, 5, 5),
    (129, 6, 5),
    (130, 7, 5),
    (131, 8, 5),
    (132, 9, 5),
    (133, 10, 5),
    (134, 11, 5),
    (135, 12, 5),
    (136, 13, 5),
    (137, 14, 5),
    (138, 15, 5),
    (139, 16, 5),
    (140, 17, 5),
    (141, 18, 5),
    (142, 19, 5),
    (143, 20, 5),
    (144, 21, 5),
    (145, 22, 5),
    (146, 23, 5),
    (147, 24, 5),
    (148, 25, 5),
    (149, 26, 5),
    (150, 27, 5),
    (151, 28, 5),
    (152, 29, 5),
    (153, 30, 5),
    (154, 31, 5),
    (155, 32, 5),
    (156, 33, 5),
    (157, 34, 5),
    (158, 35, 5),
    (159, 36, 5);

    DROP TABLE IF EXISTS `tipo_contrato`;
    CREATE TABLE IF NOT EXISTS `tipo_contrato` (
      `tipo_contrato_id` int NOT NULL,
      `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`tipo_contrato_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `tipo_contrato` (`tipo_contrato_id`, `nombre`) VALUES
    (1, 'Permanente'),
    (2, 'Temporal'),
    (3, 'A Termino');
  
    DROP TABLE IF EXISTS `tipo_cuenta`;
    CREATE TABLE IF NOT EXISTS `tipo_cuenta` (
      `tipo_cuenta_id` int NOT NULL,
      `nombre` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      PRIMARY KEY (`tipo_cuenta_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `tipo_cuenta` (`tipo_cuenta_id`, `nombre`, `estado`) VALUES
    (1, 'Efectivo', 1),
    (2, 'Tarjeta', 1),
    (3, 'Banco', 1);
  
    DROP TABLE IF EXISTS `tipo_empleado`;
    CREATE TABLE IF NOT EXISTS `tipo_empleado` (
      `tipo_empleado_id` int NOT NULL,
      `nombre` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`tipo_empleado_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `tipo_empleado` (`tipo_empleado_id`, `nombre`) VALUES
    (1, 'Normal');
  
    DROP TABLE IF EXISTS `tipo_nomina`;
    CREATE TABLE IF NOT EXISTS `tipo_nomina` (
      `tipo_nomina_id` int NOT NULL,
      `nombre` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`tipo_nomina_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `tipo_nomina` (`tipo_nomina_id`, `nombre`) VALUES
    (1, 'Nomina'),
    (2, 'Catorceavo'),
    (3, 'Treceavo');
  
    DROP TABLE IF EXISTS `tipo_pago`;
    CREATE TABLE IF NOT EXISTS `tipo_pago` (
      `tipo_pago_id` int NOT NULL,
      `tipo_cuenta_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `cuentas_id` int NOT NULL COMMENT 'Asignación de Cuenta contable',
      `estado` int NOT NULL COMMENT '1. Activo 2. Inactivo',
      `fecha_registro` datetime DEFAULT NULL,
      PRIMARY KEY (`tipo_pago_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `tipo_pago` (`tipo_pago_id`, `tipo_cuenta_id`, `nombre`, `cuentas_id`, `estado`, `fecha_registro`) VALUES
    (0, 0, 'Sin Pago', 0, 1, NOW()),
    (1, 1, 'Efectivo', 2, 1, NOW()),
    (2, 2, 'Tarjeta', 3, 1, NOW()),
    (3, 3, 'Transferencia', 3, 1, NOW());
  
    DROP TABLE IF EXISTS `tipo_producto`;
    CREATE TABLE IF NOT EXISTS `tipo_producto` (
      `tipo_producto_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      PRIMARY KEY (`tipo_producto_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `tipo_producto` (`tipo_producto_id`, `nombre`) VALUES
    (1, 'Producto'),
    (2, 'Servicio'),
    (3, 'Insumos');
  
    DROP TABLE IF EXISTS `tipo_user`;
    CREATE TABLE IF NOT EXISTS `tipo_user` (
      `tipo_user_id` int NOT NULL,
      `nombre` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`tipo_user_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `tipo_user` (`tipo_user_id`, `nombre`, `estado`, `fecha_registro`) VALUES
    (1, 'Super Administrador', 1, NOW()),
    (2, 'Administrador', 1, NOW());
  
    DROP TABLE IF EXISTS `ubicacion`;
    CREATE TABLE IF NOT EXISTS `ubicacion` (
      `ubicacion_id` int NOT NULL,
      `empresa_id` int NOT NULL,
      `nombre` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `estado` int NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`ubicacion_id`),
      KEY `empresa_id` (`empresa_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `ubicacion` (`ubicacion_id`, `empresa_id`, `nombre`, `estado`, `fecha_registro`) VALUES
    (1, 1, 'San Pedro Sula', 1, NOW());

    DROP TABLE IF EXISTS `users`;
    CREATE TABLE IF NOT EXISTS `users` (
      `users_id` int NOT NULL,
      `colaboradores_id` int NOT NULL,
      `privilegio_id` int NOT NULL,
      `username` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `password` char(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `email` char(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `tipo_user_id` int NOT NULL COMMENT 'Permisos según el tipo de usuario que ingresa al sistema',
      `estado` int NOT NULL COMMENT '1. Activo. 2. Inactivo',
      `fecha_registro` datetime NOT NULL,
      `empresa_id` int NOT NULL,
      `server_customers_id` int NOT NULL,
      PRIMARY KEY (`users_id`),
      KEY `FK_colaborador_id` (`colaboradores_id`),
      KEY `FK_empresa_id` (`empresa_id`),
      KEY `FK_tipo_user_id` (`tipo_user_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
  
    INSERT INTO `users` (`users_id`, `colaboradores_id`, `privilegio_id`, `username`, `password`, `email`, `tipo_user_id`, `estado`, `fecha_registro`, `empresa_id`, `server_customers_id`) VALUES 
    ('1', '1', '1', 'admin', '$contraseña_generadaAdmin', '', 1, '1', '$fecha_registro', $empresa_id, '0'),
    ('2', '2', '1', '$username', '$contraseña_generada', '$correo', '1', '$estado', '$fecha_registro', $empresa_id, '0');
  
    DROP TABLE IF EXISTS `vale`;
    CREATE TABLE IF NOT EXISTS `vale` (
      `vale_id` int(11) NOT NULL,
      `nomina_id` int(11) NOT NULL,
      `colaboradores_id` int(11) NOT NULL,
      `monto` decimal(12,2) NOT NULL,
      `fecha` date NOT NULL,
      `nota` varchar(254) COLLATE utf8mb4_spanish_ci NOT NULL,
      `usuario` int(11) NOT NULL,
      `estado` int(11) NOT NULL COMMENT '0. Pendiente 1. Pagado 2. Anulado',
      `empresa_id` int(11) NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`vale_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    COMMIT;      
  
    DROP TABLE IF EXISTS `vigencia_cotizacion`;
    CREATE TABLE IF NOT EXISTS `vigencia_cotizacion` (
      `vigencia_cotizacion_id` int NOT NULL,
      `valor` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
      `fecha_registro` datetime NOT NULL,
      PRIMARY KEY (`vigencia_cotizacion_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    COMMIT;
  
    INSERT INTO `vigencia_cotizacion` (`vigencia_cotizacion_id`, `valor`, `fecha_registro`) VALUES
    (0, 'Sin Vigencia', NOW()),
    (1, '5 Días', NOW()),
    (2, '10 Días', NOW()),
    (3, '15 Días', NOW()),
    (4, '20 Días', NOW()),
    (5, '25 Días', NOW()),
    (6, '30 Días', NOW()),
    (7, '35 Días', NOW()),
    (8, '40 Días', NOW()),
    (9, '45 Días', NOW()),
    (10, '50 Días', NOW()),
    (11, '55 Días', NOW());

    ";

    // Ejecutar el script SQL
    if ($conn->multi_query($sql) === TRUE) {
      do {
        // Obtiene el resultado actual
        if ($result = $conn->store_result()) {
          // Libera el resultado
          $result->free();
        }
      } while ($conn->more_results() && $conn->next_result());

      echo 'Éxito: El sistema ha sido generado correctamente';
    } else {
      echo 'Error al ejecutar el script SQL: ' . $conn->error;
    }

    // Agregar una pausa de 5 segundos
    sleep(5);

    // Define los valores para inserción en diferentes tablas
    if ($planes_id === '1') {  // EMPRENDEDOR
      $sqlQueries = [
        // Menu: Ventas
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 2, 2, 1, NOW());',
        // Submenu: Clientes, Facturas
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 1, 2, 1, NOW()),
          (2, 2, 2, 1, NOW());',
        // Menu: Almacen
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (2, 4, 2, 1, NOW());',
        // Submenu: Productos
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (3, 6, 2, 1, NOW());',
        // Menu: Reportes
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (3, 6, 2, 1, NOW());',
        // Submenu: Ventas
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (4, 14, 2, 1, NOW());',
        // Submenu1: Ventas
        'INSERT INTO `acceso_submenu1` (`acceso_submenu1_id`, `submenu1_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 3, 2, 1, NOW());',
        // Menu: Recursos Humanos
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (4, 7, 2, 1, NOW());',
        // Submenu: Colaboradores
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (5, 16, 2, 1, NOW());',
        // Menu: Configuracion
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (5, 8, 2, 1, NOW());',
        // Submenu: puestos, users, secuencia, empresa, confMedida, privilegio, tipoUser, confCategoria, confImpresora
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (6, 17, 2, 1, NOW()),
          (7, 18, 2, 1, NOW()),
          (8, 19, 2, 1, NOW()),
          (9, 20, 2, 1, NOW()),
          (10, 23, 2, 1, NOW()),
          (11, 24, 2, 1, NOW()),
          (12, 25, 2, 1, NOW()),
          (13, 26, 2, 1, NOW()),
          (14, 34, 2, 1, NOW());'
      ];

      insertarAccesoMenus($sqlQueries, $conn);
    } else if ($planes_id === '2') {  // ECONÓMICO
      $sqlQueries = [
        // Menu: Ventas
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 2, 2, 1, NOW());',
        // Submenu: Clientes, Facturas, Cotizaciones
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 1, 2, 1, NOW()),
          (2, 2, 2, 1, NOW()),
          (3, 27, 2, 1, NOW());',
        // Menu: Almacen
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (2, 4, 2, 1, NOW());',
        // Submenu: Productos
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (4, 6, 2, 1, NOW());',
        // Menu: Reportes
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (3, 6, 2, 1, NOW());',
        // Submenu: Ventas
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (5, 14, 2, 1, NOW());',
        // Submenu1: Ventas, Cotizaciones
        'INSERT INTO `acceso_submenu1` (`acceso_submenu1_id`, `submenu1_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 3, 2, 1, NOW()),
          (2, 4, 2, 1, NOW());',
        // Menu: Recursos Humanos
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (4, 7, 2, 1, NOW());',
        // Submenu: Colaboradores
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (6, 16, 2, 1, NOW());',
        // Menu: Configuracion
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (5, 8, 2, 1, NOW());',
        // Submenu: puestos, users, secuencia, empresa, confMedida, privilegio, tipoUser, confCategoria, confImpresora
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (7, 17, 2, 1, NOW()),
          (8, 18, 2, 1, NOW()),
          (9, 19, 2, 1, NOW()),
          (10, 20, 2, 1, NOW()),
          (11, 23, 2, 1, NOW()),
          (12, 24, 2, 1, NOW()),
          (13, 25, 2, 1, NOW()),
          (14, 26, 2, 1, NOW()),
          (15, 34, 2, 1, NOW());'
      ];

      insertarAccesoMenus($sqlQueries, $conn);
    } else if ($planes_id === '3') {  // BÁSICO
      $sqlQueries = [
        // Menu: Ventas
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 2, 2, 1, NOW());',
        // Submenu: Clientes, Facturas, Cotizaciones, Cajas
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 1, 2, 1, NOW()),
          (2, 2, 2, 1, NOW()),
          (3, 27, 2, 1, NOW()),
          (4, 3, 2, 1, NOW());',
        // Menu: Compras
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (2, 3, 2, 1, NOW());',
        // Submenu: Proveedores, Compras
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (5, 4, 2, 1, NOW()),
          (6, 5, 2, 1, NOW());',
        // Menu: Almacen
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (3, 4, 2, 1, NOW());',
        // Submenu: Productos, Inventario
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (7, 6, 2, 1, NOW()),
          (8, 33, 2, 1, NOW());',
        // Menu: Reportes
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (4, 6, 2, 1, NOW());',
        // Submenu: Ventas
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (9, 14, 2, 1, NOW());',
        // Submenu1: Ventas, Cotizaciones, CXC Clientes
        'INSERT INTO `acceso_submenu1` (`acceso_submenu1_id`, `submenu1_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (1, 3, 2, 1, NOW()),
          (2, 7, 2, 1, NOW()),
          (3, 4, 2, 1, NOW());',
        // Submenu: Compras
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (10, 15, 2, 1, NOW());',
        // Submenu1: Compras, CXP Proveedores
        'INSERT INTO `acceso_submenu1` (`acceso_submenu1_id`, `submenu1_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (4, 5, 2, 1, NOW()),
          (5, 6, 2, 1, NOW());',
        // Menu: Recursos Humanos
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (5, 7, 2, 1, NOW());',
        // Submenu: Colaboradores
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (11, 16, 2, 1, NOW());',
        // Menu: Configuracion
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (6, 8, 2, 1, NOW());',
        // Submenu: puestos, users, secuencia, empresa, confMedida, privilegio, tipoUser, confCategoria, confImpresora
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
          (12, 17, 2, 1, NOW()),
          (13, 18, 2, 1, NOW()),
          (14, 19, 2, 1, NOW()),
          (15, 20, 2, 1, NOW()),
          (16, 23, 2, 1, NOW()),
          (17, 24, 2, 1, NOW()),
          (18, 25, 2, 1, NOW()),
          (19, 26, 2, 1, NOW()),
          (20, 34, 2, 1, NOW());'
      ];

      insertarAccesoMenus($sqlQueries, $conn);
    } else if ($planes_id === '4') {  // PREMIUM
      $sqlQueries = [
        'INSERT INTO `acceso_menu` (`acceso_menu_id`, `menu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
            (1, 8, 2, 1, NOW()),
            (2, 5, 2, 1, NOW()),
            (3, 7, 2, 1, NOW()),
            (4, 6, 2, 1, NOW()),
            (5, 4, 2, 1, NOW()),
            (6, 3, 2, 1, NOW()),
            (7, 2, 2, 1, NOW()),
            (8, 1, 2, 1, NOW());',
        'INSERT INTO `acceso_submenu1` (`acceso_submenu1_id`, `submenu1_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
            (1, 1, 2, 1, NOW()),
            (2, 2, 2, 1, NOW()),
            (3, 3, 2, 1, NOW()),
            (4, 4, 2, 1, NOW()),
            (5, 6, 2, 1, NOW()),
            (6, 5, 2, 1, NOW()),
            (7, 7, 2, 1, NOW()),
            (8, 1, 2, 2, NOW()),
            (9, 2, 2, 2, NOW()),
            (10, 3, 2, 1, NOW()),
            (11, 7, 2, 1, NOW()),
            (12, 4, 2, 1, NOW()),
            (13, 5, 2, 1, NOW()),
            (14, 6, 2, 1, NOW());',
        'INSERT INTO `acceso_submenu` (`acceso_submenu_id`, `submenu_id`, `privilegio_id`, `estado`, `fecha_registro`) VALUES
            (1, 34, 2, 1, NOW()),
            (2, 29, 2, 1, NOW()),
            (3, 26, 2, 1, NOW()),
            (4, 25, 2, 1, NOW()),
            (5, 24, 2, 1, NOW()),
            (6, 23, 2, 1, NOW()),
            (7, 22, 2, 1, NOW()),
            (8, 21, 2, 1, NOW()),
            (9, 20, 2, 1, NOW()),
            (10, 19, 2, 1, NOW()),
            (11, 18, 2, 1, NOW()),
            (12, 32, 2, 1, NOW()),
            (13, 31, 2, 1, NOW()),
            (14, 30, 2, 1, NOW()),
            (15, 28, 2, 1, NOW()),
            (16, 12, 2, 1, NOW()),
            (17, 11, 2, 1, NOW()),
            (18, 10, 2, 1, NOW()),
            (19, 9, 2, 1, NOW()),
            (20, 8, 2, 1, NOW()),
            (21, 17, 2, 1, NOW()),
            (22, 36, 2, 1, NOW()),
            (23, 35, 2, 1, NOW()),
            (24, 16, 2, 1, NOW()),
            (25, 15, 2, 1, NOW()),
            (26, 14, 2, 1, NOW()),
            (27, 13, 2, 1, NOW()),
            (28, 33, 2, 1, NOW()),
            (29, 7, 2, 1, NOW()),
            (30, 6, 2, 1, NOW()),
            (31, 5, 2, 1, NOW()),
            (32, 4, 2, 1, NOW()),
            (33, 27, 2, 1, NOW()),
            (34, 3, 2, 1, NOW()),
            (35, 2, 2, 1, NOW()),
            (36, 1, 2, 1, NOW());'
      ];

      insertarAccesoMenus($sqlQueries, $conn);
    }

    // GUARDAMOS LOS DATOS EN EL server_customers
    $tabla = 'server_customers';

    // CONSULTAMOS EL CODIGO DEL CLIENTE ANTES DE GUARDARLO
    $generadoCodCliente = false;
    $codigo_cliente = '';

    while ($generadoCodCliente) {
      // Generar un PIN aleatorio de 8 dígitos
      $codigo_cliente = mt_rand(10000000, 999999);

      $camposServer_customers = ['server_customers_id'];
      $condicionesServer_customers = ['codigo_cliente' => $codigo_cliente];
      $orderBy = '';
      $tablaJoin = '';
      $condicionesJoin = [];
      $resultadoServer_customers = $database->consultarTabla($tabla, $camposServer_customers, $condicionesServer_customers, $orderBy, $tablaJoin, $condicionesJoin);

      if (empty($resultadoServer_customers)) {
        $generadoCodCliente = true;

        break;  // SALIMOS DEL CICLO
      } else {
        $generadoCodCliente = false;
      }
    }

    $campos = ['server_customers_id', 'clientes_id', 'db', 'validar', 'sistema_id', 'planes_id', 'estado', 'codigo_cliente'];
    $campoCorrelativo = 'server_customers_id';
    $server_customers_id = $database->obtenerCorrelativo($tabla, $campoCorrelativo);

    $valores = [$server_customers_id, $clientes_id, $databaseCliente, $validar, $sistema_id, $planes_id, '1', $codigo_cliente];
    $database->insertarRegistro($tabla, $campos, $valores);

    // GUARDAMOS LOS DATOS EN LA TABLA DEL CLIENTE
    $sqlQueries = [
      "INSERT INTO `server_customers` (`server_customers_id`, `clientes_id`, `codigo_cliente`, `db`, `planes_id`, `sistema_id`, `validar`, `estado`) VALUES
      (1, $clientes_id, '$codigo_cliente', '$databaseCliente', $planes_id, $sistema_id, $validar, 1);"
    ];

    insertarAccesoMenus($sqlQueries, $conn);

    // GUARDAMOS LOS DATOS DEL COLABORADOR EN ESTE CASO CON UNA PUESTO O CATEGORIA CLIENTES
    $puestos_id_defualt = 5;  // CLIENTES
    $tablarRegistroColaboradores = 'colaboradores';
    $camposRegistroColaboradores = ['colaboradores_id', 'puestos_id', 'nombre', 'apellido', 'identidad', 'estado', 'telefono', 'empresa_id', 'fecha_registro', 'fecha_ingreso', 'fecha_egreso'];
    $campoCorrelativoRegistroColaboradores = 'colaboradores_id';
    $server_colaboradores_id = $database->obtenerCorrelativo($tablarRegistroColaboradores, $campoCorrelativoRegistroColaboradores);

    $valoresColaboradores = [$server_colaboradores_id, $puestos_id_defualt, $razon_social, '', $rtn, '1', $telefono, $empresa_id, $fecha_registro, $fecha_registro, ''];
    $database->insertarRegistro($tablarRegistroColaboradores, $camposRegistroColaboradores, $valoresColaboradores);

    // ACTUALIZAMOS LA TABLA COLABORADORES
    $query_colaboradores = "UPDATE colaboradores SET colaboradores_id = '$server_colaboradores_id' WHERE identidad = '$rtn'";
    actualizarRegistros($query_colaboradores, $conn);

    // ACTUALIZAMOS LA TABLA USUARIOS
    $query_colaboradores = "UPDATE users SET colaboradores_id = '$server_colaboradores_id' WHERE identidad = '$rtn'";
    actualizarRegistros($query_colaboradores, $conn);

    // GUARDAMOS LOS DATOS DEL USUARIO
    $tablarRegistroUsers = 'users';
    $camposRegistroUsers = ['users_id', 'colaboradores_id', 'privilegio_id', 'password', 'email', 'tipo_user_id', 'estado', 'fecha_registro', 'empresa_id', 'server_customers_id'];
    $campoCorrelativoRegistroUsers = 'users_id';
    $server_users_id = $database->obtenerCorrelativo($tablarRegistroUsers, $campoCorrelativoRegistroUsers);

    // ACTUALIZAMOS LA TABLA DEL CLIENTE
    $query_users = "UPDATE users SET server_customers_id = '$server_customers_id', colaboradores_id = '$server_colaboradores_id' WHERE email = '$correo'";
    actualizarRegistros($query_users, $conn);

    $valoresUsers = [$server_users_id, $server_colaboradores_id, $privilegio_id, $contraseña_generada, $correo, $tipo_user_id, $estado, $fecha_registro, $empresa_id, $server_customers_id];
    $database->insertarRegistro($tablarRegistroUsers, $camposRegistroUsers, $valoresUsers);

    // MODIFICAMOS LOS DATOS DEL CLIENTE
    $datos_actualizar = [
      'empresa' => $empresa,
      'eslogan' => $eslogan,
      'otra_informacion' => $otra_informacion,
      'whatsapp' => $celular
    ];

    // Condiciones para seleccionar los registros que se actualizarán
    $condiciones_actualizar = ['clientes_id' => $clientes_id];

    // Llamar a la función para actualizar los registros
    if ($database->actualizarRegistros('clientes', $datos_actualizar, $condiciones_actualizar)) {
      // echo "Registros actualizados correctamente.";
    } else {
      echo 'Error al actualizar registros.';
    }

    // OBTENEMOS EL CORREO DEL REVENDEDOR  privilegio_id => 3 ES EL REVENDEDOR
    $users_id = $_SESSION['users_id_sd'];
    $tablaUsers = 'users';
    $camposUsers = ['email', 'colaboradores_id'];
    $condicionesUsers = ['users_id' => $users_id, 'privilegio_id' => 3];
    $orderBy = '';
    $tablaJoin = '';
    $condicionesJoin = [];
    $resultadoUsers = $database->consultarTabla($tablaUsers, $camposUsers, $condicionesUsers, $orderBy, $tablaJoin, $condicionesJoin);

    $correo_revendedor = '';
    $colaboradores_id_revendedor = '';

    if (!empty($resultadoUsers)) {
      $correo_revendedor = $resultadoUsers[0]['email'];
      $colaboradores_id_revendedor = $resultadoUsers[0]['colaboradores_id'];
    }

    // OBTENEMOS EL NOMBRE DEL REVENDEDOR
    $tablaColaboradoresRevendedores = 'colaboradores';
    $camposColaboradoresRevendedores = ['nombre', 'apellido'];
    $condicionesColaboradoresRevendedores = ['colaboradores_id' => $colaboradores_id_revendedor];
    $orderBy = '';
    $tablaJoin = '';
    $condicionesJoin = [];
    $resultadoColaboradoresRevendedores = $database->consultarTabla($tablaColaboradoresRevendedores, $camposColaboradoresRevendedores, $condicionesColaboradoresRevendedores, $orderBy, $tablaJoin, $condicionesJoin);

    $nombre_revendedor = '';

    if (!empty($resultadoColaboradoresRevendedores)) {
      $nombre_revendedor = trim($resultadoColaboradoresRevendedores[0]['nombre'] . ' ' . $resultadoColaboradoresRevendedores[0]['apellido']);
    }

    $correo_tipo_id = '1';  // Notificaciones
    $destinatarios = array($correo => $razon_social);

    // Destinatarios en copia oculta (Bcc)
    // OBTENEMOS LOS CORREOS DE LOS ADMINISTRADORES
    $tablaColaboradores = 'colaboradores';
    $camposColaboradores = ['users.email', "CONCAT(colaboradores.nombre, ' ', colaboradores.apellido) AS nombre_completo"];
    $condicionesColaboradores = ['users.privilegio_id' => ['1', '2'], 'users.estado' => 1];  // Usar un array para las condiciones
    $orderBy = '';
    $tablaJoin = 'users';
    $condicionesJoin = ['colaboradores_id' => 'colaboradores_id'];
    $resultadoColaboradores = $database->consultarTabla($tablaColaboradores, $camposColaboradores, $condicionesColaboradores, $orderBy, $tablaJoin, $condicionesJoin);

    $bccDestinatarios = [];

    // Recorre los resultados de la consulta
    foreach ($resultadoColaboradores as $row) {
      // Obtén el correo electrónico y el nombre completo

      $correo = $row['email'];
      $nombreCompleto = $row['nombre_completo'];

      // Agrega el correo y el nombre completo al array $bccDestinatarios
      $bccDestinatarios[$correo] = $nombreCompleto;
    }

    if ($correo_revendedor !== '') {
      $bccDestinatarios[$correo_revendedor] = $nombre_revendedor;
    }

    $asunto = '¡Bienvenido! Registro de Usuario Exitoso';

    $mensaje = '
    <div style="padding: 20px;">
      <p style="margin-bottom: 10px;">
        ¡Hola ' . $razon_social . "!
      </p>
      
      <p style=\"margin-bottom: 10px;\">
        ¡Bienvenido a <b>ES MULTISERVICIOS</b> con <b>IZZY</b>! Estamos encantados de darle la bienvenida a nuestra plataforma de gestión de facturación e inventario diseñada para hacer su vida más fácil.
      </p>
      
      <p style=\"margin-bottom: 10px;\">
        Le damos las gracias por elegirnos como su solución de confianza para administrar su negocio de manera eficiente. Su registro en nuestro sistema ha sido exitoso y ahora es parte de la familia <b>ES MULTISERVICIOS</b>.
      </p>
      
      <ul style=\"margin-bottom: 12px;\">
        <li><b>Empesa</b>: " . $razon_social . '</li>
        <li><b>Usuario</b>: ' . $clientes_correo . '</li>
        <li><b>Contraseña</b>: ' . $pass . '</li>
        <li><b>Perfil</b>: Administrador</li>
        <li><b>Nuevo Registro</b></li>
        <li><b>Acceso al Sistema</b>:  <a href=' . SERVERURL . ">Clic para Acceder a IZZY<a></li>
      </ul>
      
      <p style=\"margin-bottom: 10px;\">
        Recuerde que la seguridad es una prioridad para nosotros. Por ello, le recomendamos cambiar su contraseña temporal en su primera sesión.
      </p>
      
      <p style=\"margin-bottom: 10px;\">
        Si tiene alguna pregunta o necesita ayuda en cualquier momento, no dude en ponerse en contacto con nuestro dedicado equipo de soporte. Estamos aquí para proporcionarle la asistencia que necesita.
      </p>
      
      <p style=\"margin-bottom: 10px;\">
        Le invitamos a explorar todas las características y funcionalidades que IZZY ofrece para simplificar la gestión de su negocio. Su éxito es nuestro objetivo y estamos comprometidos en ayudarle en cada paso del camino.
      </p>
  
      <p style=\"margin-bottom: 10px;\">
        ¡Empiece a explorar y a aprovechar al máximo nuestra plataforma de gestión de facturación e inventario!
      </p>
      
      <p style=\"margin-bottom: 10px;\">
        Gracias por unirse a <b>ES MULTISERVICIOS</b> con IZZY. Esperamos que esta plataforma sea una herramienta valiosa para su negocio.
      </p>
      
      <p style=\"margin-bottom: 10px;\">
        Saludos cordiales,
      </p>
      
      <p>
        <b>El Equipo de " . $razon_social . '</b>
      </p>                
    </div>
    ';

    $archivos_adjuntos = [];

    // ENVIAMOS EL CORREO DEL CLIENTE NUEVO AL RESELLER, ADMINISTRADOR, SUPER ADMINISTRADOR Y AL CLIENTE, SOBRE LA CREACIÓN DEL SISTEMA
    $sendEmail->enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa_id, $archivos_adjuntos);

    // Cerrar la conexión a la segunda base de datos
    $conn->close();
  } else {
    echo "Error Sistema Existe: Lo sentimos el sistema $nombre_sistema con el plan $nombre_plan ya esta activo para el cliente: $razon_social";
  }
} else {
  echo "Error Correo Existe: Lo sentimos el correo $correo ya existe en nuestros registros o pertenece a otro cliente, por favor validar en los registros de usuarios antes de continuar, o solicite ayuda con su administrador o supervisor";
}

function insertarAccesoMenus($sqlQueries, $conn)
{
  foreach ($sqlQueries as $query) {
    if (!$conn->query($query)) {
      echo 'Error en la inserción: ' . $conn->error . '<br>';
    }

    // Verificar si hay más resultados disponibles antes de llamar a next_result
    if ($conn->more_results()) {
      $conn->next_result();
    }
  }
}

function actualizarRegistros($query, $conn)
{
  if (!$conn->query($query)) {
    echo 'Error en la inserción: ' . $conn->error . '<br>';
  }
}

function generar_password_complejoScript()
{
  $largo = 12;
  $cadena_base = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $password = '';

  $limite = strlen($cadena_base) - 1;

  for ($i = 0; $i < $largo; $i++)
    $password .= $cadena_base[rand(0, $limite)];

  return $password;
}

function encryptionScript($string)
{
  $ouput = FALSE;
  $key = hash('sha256', SECRET_KEY);
  $iv = substr(hash('sha256', SECRET_IV), 0, 16);
  $output = openssl_encrypt($string, METHOD, $key, 0, $iv);
  $output = base64_encode($output);

  return $output;
}
