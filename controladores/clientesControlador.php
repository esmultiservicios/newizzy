<?php

// clientesControlador.php



if($peticionAjax){

    require_once "../modelos/clientesModelo.php";

    require_once "../core/correo/sendEmail.php";

    require_once "../core/correo/emailTemplates.php";

    require_once "../core/registro/cPanelAPI.php";

    require_once "../core/registro/DatabaseSetup.php";    

}else{

    require_once "./modelos/clientesModelo.php";

    require_once "./core/correo/sendEmail.php";

    require_once "./core/correo/emailTemplates.php";

    require_once "./core/registro/cPanelAPI.php";

    require_once "./core/registro/DatabaseSetup.php";    

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



        $fields = ['user_empresa', 'user_name', 'user_telefono', 'email', 'user_pass',

            'user_repeatpass', 'clientes_id', 'sistema_id', 'validar', 'rtn'];

        foreach ($fields as $field) {

            if (isset($_POST[$field]) && !is_string($_POST[$field])) {

                $this->responderError('Datos inválidos', 'Revise los campos del formulario.');

            }

        }



        $empresa = trim($_POST['user_empresa'] ?? '');

        $nombre = trim($_POST['user_name'] ?? '');

        $telefono = trim($_POST['user_telefono'] ?? '');

        $correo = strtolower(trim($_POST['email'] ?? ''));

        $password = $_POST['user_pass'] ?? '';

        $clientesId = max(0, (int)($_POST['clientes_id'] ?? 0));

        $publicRegistration = $clientesId === 0;

        $sistema = 1;

        $validar = 1;

        $rtn = '';



        $cn = null;

        $database = null;

        try {

            if ($clientesId > 0) {

                $session = mainModel::validarSesion();

                if (!empty($session['error']) || (int)($_SESSION['empresa_id_sd'] ?? 0) !== 1

                    || (int)($_SESSION['privilegio_sd'] ?? 0) !== 1

                    || ($_SESSION['db_cliente'] ?? '') !== DB_MAIN) {

                    $this->responderError('Acceso restringido', 'Utilice el registro público para abrir una cuenta.', 403);

                }

                $sistema = (int)($_POST['sistema_id'] ?? 1);

                $validar = (int)($_POST['validar'] ?? 1) === 2 ? 2 : 1;

                $rtn = trim($_POST['rtn'] ?? '');

                if (!in_array($sistema, [1, 2], true) || ($rtn !== '' && !preg_match('/^\\d{13,14}$/D', $rtn))) {

                    $this->responderError('Datos inválidos', 'Revise el sistema y el RTN.');

                }

                if ($password === '') $password = bin2hex(random_bytes(16));

            } elseif ($password !== ($_POST['user_repeatpass'] ?? null)) {

                $this->responderError('Contraseña inválida', 'Las contraseñas deben coincidir.');

            }



            if ($empresa === '' || $nombre === '' || mb_strlen($empresa) > 100 || mb_strlen($nombre) > 100) {

                $this->responderError('Datos inválidos', 'Ingrese la empresa y el nombre completo, con un máximo de 100 caracteres.');

            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 45) {

                $this->responderError('Correo inválido', 'Ingrese un correo válido de hasta 45 caracteres.');

            }

            if (!preg_match('/^\\d{8,12}$/D', $telefono)) {

                $this->responderError('Teléfono inválido', 'El teléfono debe tener entre 8 y 12 dígitos.');

            }

            if (mb_strlen($password) < 8 || strlen($password) > 120) {

                $this->responderError('Contraseña inválida', 'Use al menos 8 caracteres y un máximo de 120 bytes.');

            }



            $faseRegistro = 'configuracion_cpanel';

            izzyRequireAdministrativeConfig('cPanel');

            $faseRegistro = 'plantilla_no_disponible';

            if (!is_readable(dirname(__DIR__) . '/plantilla/plantilla_izzy.sql')) {

                throw new RuntimeException('Plantilla de aprovisionamiento no disponible.');

            }

            $faseRegistro = 'conexion_db_main';

            $cn = new mysqli(SERVER, USER, PASS, DB_MAIN);

            $cn->set_charset('utf8mb4');

            $faseRegistro = 'tablas_registro_db_main';

            $engines = $cn->query("SELECT COUNT(*) FROM information_schema.tables

                WHERE table_schema=DATABASE() AND ENGINE='InnoDB'

                  AND table_name IN ('clientes','colaboradores','users','server_customers','jobs_queue')");

            if ((int)$engines->fetch_row()[0] !== 5) {

                throw new RuntimeException('Aplique la sección de registro de DB_Cambios.sql en DB_MAIN.');

            }

            $faseRegistro = 'columna_db_imported';
            $cn->query('SELECT db_imported FROM server_customers LIMIT 0')->free();
            // Los correlativos heredados comparten el mismo conjunto de tablas.

            if ((int)$cn->query("SELECT GET_LOCK(CONCAT('izzy.signup.',MD5(DATABASE())),15)")->fetch_row()[0] !== 1) {

                $cn->close();

                $this->responderError('Registro ocupado', 'Intente nuevamente en unos segundos.', 503);

            }

            $faseRegistro = 'registro_cliente';

            $cn->begin_transaction();

            $stmt = $cn->prepare('SELECT EXISTS(SELECT 1 FROM users WHERE email=?) OR EXISTS(SELECT 1 FROM clientes WHERE correo=? AND clientes_id<>?)');

            $stmt->bind_param('ssi', $correo, $correo, $clientesId);

            $stmt->execute();

            $exists = (int)$stmt->get_result()->fetch_row()[0];

            $stmt->close();

            if ($exists) {

                $cn->rollback();

                $cn->close();

                $this->responderError('Correo existente', 'El correo ya está registrado. Puede iniciar sesión o recuperar su contraseña.', 409);

            }



            if ($clientesId > 0) {

                $stmt = $cn->prepare('SELECT clientes_id FROM clientes WHERE clientes_id=? AND correo=? FOR UPDATE');

                $stmt->bind_param('is', $clientesId, $correo);

                $stmt->execute();

                $customerMatches = $stmt->get_result()->num_rows === 1;

                $stmt->close();

                if (!$customerMatches) throw new RuntimeException('El cliente y el correo no coinciden.');

            } else {

                $clientesId = $this->registrarCliente($nombre, $telefono, $correo, $empresa, $rtn, $cn);

            }



            $faseRegistro = 'registro_colaborador';

            $colaboradorId = $this->registrarColaborador($nombre, $telefono, $rtn, 1, $cn);

            $faseRegistro = 'catalogo_sistemas';

            $systemName = $this->getNombreSistema($sistema, $cn);

            $dbNames = $this->generateDatabaseName($empresa, $systemName);

            $database = $this->normalizarNombreBaseDatos($dbNames['prefixed']);

            $stmt = $cn->prepare('SELECT 1 FROM server_customers WHERE db=? LIMIT 1');

            $stmt->bind_param('s', $database);

            $stmt->execute();

            $databaseInUse = $stmt->get_result()->num_rows > 0;

            $stmt->close();

            if ($publicRegistration || $databaseInUse) {

                $suffix = '_' . bin2hex(random_bytes(3)) . '_' . $systemName;

                $companyPart = substr($dbNames['prefixed'], 0, -strlen('_' . $systemName));

                $database = substr($companyPart, 0, 40 - strlen($suffix)) . $suffix;

            }

            $accountId = $this->generarServerCustomersIdUnico($cn);
            $faseRegistro = 'registro_usuario';
            $user = $this->registrarUsuario($clientesId, $accountId, $nombre, $correo, $password, $colaboradorId, $cn);
            $faseRegistro = 'registro_cuenta';
            $this->registrarServerCustomer($clientesId, random_int(10000000, 99999999), $database, $validar, 6, $sistema, $cn, $accountId);
            $safeName = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');

            $safeCompany = htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8');

            $faseRegistro = 'cola_correo';

            $this->encolarCorreoRegistro('registro_user_'.$accountId, [$correo => $nombre],

                'IZZY | Confirmación de registro',

                "<p>Hola, {$safeName}:</p><p>Recibimos el registro de <strong>{$safeCompany}</strong> en el plan Gratis de IZZY.</p>"

                . '<p>Estamos preparando su espacio de trabajo. Le enviaremos un segundo correo con el enlace de acceso cuando esté listo.</p>'

                . '<p>Gracias por elegir IZZY.<br>Equipo de IZZY · ES MULTISERVICIOS</p>', $cn);

            $this->encolarCorreoRegistro('registro_admin_'.$accountId, $this->destinatariosRegistro($cn),

                'IZZY | Nuevo cliente registrado',

                "<p>Cuenta {$accountId}: {$safeCompany}. Aprovisionamiento pendiente.</p>", $cn);

            $faseRegistro = 'cola_importacion';

            $this->registrarJobImportacion($accountId, $clientesId, $colaboradorId, $user['users_id'], $database, $empresa, $correo, $telefono, $rtn, $cn);



            $faseRegistro = 'confirmar_transaccion';

            $cn->commit();

            $cn->close();

            $this->responderExito(['estado' => true, 'servidor' => [

                'server_customers_id' => $accountId, 'db_imported' => 0,

            ]]);

        } catch (Throwable $e) {

            if ($cn) {

                $cn->rollback();

                $cn->close();

            }

            if ($e instanceof IzzyAdministrativeConfigurationException) {

                error_log('IZZY alta: ' . $e->getMessage());

                $this->responderError('Registro temporalmente no disponible', 'El servicio de alta requiere configuración. Contacte al equipo de IZZY.', 503);

            }

            $referencia = bin2hex(random_bytes(6));

            $faseRegistro = $faseRegistro ?? 'validacion';

            error_log(sprintf('IZZY registro ref=%s fase=%s error=%s codigo=%d',

                $referencia, $faseRegistro, get_class($e), (int)$e->getCode()));

            $this->responderError('Registro no completado',

                'No pudimos completar el registro. Referencia de soporte '.$referencia.'.', 500);

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

    

    private function responderExito($datos) {

        echo json_encode([

            'estado' => true,

            'type' => 'success',

            'title' => 'Registro recibido',

            'mensaje' => "Registro recibido. Estamos preparando su espacio de trabajo. Recibirá un correo cuando pueda ingresar.",

            'datos' => $datos

        ], JSON_UNESCAPED_UNICODE);

        exit;

    }

    

    private function registrarCliente($nombre, $telefono, $correo, $empresa, $rtn, mysqli $conexion) {

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



        return clientesModelo::agregar_clientes_modelo($datos, $conexion);

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



    private function registrarColaborador($nombre, $telefono, $identidad, $empresa_id, mysqli $conexion) {

        

        // Generar identidad única si está vacía o es "0"

        if (empty($identidad) || $identidad == "0") {

            $check = $conexion->prepare('SELECT 1 FROM colaboradores WHERE identidad=? LIMIT 1');

            $check->bind_param('s', $identidad);

            do {

                $identidad = 'C-' . random_int(10000000, 99999999);

                $check->execute();

            } while ($check->get_result()->num_rows > 0);

            $check->close();

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

        

        return $this->agregar_colaboradores_modelo($datos, $conexion);

    }

    

    private function generarServerCustomersIdUnico($conexion) {

        $resultado = $conexion->query("SELECT COALESCE(MAX(server_customers_id), 0) + 1 AS siguiente FROM server_customers");



        if (!$resultado) {

            throw new Exception("Error al generar server_customers_id: " . $conexion->error);

        }



        $row = $resultado->fetch_assoc();

        return (int)$row['siguiente'];

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



    private function registrarServerCustomer($clientes_id, $codigo_cliente, $nombre_db, $validar, $planes_id, $sistema_id, mysqli $conexion, int $server_customers_id) {

        $stmt = null;



        try {





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

            if ($existeCodigo) throw new RuntimeException('No se pudo reservar el código de cliente.');



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

                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)

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





            return [

                'server_customers_id' => $server_customers_id,

                'codigo_cliente' => $codigo_cliente_final

            ];



        } finally {

            if ($stmt) {

                $stmt->close();

            }



        }

    }



    private function destinatariosRegistro(mysqli $cn): array {

        $result = $cn->query('SELECT nombre,correo FROM notificaciones WHERE activo=1');

        if (!$result) throw new Exception('No se pudo consultar la tabla notificaciones.');

        $out=[];

        while ($row=$result->fetch_assoc()) {

            $mail=trim((string)$row['correo']);

            if (filter_var($mail,FILTER_VALIDATE_EMAIL)) $out[$mail]=trim((string)$row['nombre']) ?: $mail;

        }

        return $out;

    }



    private function encolarCorreoRegistro(string $key,array $recipients,string $subject,string $html, mysqli $cn): void {

        if (!$recipients) return;

        $like='%"notification_key":"'.$key.'"%';

        $type='registration_email';

        $stmt=$cn->prepare('SELECT id FROM jobs_queue WHERE job_type=? AND data LIKE ? LIMIT 1');

        $stmt->bind_param('ss',$type,$like);$stmt->execute();$exists=$stmt->get_result()->num_rows;$stmt->close();

        if ($exists) return;

        $json=json_encode(['notification_key'=>$key,'recipients'=>$recipients,'subject'=>$subject,'html'=>$html],JSON_UNESCAPED_UNICODE);

        $stmt=$cn->prepare("INSERT INTO jobs_queue(job_type,data,status,attempts,max_attempts) VALUES('registration_email',?,'pending',0,10)");

        if (!$stmt) throw new Exception('No se pudo preparar notificación de registro.');

        $stmt->bind_param('s',$json);

        if (!$stmt->execute()) throw new Exception('No se pudo encolar la notificación de registro: '.$stmt->error);

        $stmt->close();

    }



    private function registrarJobImportacion($server_customers_id, $clientes_id, $colaboradores_id, $users_id, $nombre_db, $empresa_nombre, $correo, $telefono, $rtn, mysqli $conexion) {

        $stmt = null;



        try {

            $plantillaSql = dirname(__DIR__) . '/plantilla/plantilla_izzy.sql';



            if (!file_exists($plantillaSql)) {

                throw new Exception("No se encontró la plantilla SQL: " . $plantillaSql);

            }



            $colaboradorData = $this->obtenerColaboradorPrincipal($colaboradores_id, $conexion);

            $usuarioData = $this->obtenerUsuarioPrincipal($users_id, $conexion);



            if (empty($colaboradorData)) {

                throw new Exception("No se encontró el colaborador principal para crear el job.");

            }



            if (empty($usuarioData)) {

                throw new Exception("No se encontró el usuario principal para crear el job.");

            }



            unset($usuarioData['password']);

            $data = [

                'db_name' => $nombre_db,
                'provision_database' => true,

                'db_user' => CPANEL_DB_USERNAME,



                'client_id' => (int)$clientes_id,

                'server_customers_id' => (int)$server_customers_id,



                'colaborador_data' => $colaboradorData,

                'usuario_data' => $usuarioData,



                'notify_email' => $correo,



                'empresa_nombre' => $empresa_nombre,

                'correo' => $correo,

                'telefono' => $telefono,

                'rtn' => $rtn,

                'login_url' => SERVERURL . 'login/'

            ];



            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);



            if ($jsonData === false) {

                throw new Exception("No se pudo convertir el job a JSON.");

            }



            $stmt = $conexion->prepare("

                INSERT INTO jobs_queue

                (

                    job_type,

                    data,

                    status,

                    attempts,

                    max_attempts,

                    error_message

                )

                VALUES ('db_import', ?, 'pending', 0, 3, NULL)

            ");



            if (!$stmt) {

                throw new Exception("Error al preparar jobs_queue: " . $conexion->error);

            }



            $stmt->bind_param("s", $jsonData);



            if (!$stmt->execute()) {

                throw new Exception("Error al registrar job: " . $stmt->error);

            }



            return true;



        } catch (Exception $e) {

            throw $e;



        } finally {

            if ($stmt) {

                $stmt->close();

            }

        }

    }



    private function obtenerColaboradorPrincipal($colaboradores_id, mysqli $conexion) {



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



    private function obtenerUsuarioPrincipal($users_id, mysqli $conexion) {



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



    private function registrarUsuario($clientes_id, $server_customers_id, $nombre, $correo, $password, $colaboradores_id, mysqli $conexion) {

        $stmt = null;



        try {



            $users_id = (int)$conexion->query("SELECT COALESCE(MAX(users_id),0)+1 FROM users")->fetch_row()[0];



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

            $empresa_id_principal = 1;



            $stmt->bind_param(

                "iiisssiisii",

                $users_id,

                $colaboradores_id,

                $privilegio_id,

                $username,

                $password_hash,

                $correo,

                $tipo_user_id,

                $estado,

                $fecha_registro,

                $empresa_id_principal,

                $server_customers_id

            );



            if (!$stmt->execute()) {

                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);

            }



            if ($stmt->affected_rows === 0) {

                throw new Exception("No se insertó ningún registro");

            }





            return [

                'success' => true,

                'users_id' => $users_id,

                'username' => $username,

                'email' => $correo

            ];



        } finally {



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



    private function getNombreSistema($sistema_id, mysqli $conexion) {

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