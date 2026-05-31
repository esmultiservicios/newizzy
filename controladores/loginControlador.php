<?php
// loginControlador.php

if($peticionAjax){		
    require_once "../modelos/loginModel.php";	
    require_once "../core/Database.php";
    require_once "../core/correo/sendEmail.php";
}else{		
    require_once "./modelos/loginModel.php";
    require_once "./core/Database.php";
    require_once "./core/correo/sendEmail.php";
}

class loginControlador extends loginModel{
    
    public function iniciar_sesion_controlador() {
        $username = isset($_POST['inputEmail']) ? mainModel::cleanString($_POST['inputEmail']) : "";
        $password = isset($_POST['inputPassword']) ? mainModel::encryption($_POST['inputPassword']) : "";
        $inputCliente = isset($_POST['inputCliente']) ? mainModel::cleanString($_POST['inputCliente']) : "";
        $inputPin = isset($_POST['inputPin']) ? mainModel::cleanString($_POST['inputPin']) : "";
    
        // Antes de usar $GLOBALS['db'] en iniciar_sesion_controlador
        if (!isset($GLOBALS['db']) || empty($GLOBALS['db'])) {
            $GLOBALS['db'] = $GLOBALS['DB_MAIN'];
        }
            
        $respuesta = false;
        $query_server = "";
        $codigoCliente = "";
        $pingInvalido = false;
        $Consultacliente = false;
        $mantenimiento = false;
    
        if ($inputCliente !== "" && $inputPin !== "") {
            $query_server = "SELECT 
                    COALESCE(s.server_customers_id, '0') AS server_customers_id, 
                    COALESCE(s.db, '" . DB_MAIN . "') AS db, 
                    codigo_cliente
                FROM users AS u
                LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
                WHERE s.codigo_cliente = '$inputCliente'";
    
            $resultServerUser = mainModel::connectionLogin()->query($query_server);
    
            if ($resultServerUser && $resultServerUser->num_rows > 0) {
                $consultaServeruser = $resultServerUser->fetch_assoc();
                $codigoCliente = $consultaServeruser['codigo_cliente'];
            }
    
            $mysqliPin = mainModel::connectionDBLocal(DB_MAIN);
    
            $query = "SELECT pin FROM pin WHERE codigo_cliente = '$codigoCliente' AND pin = '$inputPin' AND fecha_hora_fin > NOW()";

            $resultPin = $mysqliPin->query($query) or die($mysqliPin->error);
    
            if ($resultPin->num_rows > 0) {
                $Consultacliente = true;
                $respuesta = true;
            } else {
                $pingInvalido = true;
                $respuesta = false;
            }

        } else if ($inputCliente === "" && $inputPin === "") {
            $query_server = "SELECT 
                    COALESCE(s.server_customers_id, '0') AS server_customers_id, 
                    COALESCE(s.db, '" . DB_MAIN . "') AS db, 
                    codigo_cliente
                FROM users AS u
                LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
                WHERE BINARY u.email = '$username'";
            
            $respuesta = true;

        } else {
            $respuesta = false;
        }
    
        if ($respuesta) {
            $resultServerUser = mainModel::connectionLogin()->query($query_server);
    
            if ($resultServerUser && $resultServerUser->num_rows > 0) {
                $consultaServeruser = $resultServerUser->fetch_assoc();
                $codigoCliente = $consultaServeruser['codigo_cliente'];
                $GLOBALS['db'] = $consultaServeruser['db'] === "" ? $GLOBALS['DB_MAIN'] : $consultaServeruser['db'];
    
                $datosLogin = [
                    "username" => $username,
                    "password" => $password,
                    "db" => $GLOBALS['db'],
                ];
    
                if ($Consultacliente) {
                    $datosLogin = [
                        "username" => "admin",
                        "password" => mainModel::encryption("C@M1Cl1n1c@r3"),
                        "db" => $GLOBALS['db'],
                    ];
    
                    $result = loginModel::iniciar_sesion_admin_modelo($datosLogin);
    
                    if ($result && $result->num_rows > 0) {
                        $mantenimiento = true;
                    }

                } else {
                    $result = loginModel::iniciar_sesion_modelo($datosLogin);
                }
    
                if ($result && $result->num_rows != 0) {
                    $row = $result->fetch_assoc();
    
                    $fechaActual = date("Y-m-d");
                    $añoActual = date("Y");
                    $horaActual = date("H:i:s");
    
                    $query = "SELECT bitacora_id FROM bitacora";
                    $result1 = mainModel::ejecutar_consulta_simple($query);
    
                    $numero = ($result1->num_rows) + 1;
                    $codigoB = mainModel::getRandom("CB", 7, $numero);
    
                    $datosBitacora = [
                        "bitacoraCodigo" => $codigoB,
                        "bitacoraFecha" => $fechaActual,
                        "bitacoraHoraInicio" => $horaActual,
                        "bitacoraHoraFinal" => "Sin Registro",
                        "bitacoraTipo" => $row['tipo_user_id'],
                        "bitacoraYear" => $añoActual,
                        "user_id" => $row['users_id']
                    ];
    
                    $insertarBitacora = mainModel::guardar_bitacora($datosBitacora);
    
                    if ($insertarBitacora) {
                        $_SESSION['users_id_sd'] = $row['users_id'];
                        $_SESSION['user_sd'] = $row['users_id'];
                        $_SESSION['tipo_sd'] = $row['cuentaTipo'];
                        $_SESSION['privilegio_sd'] = $row['privilegio_id'];
                        $_SESSION['tipo_user_id_sd'] = $row['tipo_user_id'];
                        $_SESSION['token_sd'] = uniqid(mt_rand(), true);
                        $_SESSION['server_token'] = $_SESSION['token_sd'];
                        $_SESSION['colaborador_id_sd'] = $row['colaboradores_id'];
                        $_SESSION['empresa_id_sd'] = $row['empresa_id'];
                        $_SESSION['server_customers_id'] = $row['server_customers_id'];
                        $_SESSION['codigo_bitacora_sd'] = $codigoB;
                        $_SESSION['identidad'] = $row['identidad'];
                        $_SESSION['codigoCliente'] = $codigoCliente;
                        $_SESSION['session_time'] = time();

                        // CONSULTAMOS EL PLAN ACTIVO DEL CLIENTE
                        $resultPlanSistema = mainModel::getPlanSistema()->fetch_assoc();
                        $_SESSION['planes_id'] = $resultPlanSistema['plan_id'];
                        $_SESSION['planes_id_sistema'] = $resultPlanSistema['planes_id'];

                        if ($mantenimiento) {
                            $_SESSION['modo_soporte'] = "SI";
                        } else {
                            $_SESSION['modo_soporte'] = "NO";
                        }
    
                        $_SESSION['db_cliente'] = $consultaServeruser['db'];

                        /*
                            Enviar notificación de inicio de sesión.
                            No se envía si es modo soporte/mantenimiento para evitar alertas innecesarias.
                            Si falla el correo, NO bloquea el login.
                        */
                        if (!$mantenimiento) {
                            $this->enviarCorreoInicioSesion($row, $codigoCliente);
                        }
    
                        $result_consultaMenu = loginModel::getMenuAccesoLogin($row['privilegio_id']);
    
                        if ($result_consultaMenu->num_rows > 0) {
                            $result_MenuAcceso = $result_consultaMenu->fetch_assoc();
                            $consultaMenu = $result_MenuAcceso['name'];
    
                            $url = SERVERURL . $consultaMenu . "/";

                        } else {
                            $result_consultaSubMenu = loginModel::getSubMenuAccesoLogin($row['privilegio_id']);
    
                            if ($result_consultaSubMenu->num_rows > 0) {
                                $result_SubMenuAcceso = $result_consultaSubMenu->fetch_assoc();
                                $consultaSubMenu = $result_SubMenuAcceso['name'];
    
                                $url = SERVERURL . $consultaSubMenu . "/";

                            } else {
                                $result_consultaSubMenu1 = loginModel::getSubMenu1AccesoLogin($row['privilegio_id']);
    
                                if ($result_consultaSubMenu1->num_rows > 0) {
                                    $result_SubMenu1Acceso = $result_consultaSubMenu1->fetch_assoc();
                                    $consultaSubMenu1 = $result_SubMenu1Acceso['name'];
    
                                    $url = SERVERURL . $consultaSubMenu1 . "/";

                                } else {
                                    $url = SERVERURL . "dashboard/";
                                }
                            }
                        }
    
                        $datos = [
                            0 => $url,
                            1 => "",
                        ];

                    } else {
                        $datos = [
                            0 => "",
                            1 => "Error",
                        ];
                    }

                } else {
                    $datos = [
                        0 => "",
                        1 => "ErrorS",
                    ];
                }

            } else {
                $datos = [
                    0 => "",
                    1 => "ErrorC",
                ];
            }

        } else {
            if ($pingInvalido) {
                $datos = [
                    0 => "",
                    1 => "ErrorPinInvalido",
                ];
            } else {
                $datos = [
                    0 => "",
                    1 => "ErrorVacio",
                ];
            }
        }
    
        return json_encode($datos);
    }

    private function obtenerNombreSistemaCorreo() {
        $nombreSistema = "IZZY";

        if (defined('COMPANY') && trim(COMPANY) != "") {
            $company = trim(COMPANY);

            /*
                Ejemplo:
                COMPANY = 'IZZY :: ES MULTISERVICIOS'
                Resultado:
                IZZY
            */
            if (strpos($company, "::") !== false) {
                $partes = explode("::", $company);
                $nombreSistema = trim($partes[0]);
            } else {
                $nombreSistema = trim($company);
            }
        }

        if ($nombreSistema == "") {
            $nombreSistema = "IZZY";
        }

        return strtoupper($nombreSistema);
    }

    private function enviarCorreoInicioSesion($rowUsuario, $codigoCliente = "") {
        try {
            $correoUsuario = "";

            if (isset($rowUsuario['email']) && filter_var($rowUsuario['email'], FILTER_VALIDATE_EMAIL)) {
                $correoUsuario = trim($rowUsuario['email']);
            }

            if ($correoUsuario == "") {
                return false;
            }

            $nombreUsuario = "Usuario";

            if (isset($rowUsuario['nombre']) && trim($rowUsuario['nombre']) != "") {
                $nombreUsuario = trim($rowUsuario['nombre']);
            } else if (isset($rowUsuario['colaborador']) && trim($rowUsuario['colaborador']) != "") {
                $nombreUsuario = trim($rowUsuario['colaborador']);
            } else if (isset($rowUsuario['nombre_completo']) && trim($rowUsuario['nombre_completo']) != "") {
                $nombreUsuario = trim($rowUsuario['nombre_completo']);
            } else {
                $nombreUsuario = $correoUsuario;
            }

            $empresa_id = isset($rowUsuario['empresa_id']) ? (int)$rowUsuario['empresa_id'] : 0;

            if ($empresa_id <= 0 && isset($_SESSION['empresa_id_sd'])) {
                $empresa_id = (int)$_SESSION['empresa_id_sd'];
            }

            if ($empresa_id <= 0) {
                $empresa_id = 1;
            }

            $sendEmail = new sendEmail();

            $empresaData = $sendEmail->obtenerDatosEmpresa($empresa_id);

            $empresaNombre = isset($empresaData['nombre']) && trim($empresaData['nombre']) != "" 
                ? strtoupper(trim($empresaData['nombre'])) 
                : "LA EMPRESA";

            $sistemaNombre = $this->obtenerNombreSistemaCorreo();

            $fechaHora = date("d/m/Y h:i:s a");
            $ip = $this->obtenerIpCliente();
            $navegador = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "No identificado";

            $codigoClienteTexto = "";

            if ($codigoCliente != "") {
                $codigoClienteTexto = '
                    <li style="margin-bottom: 6px;">
                        <strong>Código de cliente:</strong> '.$codigoCliente.'
                    </li>
                ';
            }

            $asunto = "Nuevo inicio de sesión en " . $sistemaNombre;

            $mensaje = '
                <div style="padding: 20px; font-family: Arial, Helvetica, sans-serif; color: #2d3748;">
                    <p style="margin-bottom: 10px;">
                        ¡Hola '.$nombreUsuario.'!
                    </p>

                    <p style="margin-bottom: 12px;">
                        Se detectó un nuevo inicio de sesión en su cuenta de <b>'.$sistemaNombre.'</b>.
                    </p>

                    <div style="
                        background: #f8fafc;
                        border-left: 4px solid #0d6efd;
                        padding: 14px 16px;
                        border-radius: 8px;
                        margin: 18px 0;
                    ">
                        <p style="margin-top: 0; margin-bottom: 10px;">
                            <strong>Detalles del acceso:</strong>
                        </p>

                        <ul style="margin: 0; padding-left: 18px;">
                            <li style="margin-bottom: 6px;">
                                <strong>Sistema:</strong> '.$sistemaNombre.'
                            </li>
                            <li style="margin-bottom: 6px;">
                                <strong>Empresa:</strong> '.$empresaNombre.'
                            </li>
                            <li style="margin-bottom: 6px;">
                                <strong>Usuario:</strong> '.$correoUsuario.'
                            </li>
                            <li style="margin-bottom: 6px;">
                                <strong>Fecha y hora:</strong> '.$fechaHora.'
                            </li>
                            <li style="margin-bottom: 6px;">
                                <strong>IP:</strong> '.$ip.'
                            </li>
                            '.$codigoClienteTexto.'
                            <li style="margin-bottom: 6px;">
                                <strong>Dispositivo/Navegador:</strong> '.$navegador.'
                            </li>
                        </ul>
                    </div>

                    <p style="margin-bottom: 12px;">
                        Si este inicio de sesión fue realizado por usted, puede ignorar este mensaje.
                    </p>

                    <p style="margin-bottom: 12px;">
                        Si no reconoce esta actividad, le recomendamos cambiar su contraseña inmediatamente
                        y comunicarse con soporte.
                    </p>

                    <p>
                        Atentamente,<br>
                        <b>El equipo de '.$empresaNombre.'</b>
                    </p>
                </div>
            ';

            $destinatarios = [
                $correoUsuario => $nombreUsuario
            ];

            $bccDestinatarios = [];
            $correo_tipo_id = 1; // Notificaciones
            $archivos_adjuntos = [];

            $sendEmail->enviarCorreo(
                $destinatarios,
                $bccDestinatarios,
                $asunto,
                $mensaje,
                $correo_tipo_id,
                $empresa_id,
                $archivos_adjuntos
            );

            return true;

        } catch (Throwable $e) {
            error_log("Error enviando correo de inicio de sesión: " . $e->getMessage());
            return false;
        }
    }

    private function obtenerIpCliente() {
        $ip = "";

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = "No identificada";
        }

        return $ip;
    }	
    
    /* ============================
       Cerrar sesión (compat 1/0)
       ============================ */
    public function cerrar_sesion_controlador($tokenParam = null){
        // Asegura sesión iniciada
        if(session_status() !== PHP_SESSION_ACTIVE){
            session_start(['name'=>'SD']);
        }

        // Token de la sesión
        $tokenSesion = $_SESSION['server_token'] ?? '';

        // Si enviaron un token por parámetro, úsalo para validar (opcional)
        $token = $tokenParam ?: $tokenSesion;

        $hora = date("H:i:s");

        $datos = [
            "usuario" => $_SESSION['user_sd'] ?? '',
            "token_s" => $_SESSION['token_sd'] ?? '',
            "token" => $token,
            "codigo" => $_SESSION['codigo_bitacora_sd'] ?? '',
            "hora" => $hora,
        ];

        // Guarda historial (no debe romper logout si falla)
        @mainModel::guardar_historial_accesos("Cierre de Sesion");

        // Intento normal vía modelo
        $ok = 0;

        try{
            $ok = loginModel::cerrar_sesion_modelo($datos);
        }catch(Throwable $e){
            $ok = 0;
        }

        // Siempre limpia la sesión (fallback)
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        @session_destroy();

        // 1 = ok, 0 = error (compatibilidad con código viejo)
        return ($ok == 1) ? 1 : 0;
    }

    /* =========================================
       Cerrar sesión (JSON robusto para AJAX)
       ========================================= */
    public function cerrar_sesion_controlador_json($tokenParam = null){
        // Asegura sesión iniciada
        if(session_status() !== PHP_SESSION_ACTIVE){
            session_start(['name'=>'SD']);
        }

        $result = $this->cerrar_sesion_controlador($tokenParam);

        if ($result == 1) {
            return json_encode([
                'ok' => true,
                'message' => 'Sesión cerrada correctamente.',
                'redirect' => SERVERURL . 'login/'
            ], JSON_UNESCAPED_UNICODE);

        } else {
            // Aunque el modelo haya fallado, la sesión fue destruida arriba (fallback)
            return json_encode([
                'ok' => true,
                'message' => 'Sesión cerrada (forzada).',
                'redirect' => SERVERURL . 'login/'
            ], JSON_UNESCAPED_UNICODE);
        }
    }	
    
    public function forzar_cierre_sesion_controlador(){
        // Verificar si la sesión no está activa
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['name'=>'SD']);
        }
        
        // Registrar el cierre forzado en el historial
        $colaborador_id = isset($_SESSION['colaborador_id_sd']) ? intval($_SESSION['colaborador_id_sd']) : 0;
        $usuario = isset($_SESSION['user_sd']) ? $_SESSION['user_sd'] : 'Desconocido';
        
        // Guardar en el historial con datos seguros
        mainModel::guardar_historial_accesos("Cierre de Sesión Forzado - Usuario: $usuario");
        
        // Limpiar completamente la sesión
        $_SESSION = array();
        
        // Eliminar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirección segura
        header("Location: ".SERVERURL."login/");
        exit();
    }
    
    public function validar_pago_pendiente_main_server_controlador(){
        $username = isset($_POST['inputEmail']) ? mainModel::cleanString($_POST['inputEmail']) : "";
        $password = isset($_POST['inputPassword']) ? mainModel::encryption($_POST['inputPassword']) : "";
        
        $query_server = "SELECT 
                COALESCE(s.server_customers_id, '0') AS server_customers_id, 
                COALESCE(s.db, '" . DB_MAIN . "') AS db, 
                codigo_cliente
            FROM users AS u
            LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
            WHERE BINARY u.email = '$username'";
        
        $resultServerUser = mainModel::connectionLogin()->query($query_server);
                
        if ($resultServerUser && $resultServerUser->num_rows > 0) {
            $consultaServeruser = $resultServerUser->fetch_assoc();
            $GLOBALS['db'] = $consultaServeruser['db'] === "" ? $GLOBALS['DB_MAIN'] : $consultaServeruser['db'];
        }	

        $result = loginModel::validar_pago_pendiente_main_server_modelo();
        $result_validar_cliente = loginModel::validar_cliente_server_modelo();
            
        $date = date("Y-m-d");
        $año = date("Y");
        $mes = date("m");

        $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-01"));
        $fecha_final = date("Y-m-d", strtotime($año."-".$mes."-15"));

        // SI NOS ESTAMOS CONECTANDO AL SISTEMA PRINCIPAL, SIMPLEMENTE ENTRAMOS SIN PROBLEMA
        if($GLOBALS['db'] == DB_MAIN_LOGIN_CONTROLADOR){
            $datos = 1;

        }else{			
            $result_pagoVencido = loginModel::validar_cliente_pagos_vencidos_main_server_modelo();

            $row = $result_validar_cliente->fetch_assoc();

            // EVALUAMOS QUE LA VARIABLE VALIDAR NO VENGA VACÍA O NULA
            $validar = $row['validar'] ?? 0;
            
            // CONSULTAMOS SI ES NECESARIO VALIDAR EL CLIENTE
            if($validar == 0){
                $datos = 1;

            }else{
                // VALIDAMOS SI EL CLIENTE TIENE PAGOS VENCIDOS
                if($result_pagoVencido->num_rows >= 1){
                    $datos = [
                        0 => "",
                        1 => "ErrorP",
                    ];	

                }else{
                    // SI EL CLIENTE NO TIENE PAGOS VENCIDOS, EVALUAMOS LOS PAGOS PENDIENTES DEL MES EN CURSO
                    if($result->num_rows == 1){
                        if($date >= $fecha_inicial && $date <= $fecha_final){
                            $datos = 1;
                        }else{
                            $datos = [
                                0 => "",
                                1 => "ErrorP",
                            ];	
                        }

                    }else if($result->num_rows > 2){
                        $datos = [
                            0 => "",
                            1 => "ErrorP",
                        ];	

                    }else{
                        $datos = 1;
                    }
                }					
            }				
        }

        return json_encode($datos);
    }
}