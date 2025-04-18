<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

if (isset($_POST['plan_id']) && isset($_POST['menu_id'])) {
    try {
        $planId = $mainModel->cleanString($_POST['plan_id']);
        $menuId = $mainModel->cleanString($_POST['menu_id']);
        $estado = $mainModel->cleanString($_POST['estado']);
        
        // 1. Actualizar en la base de datos principal
        $conexionPrincipal = $mainModel->connection();
        
        // Verificar si ya existe
        $query = "SELECT * FROM menu_plan WHERE planes_id = '$planId' AND menu_id = '$menuId'";
        $result = $conexionPrincipal->query($query);
        
        if ($result->num_rows > 0) {
            $update = "UPDATE menu_plan SET estado = '$estado' WHERE menu_id = '$menuId' AND planes_id = '$planId'";
            $conexionPrincipal->query($update);
        } else {
            $insert = "INSERT INTO menu_plan (menu_id, planes_id, estado) VALUES ('$menuId', '$planId', '$estado')";
            $conexionPrincipal->query($insert);
        }
        
        // 2. Actualizar en bases de datos de clientes
        $clientes = $mainModel->ejecutar_consulta("SELECT db FROM server_customers WHERE planes_id = '$planId' AND estado = 1 AND db != ''");
        
        foreach ($clientes as $cliente) {
            $dbName = $cliente['db'];
            $configCliente = [
                'host' => SERVER,
                'user' => USER,
                'pass' => PASS,
                'name' => $dbName
            ];
            
            // Verificar si la base de datos existe antes de conectar
            if ($mainModel->databaseExists($dbName)) {
                $connCliente = $mainModel->connectToDatabase($configCliente);
                
                if ($connCliente !== false) {
                    // Verificar si la tabla existe
                    $tableExists = $connCliente->query("SHOW TABLES LIKE 'menu_plan'");
                    if ($tableExists->num_rows > 0) {
                        $resultCliente = $connCliente->query("SELECT 1 FROM menu_plan WHERE planes_id = '$planId' AND menu_id = '$menuId'");
                        
                        if ($resultCliente->num_rows > 0) {
                            $connCliente->query("UPDATE menu_plan SET estado = '$estado' WHERE menu_id = '$menuId' AND planes_id = '$planId'");
                        } else {
                            $connCliente->query("INSERT INTO menu_plan (menu_id, planes_id, estado) VALUES ('$menuId', '$planId', '$estado')");
                        }
                    }
                    $connCliente->close();
                }
            }
        }
        
        $conexionPrincipal->close();
        
        echo json_encode([
            'type' => 'success',
            'title' => 'Operación exitosa',
            'message' => 'El registro se ha actualizado correctamente en todas las bases de datos.',
            'estado' => true
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'type' => 'error',
            'title' => 'Error en la operación',
            'message' => 'Hubo un problema al procesar la solicitud: ' . $e->getMessage(),
            'estado' => false
        ]);
    }
} else {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error en la operación',
        'message' => 'Faltan parámetros requeridos.',
        'estado' => false
    ]);
}