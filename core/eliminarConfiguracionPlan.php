<?php
//eliminarConfiguracionPlanAjax.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$plan_id = $insMainModel->cleanString($_POST['plan_id']);
$clave = $insMainModel->cleanString($_POST['clave']);

if(empty($plan_id) || empty($clave)) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit();
}

try {
    // 1. Procesar en la base de datos principal
    $conexionPrincipal = $insMainModel->connection();
    $conexionPrincipal->autocommit(false); // Iniciar transacción
    
    // Obtener el plan actual
    $consulta = $conexionPrincipal->query("SELECT configuraciones FROM planes WHERE planes_id = '$plan_id'");
    
    if($consulta->num_rows == 0) {
        $conexionPrincipal->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Plan no encontrado'
        ]);
        exit();
    }
    
    $plan = $consulta->fetch_assoc();
    $configuraciones = json_decode($plan['configuraciones'], true) ?: [];
    
    // Verificar si la configuración existe
    if(!array_key_exists($clave, $configuraciones)) {
        $conexionPrincipal->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'La configuración no existe en este plan'
        ]);
        exit();
    }
    
    // Eliminar la configuración
    unset($configuraciones[$clave]);
    $nuevasConfigs = empty($configuraciones) ? null : json_encode($configuraciones);
    
    // Actualizar en la base principal
    $actualizar = $conexionPrincipal->query("UPDATE planes SET configuraciones = ".($nuevasConfigs ? "'$nuevasConfigs'" : "NULL")." WHERE planes_id = '$plan_id'");
    
    if(!$actualizar) {
        $conexionPrincipal->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el plan en la base principal'
        ]);
        exit();
    }
    
    // 2. Actualizar en todas las bases de datos de clientes
    $clientes = $insMainModel->ejecutar_consulta("SELECT db FROM server_customers WHERE estado = 1 AND db != ''");
    $erroresClientes = [];
    
    foreach ($clientes as $cliente) {
        $dbName = $cliente['db'];
        
        // Verificar si la base de datos existe
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
                    // Verificar si la tabla planes existe
                    $tableExists = $connCliente->query("SHOW TABLES LIKE 'planes'");
                    
                    if ($tableExists->num_rows > 0) {
                        // Verificar si el plan existe en el cliente
                        $planExists = $connCliente->query("SELECT 1 FROM planes WHERE planes_id = '$plan_id'");
                        
                        if ($planExists->num_rows > 0) {
                            // Actualizar en la base de datos del cliente
                            $updateCliente = $connCliente->query("UPDATE planes SET configuraciones = ".($nuevasConfigs ? "'$nuevasConfigs'" : "NULL")." WHERE planes_id = '$plan_id'");
                            
                            if (!$updateCliente) {
                                $erroresClientes[] = "Error al actualizar en $dbName: " . $connCliente->error;
                            }
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
    
    // Si hay errores en clientes pero la operación principal fue exitosa
    if (!empty($erroresClientes)) {
        error_log("Errores al actualizar configuraciones en clientes: " . implode(", ", $erroresClientes));
        // Puedes decidir si hacer rollback o commit aquí según tu política de errores
    }
    
    // Confirmar transacción principal
    $conexionPrincipal->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración eliminada correctamente',
        'configuraciones' => $configuraciones,
        'warnings' => $erroresClientes
    ]);
    
} catch(Exception $e) {
    if(isset($conexionPrincipal)) {
        $conexionPrincipal->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
} finally {
    if(isset($conexionPrincipal)) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}