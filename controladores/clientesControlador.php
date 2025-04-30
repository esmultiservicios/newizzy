<?php
//clientesControlador.php
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
        $sistema_nombre = $this->getNombreSistema($datos['sistema_id']);

        // Generar nombres para la base de datos
        $dbNames = mainModel::generateDatabaseName($datos['empresa'], $sistema_nombre);

        $codigo_cliente = $this->generarCodigoCliente($clientes_id);
        $dataBaseCliente = $dbNames['prefixed'];
        
        // Ejecutamos el API de cPanel para poder crear la base de datos
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
            
            // Registrar Colaborador primero
            $id_colaborador = $this->registrarColaborador(
                $datos['nombre'], 
                $datos['telefono'], 
                $datos['rtn'], 
                1
            );

            if (!$id_colaborador) {
                $this->responderError('Error en registro', "Error al registrar el colaborador", 500);
            }

            // Preparar datos para server customer
            $datosColaborador = [
                'colaboradores_id' => $id_colaborador,
                'puestos_id' => 5,
                'nombre' => $datos['nombre'],
                'identidad' => $datos['rtn'],
                'estado' => 1,
                'telefono' => $datos['telefono'],
                'empresa_id' => 1,
                'fecha_registro' => date("Y-m-d H:i:s"),
                'fecha_ingreso' => date("Y-m-d"),
                'fecha_egreso' => '0000-00-00'
            ];

            $datosUsuarioParcial = [
                'colaboradores_id' => $id_colaborador,
                'privilegio_id' => 2,
                'password' => mainModel::encryption($datos['password']),
                'email' => $datos['correo'],
                'tipo_user_id' => 1,
                'estado' => 1,
                'empresa_id' => $clientes_id
            ];

            // Registrar server customer (esto generará el ID que necesitamos)
            $server_customers_id = $this->registrarServerCustomer(
                $clientes_id,
                $empresa_id,
                $codigo_cliente,
                $dbSetup['database']['db_name'],
                $datos['validar'],
                $datos['planes_id'],
                $datos['sistema_id'],
                $datosColaborador,
                $datosUsuarioParcial
            );            
            
            if (!$server_customers_id) {
                $this->responderError('Error en el registro', 'No se pudo registrar en server_customers', 500);
            }

            // Ahora registrar el usuario con el server_customers_id obtenido
            $usuario = $this->registrarUsuario(
                $clientes_id,
                $server_customers_id,
                $datos['nombre'],
                $datos['correo'],
                $datos['password'],
                $id_colaborador
            );
            
            if (!$usuario) {
                $this->responderError('Error en registro', "Error al registrar el usuario", 500);
            }

            // Actualizar el job en la cola con el users_id generado
            $this->actualizarJobConUserId($server_customers_id, $usuario['users_id']);
            
            // Enviar correo de bienvenida
            /* $this->enviarCorreoBienvenida([
                'nombre' => $datos['nombre'],
                'email' => $datos['correo'],
                'empresa' => $datos['empresa'],
                'nombre_db' => $dataBaseCliente,
                'password' => $datos['password']
            ], 1);   */                                      

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
                    'email' => $usuario['email']
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

    public function valid_colaborador_modelo($identidad){
        $conexion = mainModel::connection();
    
        try {
            $sql = "SELECT colaboradores_id FROM colaboradores WHERE identidad = ?";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) throw new Exception($conexion->error);
    
            $stmt->bind_param("s", $identidad);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $stmt->close();
    
            return $resultado;
        } catch (Exception $e) {
            return false;
        }
    }    

    private function registrarColaborador($nombre, $telefono, $identidad, $empresa_id) {
        // Validar y formatear teléfono
        $telefono = substr($telefono, 0, 8); // Asegurar máximo 8 caracteres
        
        // Generar identidad única si está vacía o es "0"
        if (empty($identidad) || $identidad == "0") {
            do {
                $identidad = "C-" . rand(10000000, 99999999); // Formato: C- + 8 dígitos
            } while ($this->valid_colaborador_modelo($identidad)->num_rows > 0);
        } else {
            // Si viene con valor, asegurar que no exceda los 13 caracteres
            $identidad = substr($identidad, 0, 13);
        }
    
        $datos = [
            "nombre" => $nombre,
            "identidad" => $identidad,
            "estado" => 1, // 1 para Activo
            "telefono" => $telefono,
            "empresa_id" => $empresa_id,
            "fecha_registro" => date("Y-m-d H:i:s"),
            "fecha_ingreso" => date("Y-m-d"),
            "puestos_id" => 5, // Clientes
            "fecha_egreso" => '0000-00-00' // Valor por defecto para NOT NULL
        ];
        
        return $this->agregar_colaboradores_modelo($datos);
    }
    
    private function generarCodigoCliente($clientes_id) {
        $codigo = mainModel::generarCodigoUnico($clientes_id);
        $existe = mainModel::ejecutar_consulta_simple("SELECT COUNT(*) as total FROM server_customers WHERE codigo_cliente = '$codigo'");
        return ($existe->fetch_assoc()['total'] > 0) ? (int)(date('Ymd') . substr($clientes_id, -4)) : $codigo;
    }
    
    private function registrarServerCustomer($clientes_id, $empresa_id, $codigo_cliente, $nombre_db, $validar, $planes_id, $sistema_id, $datosColaborador, $datosUsuarioParcial) {
        $conexion = mainModel::connection();
        $stmt = null;
        $stmtJob = null;
    
        try {
            $conexion->autocommit(false);
    
            $server_customers_id = mainModel::correlativo("server_customers_id", "server_customers");
            
            // Insertar en server_customers
            $stmt = $conexion->prepare(
                "INSERT INTO server_customers 
                (server_customers_id, clientes_id, codigo_cliente, db, planes_id, sistema_id, validar, estado, db_imported) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)"
            );
            
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
                throw new Exception("Error al registrar server customer: " . $stmt->error);
            }

            // Preparar datos para el job
            $jobData = [
                'db_name' => $nombre_db,
                'client_id' => $clientes_id,
                'server_customers_id' => $server_customers_id,
                'sql_file' => dirname(dirname(__DIR__)) . '/plantilla/plantilla_izzy.sql'
            ];

            // Convertir a JSON antes de bind_param para evitar el error de referencia
            $jsonData = json_encode($jobData);
            $jsonColaborador = json_encode($datosColaborador);
            $jsonUsuario = json_encode($datosUsuarioParcial);
            
            // Crear variables para los parámetros que se pasan por referencia
            $dbUser = CPANEL_DB_USERNAME;
            $dbPass = CPANEL_DB_PASSWORD;
            $notifyEmail = $datosUsuarioParcial['email'];

            // Insertar el job con todos los datos
            $stmtJob = $conexion->prepare(
                "INSERT INTO jobs_queue 
                (job_type, data, db_user, db_password, colaborador_data, usuario_data, notify_email, status, attempts, max_attempts) 
                VALUES ('db_import', ?, ?, ?, ?, ?, ?, 'pending', 0, 3)"
            );
            
            $stmtJob->bind_param("ssssss", 
                $jsonData,
                $dbUser,
                $dbPass,
                $jsonColaborador,
                $jsonUsuario,
                $notifyEmail
            );
    
            if (!$stmtJob->execute()) {
                throw new Exception("Error al registrar job: " . $stmtJob->error);
            }
    
            $conexion->commit();
    
            return $server_customers_id;
    
        } catch (Exception $e) {
            if ($conexion) {
                $conexion->rollback();
            }
            error_log("Error en registrarServerCustomer: " . $e->getMessage());
            return false;
        } finally {
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
    
    private function actualizarJobConUserId($server_customers_id, $users_id) {
        $conexion = mainModel::connection();
        
        try {
            // Obtener el job más reciente para este server_customers_id
            $stmt = $conexion->prepare(
                "SELECT id FROM jobs_queue 
                 WHERE data LIKE ? 
                 ORDER BY created_at DESC 
                 LIMIT 1"
            );
            
            $search = '%"server_customers_id":'.$server_customers_id.'%';
            $stmt->bind_param("s", $search);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $job = $result->fetch_assoc();
                $jobId = $job['id'];
                
                // Actualizar los datos del usuario en el job
                $updateStmt = $conexion->prepare(
                    "UPDATE jobs_queue 
                     SET usuario_data = JSON_SET(usuario_data, '$.users_id', ?) 
                     WHERE id = ?"
                );
                
                $updateStmt->bind_param("ii", $users_id, $jobId);
                $updateStmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error al actualizar job con user_id: " . $e->getMessage());
        } finally {
            if (isset($stmt)) $stmt->close();
            if (isset($updateStmt)) $updateStmt->close();
        }
    }
    
    private function registrarUsuario($clientes_id, $server_customers_id, $nombre, $correo, $password, $colaboradores_id) {
        $conexion = mainModel::connection();
    
        try {
            $conexion->autocommit(false);
    
            // 1. Obtener el próximo ID
            $users_id = mainModel::correlativo("users_id", "users");
            
            // 2. Validación de email
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Formato de correo electrónico inválido");
            }
    
            // 3. Generación de hash para la contraseña (sin username)
            $password_hash = mainModel::encryption($password);
    
            // 4. Insert modificado (eliminado el campo username)
            $stmt = $conexion->prepare(
                "INSERT INTO users 
                (users_id, colaboradores_id, privilegio_id, password, email, tipo_user_id, estado, fecha_registro, empresa_id, server_customers_id) 
                VALUES (?, ?, 2, ?, ?, 1, 1, NOW(), ?, ?)"
            );
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
    
            $stmt->bind_param("iissii", 
                $users_id, 
                $colaboradores_id, 
                $password_hash, 
                $correo, 
                $clientes_id, 
                $server_customers_id
            );
    
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }
    
            // 5. Verificar inserción
            if ($stmt->affected_rows === 0) {
                throw new Exception("No se insertó ningún registro");
            }
    
            $conexion->commit();
    
            return [
                'success' => true,
                'users_id' => $users_id,
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
        $conexion = mainModel::connection();
    
        try {
            $stmt = $conexion->prepare("SELECT LOWER(nombre) AS nombre FROM sistema WHERE sistema_id = ? AND estado = 1");
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
    
            $stmt->bind_param("i", $sistema_id);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['nombre'];
            } else {
                throw new Exception("No se encontró un sistema con el ID proporcionado o está inactivo.");
            }
        } catch (Exception $e) {
            error_log("Error en getNombreSistema: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}