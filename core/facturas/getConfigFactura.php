<?php
// core/facturas/getConfigFactura.php

header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

function responderGetConfigFactura($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $mainModel = new mainModel();

    if (empty($_SESSION['users_id_sd'])) {
        responderGetConfigFactura([
            'success' => false,
            'message' => 'Sesión no válida.'
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        responderGetConfigFactura([
            'success' => false,
            'message' => 'Método no permitido.'
        ]);
    }

    $token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';

    if ($token === '') {
        responderGetConfigFactura([
            'success' => false,
            'message' => 'Token no recibido.'
        ]);
    }

    if (
        empty($_SESSION['admin_config_token']) ||
        empty($_SESSION['admin_config_token_expira']) ||
        $_SESSION['admin_config_token'] !== $token ||
        time() > (int)$_SESSION['admin_config_token_expira']
    ) {
        responderGetConfigFactura([
            'success' => false,
            'message' => 'Debe validar un administrador para ver esta configuración.'
        ]);
    }

    $db = $mainModel->connection();

    if (!$db) {
        responderGetConfigFactura([
            'success' => false,
            'message' => 'No se pudo conectar a la base de datos.'
        ]);
    }

    $catalogo = [
        1 => [
            'accion' => 'Mostrar detalle facturas - Caja',
            'titulo' => 'Mostrar detalle de facturas en caja',
            'descripcion' => 'Controla si el módulo de caja puede mostrar el detalle de facturas relacionado con ventas y movimientos.',
            'activo_texto' => 'Activo: el usuario puede ver el detalle de facturas dentro de caja.',
            'inactivo_texto' => 'Inactivo: se oculta el detalle de facturas en caja y solo quedan visibles los resúmenes.',
            'categoria' => 'Caja'
        ],
        2 => [
            'accion' => 'Validar Apertura Caja',
            'titulo' => 'Validar apertura de caja',
            'descripcion' => 'Controla si el sistema exige una caja abierta antes de facturar o cobrar.',
            'activo_texto' => 'Activo: el usuario debe tener caja abierta para operar ventas y cobros.',
            'inactivo_texto' => 'Inactivo: permite operar sin validar apertura de caja.',
            'categoria' => 'Caja'
        ],
        3 => [
            'accion' => 'Activar Proforma',
            'titulo' => 'Activar proformas',
            'descripcion' => 'Controla si la vista de facturación permite generar documentos tipo proforma.',
            'activo_texto' => 'Activo: aparece la opción para generar factura proforma.',
            'inactivo_texto' => 'Inactivo: se oculta o bloquea la generación de proformas.',
            'categoria' => 'Proformas'
        ],
        4 => [
            'accion' => 'Activar Rebajar Inventario Proforma',
            'titulo' => 'Rebajar inventario en proforma',
            'descripcion' => 'Controla si una proforma puede descontar inventario cuando el usuario lo indique.',
            'activo_texto' => 'Activo: la proforma puede rebajar inventario si se marca esa opción.',
            'inactivo_texto' => 'Inactivo: la proforma no rebaja inventario aunque el usuario lo intente.',
            'categoria' => 'Proformas'
        ],
        5 => [
            'accion' => 'Activar Cobro Proforma',
            'titulo' => 'Cobrar proformas',
            'descripcion' => 'Controla si una proforma de contado puede abrir el modal de pago.',
            'activo_texto' => 'Activo: la proforma contado puede cobrarse desde el modal de pago.',
            'inactivo_texto' => 'Inactivo: la proforma solo se imprime o registra sin cobro directo.',
            'categoria' => 'Proformas'
        ],
        6 => [
            'accion' => 'Activar ISV Proforma',
            'titulo' => 'Calcular ISV en proformas',
            'descripcion' => 'Controla si las proformas calculan ISV cuando el producto tiene ISV activo.',
            'activo_texto' => 'Activo: la proforma calcula ISV según isv_venta, isv1 e isv2 del producto.',
            'inactivo_texto' => 'Inactivo: la proforma fuerza ISV 0 aunque el producto tenga ISV.',
            'categoria' => 'Proformas'
        ]
    ];

    $ids = array_keys($catalogo);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "
        SELECT config_id, accion, activar
        FROM config
        WHERE config_id IN ($placeholders)
        ORDER BY config_id ASC
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        responderGetConfigFactura([
            'success' => false,
            'message' => 'No se pudo preparar la consulta de configuración.',
            'error' => $db->error
        ]);
    }

    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        responderGetConfigFactura([
            'success' => false,
            'message' => 'No se pudo ejecutar la consulta de configuración.',
            'error' => $error
        ]);
    }

    $rs = $stmt->get_result();
    $configActual = [];

    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $configActual[(int)$row['config_id']] = $row;
        }
    }

    $stmt->close();

    $items = [];

    foreach ($catalogo as $configId => $meta) {
        $row = isset($configActual[$configId]) ? $configActual[$configId] : null;

        $activar = $row ? (int)$row['activar'] : 2;
        $activar = ($activar === 1) ? 1 : 2;

        $items[] = [
            'config_id' => $configId,
            'accion' => $row ? $row['accion'] : $meta['accion'],
            'activar' => $activar,
            'activo' => $activar === 1,
            'titulo' => $meta['titulo'],
            'descripcion' => $meta['descripcion'],
            'activo_texto' => $meta['activo_texto'],
            'inactivo_texto' => $meta['inactivo_texto'],
            'categoria' => $meta['categoria']
        ];
    }

    responderGetConfigFactura([
        'success' => true,
        'message' => 'Configuración cargada correctamente.',
        'config' => $items
    ]);

} catch (Throwable $e) {
    responderGetConfigFactura([
        'success' => false,
        'message' => 'Error al cargar configuración.',
        'error' => $e->getMessage()
    ]);
}