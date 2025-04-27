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
			// Validar sesión primero
			$validacion = mainModel::validarSesion();
			if($validacion['error']) {
				return mainModel::showNotification([
					"title" => "Error de sesión",
					"text" => $validacion['mensaje'],
					"type" => "error",
					"funcion" => "window.location.href = '".$validacion['redireccion']."'"
				]);
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
        
        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();
        
        // Solo evaluar si existe configuración de plan
        if (!empty($planConfig)) {
            $limiteClientes = (int)($planConfig['clientes'] ?? 0);
            
            // Caso 1: Límite es 0 (bloquear)
            if ($limiteClientes === 0) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan actual no permite registrar clientes."
                ]);
            }
            
            // Caso 2: Si tiene límite > 0, validar disponibilidad
            $totalRegistrados = (int)clientesModelo::getTotalClientesRegistrados();
            
            if ($totalRegistrados >= $limiteClientes) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Límite de clientes alcanzado (Máximo: $limiteClientes). Actualiza tu plan."
                ]);
            }
        }

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
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Cliente registrado correctamente",           
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
        
        // Ejecutamos el API de cPanel para poder crear la base de datos
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
            
            // Registrar Colaborador
            $id_colaborador = $this->registrarColaborador(
                $datos['nombre'], 
                $datos['telefono'], 
                $datos['rtn'], 
                1
            );

            if (!$id_colaborador) {
                $this->responderError(
                    'Error en registro', 
                    "Error al registrar en el colaborador", 
                    500
                );
                return;
            }            

            // Registrar usuario
            $usuario = $this->registrarUsuario(
                $clientes_id,
                $server_customers_id,
                $datos['nombre'],
                $datos['correo'],
                $datos['password'],
                $id_colaborador
            );
            
            if (!$usuario) {
                $this->responderError(
                    'Error en registro', 
                    "Error al registrar en el usuario", 
                    500
                );
                return;
            }
            
            // Establecemos los permisos del usuario a la Base de Datos creada
            $dbSetup = new DatabaseSetup(
                SERVER,
                MYSQL_USER,
                MYSQL_PASS,
                $dataBaseCliente
            );
            
            $grantResult = $dbSetup->grantPrivilegesToExistingUser(
                USER,
                $dataBaseCliente,
                ['ALL PRIVILEGES']
            );
            
            if ($grantResult !== true) {
                $this->responderError(
                    'Error en permisos', 
                    "No se pudieron otorgar los privilegios: " . ($grantResult['error'] ?? 'Error desconocido'),
                    500
                );
                return;
            }

            //Enviar correo electronico - Procedimiento Almacenado

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
                    'nombre_db' => $dataBaseCliente
                ],
                'usuario' => [
                    'id' => $usuario['users_id'],
                    'username' => $usuario['username']
                ]
            ], $dbNames['prefixed']);

            //IMPORTAR BASE DE DATOS A cPanel - Procedimiento Almacenado            
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
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
        
        $estado = isset($_POST['clientes_activo']) && $_POST['clientes_activo'] == 'on' ? 1 : 0;

        $datos = [
            "clientes_id" => $_POST['clientes_id'],
            "nombre" => mainModel::cleanStringConverterCase($_POST['nombre_clientes']),
            "rtn" => mainModel::cleanString($_POST['identidad_clientes']),
            "departamento_id" => isset($_POST['departamento_cliente']) ? intval($_POST['departamento_cliente']) : 0,
            "municipio_id" => isset($_POST['municipio_cliente']) ? intval($_POST['municipio_cliente']) : 0,
            "localidad" => mainModel::cleanString($_POST['dirección_clientes']),
            "telefono" => mainModel::cleanString($_POST['telefono_clientes']),
            "correo" => mainModel::cleanStringStrtolower($_POST['correo_clientes']),
            "estado" => $estado
        ];
                    
        if(!clientesModelo::edit_clientes_modelo($datos)){
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo actualizar el cliente",                
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
            "type" => "success",
            "title" => "Actualización exitosa",
            "text" => "Cliente actualizado correctamente",
            "funcion" => "listar_clientes();"
        ]);
    }
    
    /* Método para eliminar clientes */
    public function delete_clientes_controlador(){
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
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

    private function registrarColaborador($nombre, $telefono, $identidad, $empresa_id) {
        $datos = [
            "nombre" => $nombre,
            "identidad" => $identidad,
            "estado" => 1, // 1 para Activo
            "telefono" => $telefono,
            "empresa_id" => $empresa_id,
            "fecha_registro" => date("Y-m-d H:i:s"),
            "fecha_ingreso" => date("Y-m-d"), // Fecha actual como fecha de ingreso
            "puestos_id" => 5 // Clientes
        ];
        
        return $this->agregar_colaboradores_modelo($datos);
    }  
    
    private function generarCodigoCliente($clientes_id) {
        $codigo = mainModel::generarCodigoUnico($clientes_id);
        $existe = mainModel::ejecutar_consulta_simple("SELECT COUNT(*) as total FROM server_customers WHERE codigo_cliente = '$codigo'");
        return ($existe->fetch_assoc()['total'] > 0) ? (int)(date('Ymd') . substr($clientes_id, -4)) : $codigo;
    }
    
    private function registrarServerCustomer($clientes_id, $empresa_id, $codigo_cliente, $nombre_db, $validar, $planes_id, $sistema_id) {
        $conexion = mainModel::connection();
        $stmt = null;
        $stmtJob = null;
    
        try {
            // Desactivar autocommit para la transacción
            $conexion->autocommit(false);
    
            // 1. Obtener ID de forma más segura
            $server_customers_id = mainModel::correlativo("server_customers_id", "server_customers");
            
            // 2. Validar parámetros importantes
            if (empty($nombre_db) || !preg_match('/^[a-zA-Z0-9_]+$/', $nombre_db)) {
                throw new Exception("Nombre de base de datos inválido");
            }
    
            // 3. Insertar en server_customers (con manejo mejorado de errores)
            $stmt = $conexion->prepare(
                "INSERT INTO server_customers 
                (server_customers_id, clientes_id, codigo_cliente, db, planes_id, sistema_id, validar, estado, db_imported) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)"
            );
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
    
            $stmt->bind_param("iissiii", 
                $server_customers_id, 
                $clientes_id, 
                $codigo_cliente, 
                $nombre_db, 
                $planes_id, 
                $sistema_id, 
                $validar
            );
    
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }
    
            // 4. Verificar que realmente se insertó
            if ($stmt->affected_rows === 0) {
                throw new Exception("No se insertó ningún registro en server_customers");
            }
    
            // 5. Encolar trabajo de importación (con validación)
            $jobData = [
                'db_name' => $nombre_db,
                'client_id' => $clientes_id,
                'server_customers_id' => $server_customers_id,
                'sql_file' => $_SERVER['DOCUMENT_ROOT'].'/plantilla/plantilla_izzy.sql'
            ];
            
            // Verificar que el archivo SQL existe
            if (!file_exists($jobData['sql_file'])) {
                throw new Exception("Archivo SQL de plantilla no encontrado");
            }
    
            $stmtJob = $conexion->prepare(
                "INSERT INTO jobs_queue (job_type, data, status, created_at) 
                VALUES ('db_import', ?, 'pending', NOW())"
            );
            
            if (!$stmtJob) {
                throw new Exception("Error al preparar job: " . $conexion->error);
            }
    
            $jsonData = json_encode($jobData);
            if (!$stmtJob->bind_param("s", $jsonData)) {
                throw new Exception("Error al bindear job: " . $stmtJob->error);
            }
    
            if (!$stmtJob->execute()) {
                throw new Exception("Error al ejecutar job: " . $stmtJob->error);
            }
    
            // Confirmar la transacción
            $conexion->commit();
    
            return [
                'success' => true,
                'server_customers_id' => $server_customers_id,
                'job_queued' => true
            ];
    
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            if ($conexion) {
                $conexion->rollback();
            }
            
            error_log("Error en registrarServerCustomer: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        } finally {
            // Reactivar autocommit y cerrar statements
            if ($conexion) {
                $conexion->autocommit(true);
            }
            if ($stmt) {
                $stmt->close();
            }
            if ($stmtJob) {
                $stmtJob->close();
            }
        }
    }
    
    private function registrarUsuario($clientes_id, $server_customers_id, $nombre, $correo, $password, $colaboradores_id) {
        $conexion = mainModel::connection();
    
        try {
            $conexion->autocommit(false);
    
            // 1. Mejor forma de obtener el próximo ID (para evitar race conditions)
            $users_id = mainModel::correlativo("users_id", "users");
            
            // 2. Validación de email adicional
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Formato de correo electrónico inválido");
            }
    
            // 3. Generación de credenciales
            $username = mainModel::generarUsernameUnico($nombre);
            $password_hash = mainModel::encryption($password);
    
            // 4. Insert con más control de errores
            $stmt = $conexion->prepare(
                "INSERT INTO users 
                (users_id, colaboradores_id, privilegio_id, username, password, email, tipo_user_id, estado, fecha_registro, empresa_id, server_customers_id) 
                VALUES (?, ?, 2, ?, ?, ?, 1, 1, NOW(), ?, ?)"
            );
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
    
            $stmt->bind_param("iisssii", 
                $users_id, 
                $colaboradores_id, 
                $username, 
                $password_hash, 
                $correo, 
                $clientes_id, 
                $server_customers_id
            );
    
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }
    
            // 5. Verificar que realmente se insertó
            if ($stmt->affected_rows === 0) {
                throw new Exception("No se insertó ningún registro");
            }
    
            $conexion->commit();
    
            return [
                'success' => true,
                'users_id' => $users_id,
                'username' => $username,
                'email' => $correo
            ];
    
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en registrarUsuario: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        } finally {
            $conexion->autocommit(true);
            if (isset($stmt)) {
                $stmt->close();
            }
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