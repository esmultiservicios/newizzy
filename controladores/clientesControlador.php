<?php
if($peticionAjax){
    require_once "../modelos/clientesModelo.php";
    require_once "../core/sendEmail.php";
    require_once "../core/emailTemplates.php";
    require_once "../core/cPanelAPI.php";
    require_once "../core/DatabaseSetup.php";    
}else{
    require_once "./modelos/clientesModelo.php";
    require_once "./core/sendEmail.php";
    require_once "./core/emailTemplates.php";
    require_once "./core/cPanelAPI.php";
    require_once "../core/DatabaseSetup.php";    
}

class clientesControlador extends clientesModelo {
    
    /* Método para agregar clientes normales */
    public function agregar_clientes_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
            
        $datos = [
            "nombre" => mainModel::cleanString($_POST['nombre_clientes']),
            "rtn" => mainModel::cleanString($_POST['identidad_clientes']),
            "fecha" => mainModel::cleanString($_POST['fecha_clientes']),
            "departamento_id" => isset($_POST['departamento_cliente']) ? intval($_POST['departamento_cliente']) : 0,
            "municipio_id" => isset($_POST['municipio_cliente']) ? intval($_POST['municipio_cliente']) : 0,
            "localidad" => mainModel::cleanString($_POST['dirección_clientes']),
            "telefono" => mainModel::cleanString($_POST['telefono_clientes']),
            "correo" => mainModel::cleanStringStrtolower($_POST['correo_clientes']),
            "estado_clientes" => 1,
            "colaborador_id" => $_SESSION['colaborador_id_sd'],
            "fecha_registro" => date("Y-m-d H:i:s"),
            "empresa" => ""
        ];
        
        if(!clientesModelo::agregar_clientes_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo registrar el cliente",
                "type" => "error"
            ]);
        }
        
        // Registrar en historial
        mainModel::guardarHistorial([
            "modulo" => 'Clientes',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Registro",
            "observacion" => "Se registró el cliente {$datos['nombre']} con RTN {$datos['rtn']}",
            "fecha_registro" => date("Y-m-d H:i:s")
        ]);
        
        return mainModel::showNotification([
            "title" => "Registro exitoso",
            "text" => "Cliente registrado correctamente",
            "type" => "success",
            "form" => "formClientes",
            "funcion" => "listar_clientes();getDepartamentoClientes();getMunicipiosClientes(0);listar_clientes_factura_buscar();listar_clientes_cotizacion_buscar();listar_colaboradores_buscar_compras();"
        ]);
    }

    /* Método para registrar clientes autónomos */
    public function registrar_cliente_autonomo_controlador() {
        // Validar campos requeridos
        $required = ['user_empresa', 'user_name', 'user_telefono', 'email'];
        foreach ($required as $field) {
            if (!isset($_POST[$field])) {
                $this->responderError('Campos faltantes', 'Faltan campos obligatorios', 400);
            }
        }        

        // Limpiar y validar datos
        $datos = [
            'empresa' => mainModel::cleanString($_POST['user_empresa'] ?? ''),
            'nombre' => mainModel::cleanString($_POST['user_name'] ?? ''),
            'telefono' => mainModel::cleanString($_POST['user_telefono'] ?? ''),
            'correo' => mainModel::cleanStringStrtolower($_POST['email'] ?? ''),
            'password' => mainModel::cleanString(empty($_POST['user_pass']) ? mainModel::generar_password_complejo() : $_POST['user_pass']),
            'sistema_id' => mainModel::cleanStringStrtolower($_POST['sistema_id'] ?? 1),
            'planes_id' => mainModel::cleanString($_POST['planes_id'] ?? ''),
            'eslogan' => mainModel::cleanStringStrtolower($_POST['eslogan'] ?? ''),
            'otra_informacion' => mainModel::cleanString($_POST['otra_informacion'] ?? ''),
            'ubicacion' => mainModel::cleanString($_POST['ubicacion'] ?? ''),
            'celular' => mainModel::cleanString($_POST['celular'] ?? ''),  
            'validar' => mainModel::cleanString($_POST['validar'] ?? 0), 
            'rtn' => mainModel::cleanString($_POST['rtn'] ?? ''), 
            'clientes_id' => mainModel::cleanString($_POST['clientes_id'] ?? 0),  
        ];
                
        $empresa_id = 1; 
        $clientes_id = $datos['clientes_id'];  

        // Validaciones básicas
        if (empty($datos['nombre']) || empty($datos['empresa']) || empty($datos['telefono']) || 
            empty($datos['correo']) || empty($datos['password'])) {
            $this->responderError('Campos vacíos', 'Todos los campos son obligatorios', 400);
        }
        
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->responderError('Correo inválido', 'El formato del correo no es válido', 400);
        }        

        // Registrar cliente principal
        if($clientes_id === "0") {
            if ($this->correoYaRegistrado($datos['correo'])) {
                $this->responderError('Correo existente', 'Este correo ya está registrado', 400);
            }

            $clientes_id = $this->registrarCliente(
                $datos['nombre'], 
                $datos['telefono'], 
                $datos['correo'], 
                $datos['empresa'], 
                $datos['rtn']);
            if (!$clientes_id) {
                $this->responderError('Error', 'No se pudo registrar el cliente principal', 500);
            }
        }

        //CONSULTAMOS EL NOMBRE DEL SISTEMA
        $sistema_nombre = $this->getNombreSistema(
            $datos['sistema_id']
        );

        // Generar nombres para la base de datos
        $dbNames = mainModel::generateDatabaseName(
            $datos['empresa'], 
            $sistema_nombre
        );

        $codigo_cliente = $this->generarCodigoCliente(
            $clientes_id
        );

        $dataBaseCliente = $dbNames['prefixed'];
        
        // Configurar base de datos en cPanel
        try {
            $cpanel = new cPanelAPI();

            $dbSetup = $cpanel->setupCompleteDatabase([
                'db_name' => $dataBaseCliente,
                'db_user' => CPANEL_DB_USERNAME,
                'db_password' => CPANEL_DB_PASSWORD
            ]);
            
            if (!$dbSetup['success']) {
                return [
                    'estado' => false,
                    'type' => 'error',
                    'title' => 'Error en configuración',
                    'mensaje' => $dbSetup['message'] ?? "Error al configurar la base de datos"
                ];
            }
            
            // Registrar en server_customers con el nombre real de la DB
            $server_customers_id = $this->registrarServerCustomer(
                $clientes_id,
                $empresa_id,
                $codigo_cliente,
                $dbSetup['database']['db_name'],
                $datos['validar'],
                $datos['planes_id'],
                $datos['sistema_id']
            );
            
            if (!$server_customers_id) {
                $this->responderError(
                    'Error en el registro', 
                    'No se pudo registrar en server_customers', 
                    500);
                return; // Asegúrate de salir después de responder
            }
            
            // Registrar usuario
            $usuario = $this->registrarUsuario(
                $clientes_id,
                $server_customers_id,
                $datos['nombre'],
                $datos['correo'],
                $datos['password']
            );
            
            if (!$usuario) {
                $this->responderError(
                    'Error en registro', 
                    "Error al registrar en el usuario", 
                    500
                );
                return;
            }
            
            // Enviar correo de bienvenida
            $this->enviarCorreoBienvenida([
                'nombre' => $datos['nombre'],
                'username' => $usuario['username'],
                'email' => $datos['correo'],
                'empresa' => $datos['empresa'],
                'nombre_db' => $dataBaseCliente,
                'password' => $datos['password']
            ], 1);                                        

            // Respuesta exitosa consolidada
            $this->responderExito([
                'estado' => true,
                'cliente' => [
                    'id' => $clientes_id,
                    'nombre' => $datos['nombre'],
                    'email' => $datos['correo']
                ],
                'servidor' => [
                    'server_customers_id' => $server_customers_id,
                    'codigo_cliente' => $codigo_cliente,
                    'nombre_db' => $dbSetup['database']['db_name']
                ],
                'usuario' => [
                    'id' => $usuario['users_id'],
                    'username' => $usuario['username']
                ]
            ], $dbNames['prefixed']);
            
        } catch (Exception $e) {
            error_log("Error en registro autónomo: " . $e->getMessage());
            
            // Limpieza en caso de error
            if (isset($server_customers_id)) {
                mainModel::ejecutar_consulta_simple("DELETE FROM server_customers WHERE server_customers_id = $server_customers_id");
            }
            
            $this->responderError('Error en el registro', $e->getMessage(), 500);
        }
    }
    
    /* Métodos para editar clientes */
    public function edit_clientes_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
        
        $datos = [
            "clientes_id" => $_POST['clientes_id'],
            "nombre" => mainModel::cleanStringConverterCase($_POST['nombre_clientes']),
            "rtn" => mainModel::cleanString($_POST['identidad_clientes']),
            "departamento_id" => isset($_POST['departamento_cliente']) ? intval($_POST['departamento_cliente']) : 0,
            "municipio_id" => isset($_POST['municipio_cliente']) ? intval($_POST['municipio_cliente']) : 0,
            "localidad" => mainModel::cleanString($_POST['dirección_clientes']),
            "telefono" => mainModel::cleanString($_POST['telefono_clientes']),
            "correo" => mainModel::cleanStringStrtolower($_POST['correo_clientes']),
            "estado" => isset($_POST['clientes_activo']) ? $_POST['clientes_activo'] : 2
        ];
                    
        if(!clientesModelo::edit_clientes_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo actualizar el cliente",
                "type" => "error"
            ]);
        }
        
        // Registrar en historial
        mainModel::guardarHistorial([
            "modulo" => 'Clientes',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Edición",
            "observacion" => "Se editó el cliente {$datos['nombre']} con RTN {$datos['rtn']}",
            "fecha_registro" => date("Y-m-d H:i:s")
        ]);
        
        return mainModel::showNotification([
            "title" => "Actualización exitosa",
            "text" => "Cliente actualizado correctamente",
            "type" => "success",
            "form" => "formClientes",
            "funcion" => "listar_clientes();getDepartamentoClientes();getMunicipiosClientes(0);"
        ]);
    }
    
    /* Método para eliminar clientes */
    public function delete_clientes_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
        
        $clientes_id = $_POST['clientes_id'];
        $cliente = mainModel::consultar_tabla('clientes', ['nombre', 'rtn'], "clientes_id = {$clientes_id}");
        
        if (empty($cliente)) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Cliente no encontrado"
            ]);
            exit();
        }
        
        $nombre = $cliente[0]['nombre'] ?? '';
        $rtn = $cliente[0]['rtn'] ?? '';
        $empresa_id = 1;
                        
        if(clientesModelo::valid_clientes_facturas_modelo($clientes_id)->num_rows > 0){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "No se puede eliminar",
                "message" => "El cliente {$nombre} tiene facturas asociadas"
            ]);
            exit();                
        }
        
        if(!clientesModelo::delete_clientes_modelo($clientes_id)){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el cliente {$nombre}"
            ]);
            exit();
        }
        
        // Registrar en historial
        mainModel::guardarHistorial([
            "modulo" => 'Clientes',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Eliminar",
            "observacion" => "Se eliminó el cliente {$nombre} con RTN {$rtn}",
            "fecha_registro" => date("Y-m-d H:i:s")
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "title" => "Eliminado",
            "message" => "Cliente {$nombre} eliminado correctamente"
        ]);
        exit();
    }
    
    /* Métodos auxiliares */
    private function responderError($titulo, $mensaje, $codigo = 400) {
        http_response_code($codigo);
        echo json_encode([
            'estado' => false,
            'type' => 'error',
            'title' => $titulo,
            'mensaje' => $mensaje
        ]);
        exit;
    }
    
    private function responderExito($datos, $nombre_db) {
        echo json_encode([
            'estado' => true,
            'type' => 'success',
            'title' => 'Registro exitoso',
            'mensaje' => "Registro completado. Base de datos {$nombre_db} configurada correctamente.",
            'datos' => $datos
        ]);
        exit;
    }
    
    private function correoYaRegistrado($correo) {
        $check_email = mainModel::ejecutar_consulta_simple("SELECT clientes_id FROM clientes WHERE correo = '$correo'");
        $check_email_user = mainModel::ejecutar_consulta_simple("SELECT users_id FROM users WHERE email = '$correo'");
        return ($check_email->num_rows > 0 || $check_email_user->num_rows > 0);
    }
    
    private function registrarCliente($nombre, $telefono, $correo, $empresa, $rtn) {
        $datos = [
            "nombre" => $nombre,
            "rtn" => $rtn,
            "fecha" => date("Y-m-d"),
            "departamento_id" => 0,
            "municipio_id" => 0,
            "localidad" => "",
            "telefono" => $telefono,
            "correo" => $correo,
            "estado_clientes" => 1,
            "colaborador_id" => 1,
            "fecha_registro" => date("Y-m-d H:i:s"),
            "empresa" => $empresa,
        ];
        return clientesModelo::agregar_clientes_modelo($datos);
    }
    
    private function generarCodigoCliente($clientes_id) {
        $codigo = mainModel::generarCodigoUnico($clientes_id);
        $existe = mainModel::ejecutar_consulta_simple("SELECT COUNT(*) as total FROM server_customers WHERE codigo_cliente = '$codigo'");
        return ($existe->fetch_assoc()['total'] > 0) ? (int)(date('Ymd') . substr($clientes_id, -4)) : $codigo;
    }
    
    private function registrarServerCustomer($clientes_id, $empresa_id, $codigo_cliente, $nombre_db, $validar, $planes_id, $sistema_id) {
        $conexion = mainModel::connection();
    
        try {
            // Desactivar autocommit para la transacción
            $conexion->autocommit(false);
    
            // Obtener el próximo ID disponible
            $resultado = $conexion->query("SELECT MAX(server_customers_id) as ultimo_id FROM server_customers");
            $server_customers_id = ($resultado->fetch_assoc()['ultimo_id'] ?? 0) + 1;
    
            // Sentencia preparada para seguridad
            $stmt = $conexion->prepare(
                "INSERT INTO server_customers 
                (server_customers_id, clientes_id, codigo_cliente, db, planes_id, sistema_id, validar, estado, db_imported) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)"
            );
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conexion->error);
            }
    
            $stmt->bind_param("iissiii", $server_customers_id, $clientes_id, $codigo_cliente, $nombre_db, $planes_id, $sistema_id, $validar);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
    
            // Encolar trabajo de importación
            $jobData = [
                'db_name' => $nombre_db,
                'client_id' => $clientes_id,
                'server_customers_id' => $server_customers_id,
                'sql_file' => $_SERVER['DOCUMENT_ROOT'].'/plantilla/plantilla_izzy.sql'
            ];
            
            $stmtJob = $conexion->prepare(
                "INSERT INTO jobs_queue (job_type, data, status, created_at) 
                VALUES ('db_import', ?, 'pending', NOW())"
            );
            $jsonData = json_encode($jobData);
            $stmtJob->bind_param("s", $jsonData);
            $stmtJob->execute();
            $stmtJob->close();
    
            // Confirmar la transacción
            $conexion->commit();
    
            // Cerrar la declaración preparada
            $stmt->close();
    
            return $server_customers_id;
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conexion->rollback();
            error_log("Error en registrarServerCustomer: " . $e->getMessage());
            throw $e;
        } finally {
            // Reactivar autocommit
            $conexion->autocommit(true);
        }
    }
    
    private function registrarUsuario($clientes_id, $server_customers_id, $nombre, $correo, $password) {
        $conexion = mainModel::connection();
    
        try {
            // Desactivar autocommit para la transacción
            $conexion->autocommit(false);
    
            // Obtener el próximo ID disponible
            $resultado = $conexion->query("SELECT MAX(users_id) as ultimo_id FROM users");
            $users_id = ($resultado->fetch_assoc()['ultimo_id'] ?? 0) + 1;
    
            // Generar username único y hash de contraseña
            $username = mainModel::generarUsernameUnico($nombre);
            $password_hash = mainModel::encryption($password);
    
            // Sentencia preparada para seguridad
            $stmt = $conexion->prepare(
                "INSERT INTO users 
                (users_id, colaboradores_id, privilegio_id, username, password, email, tipo_user_id, estado, fecha_registro, empresa_id, server_customers_id) 
                VALUES (?, 1, 2, ?, ?, ?, 1, 1, NOW(), ?, ?)"
            );
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conexion->error);
            }
    
            $stmt->bind_param("isssii", $users_id, $username, $password_hash, $correo, $clientes_id, $server_customers_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
    
            // Confirmar la transacción
            $conexion->commit();
    
            // Cerrar la declaración preparada
            $stmt->close();
    
            return [
                'users_id' => $users_id,
                'username' => $username
            ];
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conexion->rollback();
            error_log("Error en registrarUsuario: " . $e->getMessage());
            throw $e;
        } finally {
            // Reactivar autocommit
            $conexion->autocommit(true);
        }
    }
    
    private function enviarCorreoBienvenida($datosUsuario, $empresa_id) {
        $sendEmail = new sendEmail();
        $emailTemplates = new emailTemplates();
        
        $datosEmpresa = $sendEmail->obtenerDatosEmpresa($empresa_id);
        $asunto = "Bienvenido a " . $datosEmpresa['nombre'];
        $mensaje = $emailTemplates->plantillaBienvenida($datosUsuario, $datosEmpresa);
        
        try {
            $sendEmail->enviarCorreo(
                [$datosUsuario['email'] => $datosUsuario['nombre']],
                [$datosEmpresa['correo'] => $datosEmpresa['nombre']],
                $asunto,
                $mensaje,
                1,
                $empresa_id
            );
        } catch (Exception $e) {
            error_log("Error al enviar correo: " . $e->getMessage());
        }
    }

    private function getNombreSistema($sistema_id) {
        // Obtener la conexión a la base de datos
        $conexion = mainModel::connection();
    
        try {
            // Consulta para obtener el nombre del sistema basado en el sistema_id
            $stmt = $conexion->prepare("SELECT LOWER(nombre) AS nombre FROM sistema WHERE sistema_id = ? AND estado = 1");
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
    
            // Vincular el parámetro
            $stmt->bind_param("i", $sistema_id);
    
            // Ejecutar la consulta
            $stmt->execute();
    
            // Obtener el resultado
            $result = $stmt->get_result();
    
            // Verificar si se encontró un registro
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['nombre']; // Devuelve el nombre en minúsculas
            } else {
                throw new Exception("No se encontró un sistema con el ID proporcionado o está inactivo.");
            }
        } catch (Exception $e) {
            // Registrar el error y lanzar una excepción
            error_log("Error en getNombreSistema: " . $e->getMessage());
            throw $e;
        } finally {
            // Cerrar la declaración preparada
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}