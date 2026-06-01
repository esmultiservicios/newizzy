<?php
// core/getCodigoCliente.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

$insMainModel = new mainModel();

$dbActual = $GLOBALS['db'];

$response = [
    'success' => false,
    'codigo_cliente' => null,
    'server_customers_id' => null,
    'is_main_db' => ($dbActual === DB_MAIN),
    'error' => null
];

try {
    if ($dbActual === DB_MAIN) {
        $response['success'] = true;
        echo json_encode($response);
        exit;
    }

    $mysqliMain = $insMainModel->connectionDBLocal(DB_MAIN);

    $query = "SELECT 
                    server_customers_id,
                    codigo_cliente,
                    db
              FROM server_customers
              WHERE db = ?
              AND codigo_cliente IS NOT NULL
              AND estado = 1
              LIMIT 1";

    $stmt = $mysqliMain->prepare($query);

    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $mysqliMain->error);
    }

    $stmt->bind_param("s", $dbActual);

    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows <= 0) {
        throw new Exception("No se encontró código de cliente para esta base de datos");
    }

    $row = $result->fetch_assoc();

    $response = [
        'success' => true,
        'codigo_cliente' => $row['codigo_cliente'],
        'server_customers_id' => $row['server_customers_id'],
        'is_main_db' => false,
        'error' => null
    ];

    $stmt->close();
    $mysqliMain->close();

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log("Error en getCodigoCliente.php: " . $e->getMessage());

    $response['success'] = false;
    $response['error'] = $e->getMessage();

    echo json_encode($response);
    exit;
}