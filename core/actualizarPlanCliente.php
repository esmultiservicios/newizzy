<?php
// actualizarPlanCliente.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();
$conexion = $mainModel->connection();

try {
    $conexion->autocommit(false);

    // Limpiar y validar datos
    $serverCustomersId = $mainModel->cleanString($_POST['server_customers_id']);
    $clienteId = $mainModel->cleanString($_POST['cliente_id']);
    $planId = $mainModel->cleanString($_POST['planes_id']);
    $userExtra = $mainModel->cleanString($_POST['user_extra']);
    $validar = isset($_POST['validar']) ? $mainModel->cleanString($_POST['validar']) : 1;
    $estado = isset($_POST['estado']) ? $mainModel->cleanString($_POST['estado']) : 1;

    // 1. Obtener nombre de la base del cliente
    $stmtDB = $conexion->prepare("SELECT db FROM server_customers WHERE server_customers_id = ?");
    $stmtDB->bind_param("i", $serverCustomersId);
    $stmtDB->execute();
    $resultDB = $stmtDB->get_result();
    $dbInfo = $resultDB->fetch_assoc();
    $dbName = $dbInfo['db'];
    $stmtDB->close();

    // 2. Actualizar tabla server_customers en base principal
    $stmtUpdateServer = $conexion->prepare("UPDATE server_customers SET planes_id = ?, validar = ?, estado = ? WHERE server_customers_id = ?");
    $stmtUpdateServer->bind_param("iiii", $planId, $validar, $estado, $serverCustomersId);
    $stmtUpdateServer->execute();
    $stmtUpdateServer->close();

    // 4. Verificar y actualizar/insertar en la base del cliente si existe
    if (!empty($dbName)) {
        $configCliente = [
            'host' => SERVER,
            'user' => USER,
            'pass' => PASS,
            'name' => $dbName
        ];

        $conexionCliente = $mainModel->connectToDatabase($configCliente);

        if ($conexionCliente) {
            $stmtCheckCliente = $conexionCliente->prepare("SELECT plan_id FROM plan WHERE plan_id = 1");
            $stmtCheckCliente->execute();
            $stmtCheckCliente->store_result();

            if ($stmtCheckCliente->num_rows > 0) {
                // Ya existe, actualizar
                $stmtUpdateCliente = $conexionCliente->prepare("UPDATE plan SET planes_id = ?, user_extra = ?, fecha_registro = NOW() WHERE plan_id = 1");
                $stmtUpdateCliente->bind_param("ii", $planId, $userExtra);
                $stmtUpdateCliente->execute();
                $stmtUpdateCliente->close();
            } else {
                // No existe, insertar
                $stmtInsertCliente = $conexionCliente->prepare("INSERT INTO plan (plan_id, planes_id, user_extra, fecha_registro) VALUES (1, ?, ?, NOW())");
                $stmtInsertCliente->bind_param("ii", $planId, $userExtra);
                $stmtInsertCliente->execute();
                $stmtInsertCliente->close();
            }

            $stmtCheckCliente->close();
            $conexionCliente->close();
        }
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'type' => 'success',
        'title' => 'Registro Actualizado',
        'message' => 'Plan actualizado correctamente en todas las bases de datos'
    ]);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode([
        'success' => false,
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error al actualizar plan: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}
