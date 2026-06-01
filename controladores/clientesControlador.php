<?php
// clientesControlador.php

if($peticionAjax){
    require_once "../modelos/clientesModelo.php";
    require_once "../core/correo/sendEmail.php";
    require_once "../core/correo/emailTemplates.php";
    require_once "../core/cPanelAPI.php";
    require_once "../core/DatabaseSetup.php";    
}else{
    require_once "./modelos/clientesModelo.php";
    require_once "./core/correo/sendEmail.php";
    require_once "./core/correo/emailTemplates.php";
    require_once "./core/cPanelAPI.php";
    require_once "./core/DatabaseSetup.php";    
}

class clientesControlador extends clientesModelo {
    
    /* Método para agregar clientes normales */
    public function agregar_clientes_controlador(){
        // Validar sesión primero
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
    
        // Sanitizar
        $nombre = trim(mainModel::cleanString($_POST['nombre_clientes'] ?? ''));
        $rtn    = trim(mainModel::cleanString($_POST['identidad_clientes'] ?? ''));
        $fecha  = mainModel::cleanString($_POST['fecha_clientes'] ?? '');
        $depto  = isset($_POST['departamento_cliente']) ? (int)$_POST['departamento_cliente'] : 0;
        $muni   = isset($_POST['municipio_cliente']) ? (int)$_POST['municipio_cliente'] : 0;
        $local  = mainModel::cleanString($_POST['dirección_clientes'] ?? '');
        $tel    = mainModel::cleanString($_POST['telefono_clientes'] ?? '');
        $correo = mainModel::cleanStringStrtolower($_POST['correo_clientes'] ?? '');
        $estado = 1;
        $colab  = $_SESSION['colaborador_id_sd'] ?? 1;
        $freg   = date("Y-m-d H:i:s");
    
        // Obligatorios mínimos
        if ($nombre === '') {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Campos obligatorios",
                "text"  => "El nombre es obligatorio."
            ]);
        }
    
        // RTN / Identidad opcional
        if ($rtn !== '' && !preg_match('/^\d{13,14}$/', $rtn)) {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "RTN/Identidad inválido",
                "text"  => "El RTN/Identidad debe contener 13 o 14 dígitos numéricos."
            ]);
        }
    
        // Teléfono opcional máximo 8 dígitos
        if ($tel !== '' && !preg_match('/^\d{1,8}$/', $tel)) {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Teléfono inválido",
                "text"  => "El teléfono debe tener máximo 8 dígitos numéricos."
            ]);
        }
    
        // Correo opcional
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Correo inválido",
                "text"  => "El formato del correo no es válido."
            ]);
        }
    
        // Límite por plan
        $mainModel  = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();

        if (isset($planConfig['clientes'])) {
            $limiteClientes = (int)$planConfig['clientes'];

            if ($limiteClientes === 0) {
                return $mainModel->showNotification([
                    "type"  => "error",
                    "title" => "Acceso restringido",
                    "text"  => "Su plan actual no permite registrar clientes."
                ]);
            }

            $totalRegs = (int)clientesModelo::getTotalClientesRegistrados();

            if ($totalRegs >= $limiteClientes) {
                return $mainModel->showNotification([
                    "type"  => "error",
                    "title" => "Límite alcanzado",
                    "text"  => "Límite de clientes alcanzado (Máximo: $limiteClientes)."
                ]);
            }
        }
    
        // Duplicado por RTN/Identidad solo si se envió RTN
        if ($rtn !== '' && clientesModelo::valid_clientes_modelo($rtn)->num_rows > 0) {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Duplicado",
                "text"  => "Ya existe un cliente registrado con ese RTN/Identidad."
            ]);
        }
    
        $datos = [
            "nombre"           => $nombre,
            "rtn"              => $rtn,
            "fecha"            => $fecha ?: date("Y-m-d"),
            "departamento_id"  => $depto,
            "municipio_id"     => $muni,
            "localidad"        => $local,
            "telefono"         => $tel,
            "correo"           => $correo,
            "estado_clientes"  => $estado,
            "colaborador_id"   => $colab,
            "fecha_registro"   => $freg,
            "empresa"          => ""
        ];
    
        $nuevoID = clientesModelo::agregar_clientes_modelo($datos);

        if (!$nuevoID) {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Error",
                "text"  => "No se pudo registrar el cliente."
            ]);
        }
    
        mainModel::guardarHistorial([
            "modulo"           => 'Clientes',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'] ?? $colab,
            "status"           => "Registro",
            "observacion"      => "Se registró el cliente {$nombre}".($rtn !== '' ? " con RTN {$rtn}" : " (sin RTN)"),
            "fecha_registro"   => date("Y-m-d H:i:s")
        ]);
    
        return mainModel::showNotification([
            "type"    => "success",
            "title"   => "Registro exitoso",
            "text"    => "Cliente registrado correctamente",
            "form"    => "formClientes",
            "funcion" => "listar_clientes();getDepartamentoClientes();getMunicipiosClientes(0);listar_clientes_factura_buscar();listar_clientes_cotizacion_buscar();listar_colaboradores_buscar_compras();getClientesIngresos();"
        ]);
    }    

    /* Método para registrar clientes autónomos */
    public function registrar_cliente_autonomo_controlador() {
        header('Content-Type: application/json; charset=utf-8');

        $required = ['user_empresa', 'user_name', 'user_telefono', 'email'];

        foreach ($required as $field) {
            if (!isset($_POST[$field])) {
                $this->responderError('Campos faltantes', 'Faltan campos obligatorios', 400);
            }
        }

        $datos = [
            'empresa' => mainModel::cleanString($_POST['user_empresa'] ?? ''),
            'nombre' => mainModel::cleanString($_POST['user_name'] ?? ''),
            'telefono' => mainModel::cleanString($_POST['user_telefono'] ?? ''),
            'correo' => mainModel::cleanStringStrtolower($_POST['email'] ?? ''),
            'password' => mainModel::cleanString(empty($_POST['user_pass']) ? mainModel::generar_password_complejo() : $_POST['user_pass']),
            'sistema_id' => (int)mainModel::cleanString($_POST['sistema_id'] ?? 1),
            'planes_id' => (int)mainModel::cleanString($_POST['planes_id'] ?? 1),
            'eslogan' => mainModel::cleanString($_POST['eslogan'] ?? ''),
            'otra_informacion' => mainModel::cleanString($_POST['otra_informacion'] ?? ''),
            'ubicacion' => mainModel::cleanString($_POST['ubicacion'] ?? ''),
            'celular' => mainModel::cleanString($_POST['celular'] ?? ''),
            'validar' => (int)mainModel::cleanString($_POST['validar'] ?? 1),
            'rtn' => mainModel::cleanString($_POST['rtn'] ?? ''),
            'clientes_id' => (int)mainModel::cleanString($_POST['clientes_id'] ?? 0)
        ];

        $empresa_id_principal = 1;
        $clientes_id = (int)$datos['clientes_id'];

        if (
            empty($datos['nombre']) ||
            empty($datos['empresa']) ||
            empty($datos['telefono']) ||
            empty($datos['correo']) ||
            empty($datos['password'])
        ) {
            $this->responderError('Campos vacíos', 'Todos los campos son obligatorios', 400);
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->responderError('Correo inválido', 'El formato del correo no es válido', 400);
        }

        if (!preg_match('/^\d{1,8}$/', $datos['telefono'])) {
            $this->responderError('Teléfono inválido', 'El teléfono debe contener máximo 8 dígitos numéricos', 400);
        }

        if ($datos['rtn'] !== '' && !preg_match('/^\d{13,14}$/', $datos['rtn'])) {
            $this->responderError('RTN/Identidad inválido', 'El RTN/Identidad debe contener 13 o 14 dígitos numéricos', 400);
        }

        if ($datos['planes_id'] <= 0) {
            $datos['planes_id'] = 1;
        }

        if ($datos['sistema_id'] <= 0) {
            $datos['sistema_id'] = 1;
        }

        if ($datos['validar'] !== 1 && $datos['validar'] !== 2) {
            $datos['validar'] = 1;
        }

        try {
            if ($clientes_id <= 0) {
                if ($this->correoYaRegistrado($datos['correo'])) {
                    $this->responderError('Correo existente', 'Este correo ya está registrado', 400);
                }

                $clientes_id = $this->registrarCliente(
                    $datos['nombre'],
                    $datos['telefono'],
                    $datos['correo'],
                    $datos['empresa'],
                    $datos['rtn']
                );

                if (!$clientes_id) {
                    $this->responderError('Error', 'No se pudo registrar el cliente principal', 500);
                }
            }

            $sistema_nombre = $this->getNombreSistema($datos['sistema_id']);
            $dbNames = mainModel::generateDatabaseName($datos['empresa'], $sistema_nombre);
            $dataBaseCliente = $this->normalizarNombreBaseDatos($dbNames['prefixed']);

            $cpanel = new cPanelAPI();

            $dbSetup = $cpanel->setupCompleteDatabase([
                'db_name' => $dataBaseCliente,
                'db_user' => CPANEL_DB_USERNAME,
                'db_password' => CPANEL_DB_PASSWORD
            ]);

            if (!$dbSetup['success']) {
                $this->responderError(
                    'Error en configuración',
                    $dbSetup['message'] ?? 'Error al configurar la base de datos',
                    500
                );
            }

            $nombre_db_creada = isset($dbSetup['database']['db_name']) && trim($dbSetup['database']['db_name']) !== ''
                ? trim($dbSetup['database']['db_name'])
                : $dataBaseCliente;

            $id_colaborador = $this->registrarColaborador(
                $datos['nombre'],
                $datos['telefono'],
                $datos['rtn'],
                $empresa_id_principal
            );

            if (!$id_colaborador) {
                $this->responderError('Error en registro', 'Error al registrar el colaborador', 500);
            }

            $serverCustomer = $this->registrarServerCustomer(
                $clientes_id,
                $this->generarCodigoCliente(),
                $nombre_db_creada,
                $datos['validar'],
                $datos['planes_id'],
                $datos['sistema_id']
            );

            if (!$serverCustomer || empty($serverCustomer['server_customers_id'])) {
                $this->responderError('Error en el registro', 'No se pudo registrar en server_customers', 500);
            }

            $server_customers_id = (int)$serverCustomer['server_customers_id'];
            $codigo_cliente = (int)$serverCustomer['codigo_cliente'];

            $usuario = $this->registrarUsuario(
                $clientes_id,
                $server_customers_id,
                $datos['nombre'],
                $datos['correo'],
                $datos['password'],
                $id_colaborador
            );

            if (!$usuario || empty($usuario['success'])) {
                $mensajeErrorUsuario = isset($usuario['error']) ? $usuario['error'] : 'Error al registrar el usuario';
                $this->responderError('Error en registro', $mensajeErrorUsuario, 500);
            }

            $jobCreado = $this->registrarJobImportacion(
                $server_customers_id,
                $clientes_id,
                $id_colaborador,
                $usuario['users_id'],
                $nombre_db_creada,
                $datos['password'],
                $datos['empresa'],
                $datos['correo'],
                $datos['telefono'],
                $datos['rtn']
            );

            if (!$jobCreado) {
                $this->responderError('Error en cola de procesos', 'La base fue creada, pero no se pudo registrar el proceso de importación.', 500);
            }

            $this->responderExito([
                'estado' => true,
                'mensaje_estado' => 'Cuenta registrada. Estamos preparando su base de datos; recibirá un correo cuando el acceso esté listo.',
                'cliente' => [
                    'id' => $clientes_id,
                    'nombre' => $datos['nombre'],
                    'email' => $datos['correo']
                ],
                'servidor' => [
                    'server_customers_id' => $server_customers_id,
                    'codigo_cliente' => $codigo_cliente,
                    'nombre_db' => $nombre_db_creada,
                    'db_imported' => 0
                ],
                'usuario' => [
                    'id' => $usuario['users_id'],
                    'email' => $usuario['email']
                ]
            ], $nombre_db_creada);

        } catch (Exception $e) {
            error_log('Error en registro autónomo: ' . $e->getMessage());
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

        $clientes_id = isset($_POST['clientes_id']) ? (int)mainModel::cleanString($_POST['clientes_id']) : 0;
        $nombre = isset($_POST['nombre_clientes']) ? mainModel::cleanStringConverterCase($_POST['nombre_clientes']) : "";
        $departamento_id = isset($_POST['departamento_cliente']) ? intval($_POST['departamento_cliente']) : 0;
        $municipio_id = isset($_POST['municipio_cliente']) ? intval($_POST['municipio_cliente']) : 0;
        $localidad = isset($_POST['dirección_clientes']) ? mainModel::cleanString($_POST['dirección_clientes']) : "";
        $telefono = isset($_POST['telefono_clientes']) ? mainModel::cleanString($_POST['telefono_clientes']) : "";
        $correo = isset($_POST['correo_clientes']) ? mainModel::cleanStringStrtolower($_POST['correo_clientes']) : "";

        if ($clientes_id <= 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se recibió el cliente a actualizar."
            ]);
        }

        if ($nombre == "") {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Campos obligatorios",
                "text" => "El nombre es obligatorio."
            ]);
        }

        /*
            IMPORTANTE:
            El RTN/Identidad NO se valida aquí y NO se toma desde el formulario.
            Ese campo se cambia únicamente desde el botón/método especial de editar RTN.
        */
        $rtn_actual = "";

        $clienteActual = mainModel::consultar_tabla(
            "clientes",
            ["rtn"],
            "clientes_id = {$clientes_id}"
        );

        if (!empty($clienteActual)) {
            $rtn_actual = isset($clienteActual[0]['rtn']) ? trim($clienteActual[0]['rtn']) : "";
        }

        if ($telefono !== '' && !preg_match('/^\d{1,8}$/', $telefono)) {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Teléfono inválido",
                "text"  => "El teléfono debe tener máximo 8 dígitos numéricos."
            ]);
        }

        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Correo inválido",
                "text"  => "El formato del correo no es válido."
            ]);
        }

        $datos = [
            "clientes_id" => $clientes_id,
            "nombre" => $nombre,

            /*
                Se manda el RTN actual para que el modelo no falle si todavía espera este campo,
                pero NO se modifica desde este modal.
            */
            "rtn" => $rtn_actual,

            "departamento_id" => $departamento_id,
            "municipio_id" => $municipio_id,
            "localidad" => $localidad,
            "telefono" => $telefono,
            "correo" => $correo,
            "estado" => $estado
        ];
                    
        if(!clientesModelo::edit_clientes_modelo($datos)){
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo actualizar el cliente"
            ]);
        }
        
        mainModel::guardarHistorial([
            "modulo" => 'Clientes',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Edición",
            "observacion" => "Se editó el cliente {$datos['nombre']}".($rtn_actual !== '' ? " con RTN {$rtn_actual}" : " (sin RTN)"),
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
        
        $clientes_id = isset($_POST['clientes_id']) ? (int)$_POST['clientes_id'] : 0;

        if ($clientes_id <= 0) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se recibió el cliente a eliminar."
            ]);
            exit();
        }

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
        
        mainModel::guardarHistorial([
            "modulo" => 'Clientes',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Eliminar",
            "observacion" => "Se eliminó el cliente {$nombre}".($rtn !== '' ? " con RTN {$rtn}" : " (sin RTN)"),
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
            'title' => 'Registro recibido',
            'mensaje' => "Registro completado. Base de datos {$nombre_db} creada. La configuración se procesará en segundo plano y recibirá un correo cuando el acceso esté listo.",
            'datos' => $datos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function correoYaRegistrado($correo) {
        $correo = mainModel::cleanStringStrtolower($correo);

        $check_email = mainModel::ejecutar_consulta_simple("
            SELECT clientes_id 
            FROM clientes 
            WHERE correo = '$correo'
            LIMIT 1
        ");

        $check_email_user = mainModel::ejecutar_consulta_simple("
            SELECT users_id 
            FROM users 
            WHERE email = '$correo'
            LIMIT 1
        ");

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
            "empresa" => $empresa
        ];

        return clientesModelo::agregar_clientes_modelo($datos);
    }

    public function valid_colaborador_modelo($identidad){
        $conexion = mainModel::connection();
    
        try {
            $sql = "
                SELECT colaboradores_id 
                FROM colaboradores 
                WHERE identidad = ?
                LIMIT 1
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception($conexion->error);
            }
    
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
        $telefono = substr($telefono, 0, 8);
        
        // Generar identidad única si está vacía o es "0"
        if (empty($identidad) || $identidad == "0") {
            do {
                $identidad = "C-" . rand(10000000, 99999999);
                $validacionIdentidad = $this->valid_colaborador_modelo($identidad);
            } while ($validacionIdentidad && $validacionIdentidad->num_rows > 0);
        } else {
            $identidad = substr($identidad, 0, 13);
        }
    
        $datos = [
            "nombre" => $nombre,
            "identidad" => $identidad,
            "estado" => 1,
            "telefono" => $telefono,
            "empresa_id" => $empresa_id,
            "fecha_registro" => date("Y-m-d H:i:s"),
            "fecha_ingreso" => date("Y-m-d"),
            "puestos_id" => 5,
            "fecha_egreso" => '0000-00-00'
        ];
        
        return $this->agregar_colaboradores_modelo($datos);
    }
    
    private function generarServerCustomersIdUnico($conexion) {
        $resultado = $conexion->query("SELECT COALESCE(MAX(server_customers_id), 0) + 1 AS siguiente FROM server_customers");

        if (!$resultado) {
            throw new Exception("Error al generar server_customers_id: " . $conexion->error);
        }

        $row = $resultado->fetch_assoc();
        return (int)$row['siguiente'];
    }

    private function generarCodigoCliente($clientes_id = 0) {
        $conexion = mainModel::connection();

        for ($i = 0; $i < 50; $i++) {
            $codigo = (int)mainModel::generarCodigoUnico((int)$clientes_id);

            if ($codigo <= 0 || strlen((string)$codigo) > 8) {
                $codigo = random_int(10000000, 99999999);
            }

            $stmt = $conexion->prepare("SELECT server_customers_id FROM server_customers WHERE codigo_cliente = ? LIMIT 1");

            if (!$stmt) {
                throw new Exception("Error al validar código de cliente: " . $conexion->error);
            }

            $stmt->bind_param("i", $codigo);
            $stmt->execute();
            $result = $stmt->get_result();
            $existe = ($result && $result->num_rows > 0);
            $stmt->close();

            if (!$existe) {
                return $codigo;
            }
        }

        throw new Exception("No se pudo generar un código de cliente único.");
    }

    private function normalizarNombreBaseDatos($nombre_db) {
        $nombre_db = trim((string)$nombre_db);

        if ($nombre_db === '') {
            throw new Exception("No se pudo generar el nombre de la base de datos.");
        }

        if (strlen($nombre_db) > 40) {
            $nombre_db = substr($nombre_db, 0, 40);
        }

        return $nombre_db;
    }

    private function registrarServerCustomer($clientes_id, $codigo_cliente, $nombre_db, $validar, $planes_id, $sistema_id) {
        $conexion = mainModel::connection();
        $stmt = null;

        try {
            $conexion->autocommit(false);
            $conexion->query("LOCK TABLES server_customers WRITE");

            $server_customers_id = $this->generarServerCustomersIdUnico($conexion);
            $codigo_cliente_final = (int)$codigo_cliente;

            for ($i = 0; $i < 50; $i++) {
                $stmtValidar = $conexion->prepare("SELECT server_customers_id FROM server_customers WHERE codigo_cliente = ? LIMIT 1");

                if (!$stmtValidar) {
                    throw new Exception("Error al validar código de cliente: " . $conexion->error);
                }

                $stmtValidar->bind_param("i", $codigo_cliente_final);
                $stmtValidar->execute();
                $resultValidar = $stmtValidar->get_result();
                $existeCodigo = ($resultValidar && $resultValidar->num_rows > 0);
                $stmtValidar->close();

                if (!$existeCodigo) {
                    break;
                }

                $codigo_cliente_final = random_int(10000000, 99999999);
            }

            $stmt = $conexion->prepare("
                INSERT INTO server_customers
                (
                    server_customers_id,
                    clientes_id,
                    codigo_cliente,
                    db,
                    planes_id,
                    sistema_id,
                    validar,
                    estado,
                    db_imported
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)
            ");

            if (!$stmt) {
                throw new Exception("Error al preparar server_customers: " . $conexion->error);
            }

            $clientes_id = (int)$clientes_id;
            $planes_id = (int)$planes_id;
            $sistema_id = (int)$sistema_id;
            $validar = (int)$validar;

            $stmt->bind_param(
                "iiisiii",
                $server_customers_id,
                $clientes_id,
                $codigo_cliente_final,
                $nombre_db,
                $planes_id,
                $sistema_id,
                $validar
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar server customer: " . $stmt->error);
            }

            $conexion->commit();

            return [
                'server_customers_id' => $server_customers_id,
                'codigo_cliente' => $codigo_cliente_final
            ];

        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en registrarServerCustomer: " . $e->getMessage());
            return false;

        } finally {
            if ($stmt) {
                $stmt->close();
            }

            $conexion->query("UNLOCK TABLES");
            $conexion->autocommit(true);
        }
    }

    private function registrarJobImportacion($server_customers_id, $clientes_id, $colaboradores_id, $users_id, $nombre_db, $password_temporal, $empresa_nombre, $correo, $telefono, $rtn) {
        $conexion = mainModel::connection();
        $stmt = null;

        try {
            $plantillaSql = dirname(__DIR__) . '/plantilla/plantilla_izzy.sql';

            if (!file_exists($plantillaSql)) {
                throw new Exception("No se encontró la plantilla SQL: " . $plantillaSql);
            }

            $colaboradorData = $this->obtenerColaboradorPrincipal($colaboradores_id);
            $usuarioData = $this->obtenerUsuarioPrincipal($users_id);

            if (empty($colaboradorData)) {
                throw new Exception("No se encontró el colaborador principal para crear el job.");
            }

            if (empty($usuarioData)) {
                throw new Exception("No se encontró el usuario principal para crear el job.");
            }

            $data = [
                'db_name' => $nombre_db,
                'client_id' => (int)$clientes_id,
                'server_customers_id' => (int)$server_customers_id,
                'sql_file' => $plantillaSql,
                'password_temporal' => $password_temporal,
                'empresa_nombre' => $empresa_nombre,
                'correo' => $correo,
                'telefono' => $telefono,
                'rtn' => $rtn,
                'login_url' => SERVERURL . 'login/'
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            $jsonColaborador = json_encode($colaboradorData, JSON_UNESCAPED_UNICODE);
            $jsonUsuario = json_encode($usuarioData, JSON_UNESCAPED_UNICODE);

            $dbUser = CPANEL_DB_USERNAME;
            $dbPass = CPANEL_DB_PASSWORD;
            $notifyEmail = $correo;

            $stmt = $conexion->prepare("
                INSERT INTO jobs_queue
                (
                    job_type,
                    data,
                    db_user,
                    db_password,
                    colaborador_data,
                    usuario_data,
                    notify_email,
                    status,
                    attempts,
                    max_attempts
                )
                VALUES ('db_import', ?, ?, ?, ?, ?, ?, 'pending', 0, 3)
            ");

            if (!$stmt) {
                throw new Exception("Error al preparar jobs_queue: " . $conexion->error);
            }

            $stmt->bind_param(
                "ssssss",
                $jsonData,
                $dbUser,
                $dbPass,
                $jsonColaborador,
                $jsonUsuario,
                $notifyEmail
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar job: " . $stmt->error);
            }

            return true;

        } catch (Exception $e) {
            error_log("Error en registrarJobImportacion: " . $e->getMessage());
            return false;

        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }
    }

    private function obtenerColaboradorPrincipal($colaboradores_id) {
        $conexion = mainModel::connection();

        $stmt = $conexion->prepare("
            SELECT
                colaboradores_id,
                puestos_id,
                nombre,
                identidad,
                estado,
                telefono,
                empresa_id,
                fecha_registro,
                fecha_ingreso,
                fecha_egreso
            FROM colaboradores
            WHERE colaboradores_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception("Error al consultar colaborador principal: " . $conexion->error);
        }

        $colaboradores_id = (int)$colaboradores_id;
        $stmt->bind_param("i", $colaboradores_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : [];
        $stmt->close();

        return $row;
    }

    private function obtenerUsuarioPrincipal($users_id) {
        $conexion = mainModel::connection();

        $stmt = $conexion->prepare("
            SELECT
                users_id,
                colaboradores_id,
                privilegio_id,
                username,
                password,
                email,
                tipo_user_id,
                estado,
                fecha_registro,
                empresa_id,
                server_customers_id
            FROM users
            WHERE users_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception("Error al consultar usuario principal: " . $conexion->error);
        }

        $users_id = (int)$users_id;
        $stmt->bind_param("i", $users_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : [];
        $stmt->close();

        return $row;
    }

    private function generarUsername($correo, $nombre = '') {
        $correo = trim((string)$correo);

        if ($correo !== '' && strpos($correo, '@') !== false) {
            $username = substr($correo, 0, strpos($correo, '@'));
        } else {
            $username = $nombre;
        }

        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username));

        if ($username === '') {
            $username = 'usuario';
        }

        return substr($username, 0, 20);
    }

    private function registrarUsuario($clientes_id, $server_customers_id, $nombre, $correo, $password, $colaboradores_id) {
        $conexion = mainModel::connection();
        $stmt = null;

        try {
            $conexion->autocommit(false);

            $users_id = mainModel::correlativo("users_id", "users");

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Formato de correo electrónico inválido");
            }

            $username = $this->generarUsername($correo, $nombre);
            $password_hash = mainModel::encryption($password);
            $fecha_registro = date("Y-m-d H:i:s");
            $privilegio_id = 2;
            $tipo_user_id = 1;
            $estado = 1;

            $stmt = $conexion->prepare("
                INSERT INTO users
                (
                    users_id,
                    colaboradores_id,
                    privilegio_id,
                    username,
                    password,
                    email,
                    tipo_user_id,
                    estado,
                    fecha_registro,
                    empresa_id,
                    server_customers_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }

            $clientes_id = (int)$clientes_id;
            $server_customers_id = (int)$server_customers_id;
            $colaboradores_id = (int)$colaboradores_id;

            $stmt->bind_param(
                "iiisssiiiii",
                $users_id,
                $colaboradores_id,
                $privilegio_id,
                $username,
                $password_hash,
                $correo,
                $tipo_user_id,
                $estado,
                $fecha_registro,
                $clientes_id,
                $server_customers_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

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

            if ($stmt) {
                $stmt->close();
            }
        }
    }

    private function enviarCorreoBienvenida($datosUsuario, $empresa_id) {
        /*
            Este método se conserva por compatibilidad, pero el correo de acceso listo
            se envía desde scripts/process_jobs.php, después de importar la plantilla.
        */
        return true;
    }

    private function getNombreSistema($sistema_id) {
        $conexion = mainModel::connection();
        $stmt = null;
    
        try {
            $stmt = $conexion->prepare("
                SELECT LOWER(nombre) AS nombre 
                FROM sistema 
                WHERE sistema_id = ? 
                  AND estado = 1
                LIMIT 1
            ");

            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
    
            $stmt->bind_param("i", $sistema_id);
            $stmt->execute();

            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['nombre'];
            }

            throw new Exception("No se encontró un sistema con el ID proporcionado o está inactivo.");

        } catch (Exception $e) {
            error_log("Error en getNombreSistema: " . $e->getMessage());
            throw $e;

        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }
    }
}