<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

if (!isset($_POST['tipo']) || !isset($_POST['nombre'])) {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
    exit();
}

$insMainModel = new mainModel();

try {
    // Procesar datos
    $tipo = $insMainModel->cleanStringConverterCase($_POST['tipo']);
    $nombre = $insMainModel->cleanStringConverterCase($_POST['nombre']);
    $descripcion = $insMainModel->cleanStringConverterCase($_POST['descripcion']);
    $icono = $insMainModel->cleanStringConverterCase($_POST['icono']);
    $orden = $insMainModel->cleanStringConverterCase($_POST['orden']);
    $dependencia = isset($_POST['dependencia']) ? $_POST['dependencia'] : null;
    $visible = isset($_POST['visible']) ? $_POST['visible'] : 1;
    $fecha_registro = date("Y-m-d H:i:s");

    // Obtener conexión principal
    $conexionPrincipal = $insMainModel->connection();
    $conexionPrincipal->autocommit(false); // Iniciar transacción

    // Consultar el último ID registrado
    if ($tipo == 'menu') {
        $lastIdQuery = $conexionPrincipal->query("SELECT MAX(menu_id) AS last_id FROM menu");
    } elseif ($tipo == 'submenu') {
        $lastIdQuery = $conexionPrincipal->query("SELECT MAX(submenu_id) AS last_id FROM submenu");
    } else {
        $lastIdQuery = $conexionPrincipal->query("SELECT MAX(submenu1_id) AS last_id FROM submenu1");
    }

    $lastIdRow = $lastIdQuery->fetch_assoc();
    $lastId = $lastIdRow['last_id'];
    $nextId = $lastId + 1;

    // Validar existencia en la base principal
    if ($tipo == 'menu') {
        $check = $conexionPrincipal->query("SELECT menu_id FROM menu WHERE name = '$nombre'");
    } elseif ($tipo == 'submenu') {
        $check = $conexionPrincipal->query("SELECT submenu_id FROM submenu WHERE name = '$nombre' AND menu_id = '$dependencia'");
    } else {
        $check = $conexionPrincipal->query("SELECT submenu1_id FROM submenu1 WHERE name = '$nombre' AND submenu_id = '$dependencia'");
    }

    if ($check->num_rows > 0) {
        $conexionPrincipal->rollback();
        echo json_encode([
            "type" => "warning",
            "title" => "Advertencia",
            "message" => "Este registro ya existe en el sistema"
        ]);
        exit();
    }

    // Insertar en la base principal
    if ($tipo == 'menu') {
        $sql = "INSERT INTO menu (menu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                VALUES ('$nextId', '$nombre', '$descripcion', '$icono', '$orden', '$fecha_registro', '$visible')";
    } elseif ($tipo == 'submenu') {
        $sql = "INSERT INTO submenu (submenu_id, menu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                VALUES ('$nextId', '$dependencia', '$nombre', '$descripcion', '$icono', '$orden', '$fecha_registro', '$visible')";
    } else {
        $sql = "INSERT INTO submenu1 (submenu1_id, submenu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                VALUES ('$nextId', '$dependencia', '$nombre', '$descripcion', '$icono', '$orden', '$fecha_registro', '$visible')";
    }

    if (!$conexionPrincipal->query($sql)) {
        $conexionPrincipal->rollback();
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo completar la operación en la base principal"
        ]);
        exit();
    }

    // Insertar en todas las bases de datos de clientes
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
                        // Insertar en la base del cliente
                        if ($tipo == 'menu') {
                            $sqlCliente = "INSERT INTO menu (menu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                                         VALUES ('$nextId', '$nombre', '$descripcion', '$icono', '$orden', '$fecha_registro', '$visible')";
                        } elseif ($tipo == 'submenu') {
                            $sqlCliente = "INSERT INTO submenu (submenu_id, menu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                                         VALUES ('$nextId', '$dependencia', '$nombre', '$descripcion', '$icono', '$orden', '$fecha_registro', '$visible')";
                        } else {
                            $sqlCliente = "INSERT INTO submenu1 (submenu1_id, submenu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                                         VALUES ('$nextId', '$dependencia', '$nombre', '$descripcion', '$icono', '$orden', '$fecha_registro', '$visible')";
                        }
                        
                        if (!$connCliente->query($sqlCliente)) {
                            $erroresClientes[] = "Error al insertar en $dbName: " . $connCliente->error;
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

    // Actualizar lista blanca si es un menú principal
    if ($tipo == 'menu') {
        $nombre_config = 'configuracion_principal';
        $insMainModel->guardar_o_actualizar_modulo_lista_blanca($nombre_config, $nombre);
    }

    // Confirmar transacción principal
    $conexionPrincipal->commit();

    $response = [
        "type" => "success",
        "title" => "Éxito",
        "message" => "Registro almacenado correctamente",
        "last_id" => $nextId
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