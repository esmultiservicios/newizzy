<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

if (!isset($_POST['id']) || !isset($_POST['tipo']) || !isset($_POST['nombre'])) {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
    exit();
}

$insMainModel = new mainModel();

try {
    $id = $insMainModel->cleanString($_POST['id']);
    $tipo = $insMainModel->cleanStringConverterCase($_POST['tipo']);
    $nombre = $insMainModel->cleanStringConverterCase($_POST['nombre']);
    $descripcion = $insMainModel->cleanStringConverterCase($_POST['descripcion']);
    $icono = isset($_POST['icono']) ? $insMainModel->cleanStringConverterCase($_POST['icono']) : null;
    $orden = isset($_POST['orden']) && is_numeric($_POST['orden']) ? (int)$_POST['orden'] : 0;
    $dependencia = isset($_POST['dependencia']) ? (int)$_POST['dependencia'] : null;
    $visible = isset($_POST['visible']) ? $_POST['visible'] : 1;
    $oldName = null;

    // Obtener conexión principal
    $conexionPrincipal = $insMainModel->connection();
    $conexionPrincipal->autocommit(false);

    // Validar existencia en la base principal (excepto para el registro actual)
    if ($tipo == 'menu') {
        $check = $conexionPrincipal->query("SELECT menu_id, name FROM menu WHERE name = '$nombre' AND menu_id != '$id'");
        $oldNameQuery = $conexionPrincipal->query("SELECT name FROM menu WHERE menu_id = '$id'");
        $oldName = $oldNameQuery->fetch_assoc()['name'];
    } elseif ($tipo == 'submenu') {
        $check = $conexionPrincipal->query("SELECT submenu_id FROM submenu WHERE name = '$nombre' AND menu_id = '$dependencia' AND submenu_id != '$id'");
    } else {
        $check = $conexionPrincipal->query("SELECT submenu1_id FROM submenu1 WHERE name = '$nombre' AND submenu_id = '$dependencia' AND submenu1_id != '$id'");
    }

    if ($check->num_rows > 0) {
        $conexionPrincipal->rollback();
        echo json_encode([
            "type" => "warning",
            "title" => "Advertencia",
            "message" => "Ya existe un registro con este nombre y dependencia"
        ]);
        exit();
    }

    // Actualizar en la base principal
    if ($tipo == 'menu') {
        $sql = "UPDATE menu SET 
                name = '$nombre', 
                descripcion = '$descripcion', 
                icon = " . ($icono ? "'$icono'" : "NULL") . ", 
                orden = $orden,
                visible = $visible
                WHERE menu_id = '$id'";
    } elseif ($tipo == 'submenu') {
        $sql = "UPDATE submenu SET 
                menu_id = '$dependencia', 
                name = '$nombre', 
                descripcion = '$descripcion',
                icon = " . ($icono ? "'$icono'" : "NULL") . ", 
                orden = $orden,
                visible = $visible
                WHERE submenu_id = '$id'";
    } else {
        $sql = "UPDATE submenu1 SET 
                submenu_id = '$dependencia', 
                name = '$nombre', 
                descripcion = '$descripcion',
                icon = " . ($icono ? "'$icono'" : "NULL") . ", 
                orden = $orden,
                visible = $visible
                WHERE submenu1_id = '$id'";
    }

    if (!$conexionPrincipal->query($sql)) {
        $conexionPrincipal->rollback();
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo actualizar el registro en la base principal"
        ]);
        exit();
    }

    // Actualizar en todas las bases de datos de clientes
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
                    $connCliente->autocommit(false);
                    
                    // Definir nombres de tabla y campo ID según el tipo
                    $tableName = $tipo == 'menu' ? 'menu' : ($tipo == 'submenu' ? 'submenu' : 'submenu1');
                    $idField = $tipo == 'menu' ? 'menu_id' : ($tipo == 'submenu' ? 'submenu_id' : 'submenu1_id');
                    
                    // Verificar si la tabla existe
                    $tableExists = $connCliente->query("SHOW TABLES LIKE '$tableName'");
                    
                    if ($tableExists->num_rows > 0) {
                        // Verificar si las columnas necesarias existen
                        $columnsNeeded = ['name', 'descripcion', 'icon', 'orden', 'visible'];
                        if ($tipo != 'menu') {
                            $columnsNeeded[] = $tipo == 'submenu' ? 'menu_id' : 'submenu_id';
                        }
                        
                        $columnsExist = true;
                        $columnsResult = $connCliente->query("SHOW COLUMNS FROM $tableName");
                        $existingColumns = [];
                        while ($column = $columnsResult->fetch_assoc()) {
                            $existingColumns[] = $column['Field'];
                        }
                        
                        foreach ($columnsNeeded as $column) {
                            if (!in_array($column, $existingColumns)) {
                                $columnsExist = false;
                                break;
                            }
                        }
                        
                        if ($columnsExist) {
                            // Verificar si el registro existe en el cliente
                            $recordExists = $connCliente->query("SELECT 1 FROM $tableName WHERE $idField = '$id'");
                            
                            if ($recordExists->num_rows > 0) {
                                // Actualizar en la base del cliente
                                if ($tipo == 'menu') {
                                    $sqlCliente = "UPDATE menu SET 
                                                name = '$nombre', 
                                                descripcion = '$descripcion', 
                                                icon = " . ($icono ? "'$icono'" : "NULL") . ", 
                                                orden = $orden,
                                                visible = $visible
                                                WHERE menu_id = '$id'";
                                } elseif ($tipo == 'submenu') {
                                    $sqlCliente = "UPDATE submenu SET 
                                                menu_id = '$dependencia', 
                                                name = '$nombre', 
                                                descripcion = '$descripcion',
                                                icon = " . ($icono ? "'$icono'" : "NULL") . ", 
                                                orden = $orden,
                                                visible = $visible
                                                WHERE submenu_id = '$id'";
                                } else {
                                    $sqlCliente = "UPDATE submenu1 SET 
                                                submenu_id = '$dependencia', 
                                                name = '$nombre', 
                                                descripcion = '$descripcion',
                                                icon = " . ($icono ? "'$icono'" : "NULL") . ", 
                                                orden = $orden,
                                                visible = $visible
                                                WHERE submenu1_id = '$id'";
                                }
                                
                                if (!$connCliente->query($sqlCliente)) {
                                    throw new Exception("Error al actualizar en $dbName: " . $connCliente->error);
                                }
                            } else {
                                // Insertar si no existe
                                $fecha_registro = date("Y-m-d H:i:s");
                                if ($tipo == 'menu') {
                                    $sqlCliente = "INSERT INTO menu (menu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                                                 VALUES ('$id', '$nombre', '$descripcion', " . ($icono ? "'$icono'" : "NULL") . ", '$orden', '$fecha_registro', '$visible')";
                                } elseif ($tipo == 'submenu') {
                                    $sqlCliente = "INSERT INTO submenu (submenu_id, menu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                                                 VALUES ('$id', '$dependencia', '$nombre', '$descripcion', " . ($icono ? "'$icono'" : "NULL") . ", '$orden', '$fecha_registro', '$visible')";
                                } else {
                                    $sqlCliente = "INSERT INTO submenu1 (submenu1_id, submenu_id, name, descripcion, icon, orden, fecha_registro, visible) 
                                                 VALUES ('$id', '$dependencia', '$nombre', '$descripcion', " . ($icono ? "'$icono'" : "NULL") . ", '$orden', '$fecha_registro', '$visible')";
                                }
                                
                                if (!$connCliente->query($sqlCliente)) {
                                    throw new Exception("Error al insertar en $dbName: " . $connCliente->error);
                                }
                            }
                        } else {
                            throw new Exception("La tabla $tableName en $dbName no tiene todas las columnas necesarias");
                        }
                    }
                    
                    $connCliente->commit();
                } catch (Exception $e) {
                    $connCliente->rollback();
                    $erroresClientes[] = $e->getMessage();
                } finally {
                    $connCliente->autocommit(true);
                    $connCliente->close();
                }
            }
        }
    }

    // Actualizar lista blanca si es un menú principal y el nombre cambió
    if ($tipo == 'menu' && $oldName && $oldName != $nombre) {
        $nombre_config = 'configuracion_principal';
        
        try {
            // 1. Primero agregamos el nuevo nombre (maneja duplicados internamente)
            $resultadoAgregar = $insMainModel->guardar_o_actualizar_modulo_lista_blanca($nombre_config, $nombre);
            
            if (!$resultadoAgregar) {
                throw new Exception("Error al agregar el nuevo nombre a la lista blanca");
            }
            
            // 2. Luego eliminamos el nombre antiguo
            $resultadoEliminar = $insMainModel->eliminar_modulo_lista_blanca($nombre_config, $oldName);
            
            if (!$resultadoEliminar) {
                error_log("No se encontró el nombre antiguo en la lista blanca para eliminar: $oldName");
            }
            
        } catch (Exception $e) {
            $conexionPrincipal->rollback();
            echo json_encode([
                "type" => "error",
                "title" => "Error",
                "message" => "Error al actualizar lista blanca: " . $e->getMessage()
            ]);
            exit();
        }
    }

    // Confirmar transacción principal
    $conexionPrincipal->commit();

    $response = [
        "type" => "success",
        "title" => "Éxito",
        "message" => "Registro actualizado correctamente"
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