<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

if (!isset($_POST['id']) || !isset($_POST['tipo'])) {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
    exit();
}

$insMainModel = new mainModel();

try {
    $id = $_POST['id'];
    $tipo = $_POST['tipo'];
    $hasDependencies = false;
    $nombreModulo = null;

    // Obtener conexión principal
    $conexionPrincipal = $insMainModel->connection();
    $conexionPrincipal->autocommit(false);

    // Validar dependencias en la base principal
    if ($tipo == 'menu') {
        $check = $conexionPrincipal->query("SELECT submenu_id FROM submenu WHERE menu_id = '$id'");
        if ($check->num_rows > 0) $hasDependencies = true;
        
        // Obtener nombre del menú
        $result = $conexionPrincipal->query("SELECT name FROM menu WHERE menu_id = '$id'");
        $row = $result->fetch_assoc();
        $nombreModulo = $row['name'];
    } elseif ($tipo == 'submenu') {
        $check = $conexionPrincipal->query("SELECT submenu1_id FROM submenu1 WHERE submenu_id = '$id'");
        if ($check->num_rows > 0) $hasDependencies = true;
        
        // Obtener nombre del submenú
        $result = $conexionPrincipal->query("SELECT name FROM submenu WHERE submenu_id = '$id'");
        $row = $result->fetch_assoc();
        $nombreModulo = $row['name'];
    } else {
        // Obtener nombre del submenú nivel 1
        $result = $conexionPrincipal->query("SELECT name FROM submenu1 WHERE submenu1_id = '$id'");
        $row = $result->fetch_assoc();
        $nombreModulo = $row['name'];
    }

    if ($hasDependencies) {
        $conexionPrincipal->rollback();
        echo json_encode([
            "type" => "warning",
            "title" => "Advertencia",
            "message" => "No se puede eliminar porque tiene elementos dependientes"
        ]);
        exit();
    }

    // Eliminar de la base principal
    if ($tipo == 'menu') {
        $sql = "DELETE FROM menu WHERE menu_id = '$id'";
    } elseif ($tipo == 'submenu') {
        $sql = "DELETE FROM submenu WHERE submenu_id = '$id'";
    } else {
        $sql = "DELETE FROM submenu1 WHERE submenu1_id = '$id'";
    }

    if (!$conexionPrincipal->query($sql)) {
        $conexionPrincipal->rollback();
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo eliminar el registro de la base principal"
        ]);
        exit();
    }

    // Eliminar de todas las bases de datos de clientes
    $clientes = $insMainModel->ejecutar_consulta("SELECT db FROM server_customers WHERE estado = 1 AND db != ''");
    $erroresClientes = [];

    foreach ($clientes as $cliente) {
        $dbName = $cliente['db'];
        
        if ($insMainModel->databaseExists($dbName)) {
            $configCliente = [
				'host' => SERVER,
				'user' => USER,
				'pass' => PASS,
                'name' => $dbName
            ];
            
            $connCliente = $insMainModel->connectToDatabase($configCliente);
            
            if ($connCliente !== false) {
                try {
                    // Verificar si la tabla existe
                    $tableName = $tipo == 'menu' ? 'menu' : ($tipo == 'submenu' ? 'submenu' : 'submenu1');
                    $tableExists = $connCliente->query("SHOW TABLES LIKE '$tableName'");
                    
                    if ($tableExists->num_rows > 0) {
                        // Eliminar de la base del cliente
                        $sqlCliente = "DELETE FROM $tableName WHERE " . ($tipo == 'menu' ? 'menu_id' : ($tipo == 'submenu' ? 'submenu_id' : 'submenu1_id')) . " = '$id'";
                        
                        if (!$connCliente->query($sqlCliente)) {
                            $erroresClientes[] = "Error al eliminar de $dbName: " . $connCliente->error;
                        }
                    }
                } catch (Exception $e) {
                    $erroresClientes[] = "Error en $dbName: " . $e->getMessage();
                } finally {
                    $connCliente->close();
                }
            }
        }
    }

    // Eliminar de lista blanca si es un menú principal
    if ($tipo == 'menu' && $nombreModulo) {
        $nombre_config = 'configuracion_principal';
        $insMainModel->eliminar_modulo_lista_blanca($nombre_config, $nombreModulo);
    }

    // Confirmar transacción principal
    $conexionPrincipal->commit();

    $response = [
        "type" => "success",
        "title" => "Éxito",
        "message" => "Registro eliminado correctamente"
    ];

    if (!empty($erroresClientes)) {
        $response['warnings'] = $erroresClientes;
    }

    echo json_encode($response);

} catch (Exception $e) {
    if (isset($conexionPrincipal)) {
        $conexionPrincipal->rollback();
    }
    
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Error en el servidor: " . $e->getMessage()
    ]);
} finally {
    if (isset($conexionPrincipal)) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}