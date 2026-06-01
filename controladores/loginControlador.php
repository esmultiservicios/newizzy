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

        if (!isset($GLOBALS['db']) || empty($GLOBALS['db'])) {
            $GLOBALS['db'] = $GLOBALS['DB_MAIN'];
        }

        $respuesta = false;
        $query_server = "";
        $codigoCliente = "";
        $pingInvalido = false;
        $Consultacliente = false;
        $mantenimiento = false;
        $consultaServeruser = null;
        $datosPinSoporte = false;

        if ($inputCliente !== "" && $inputPin !== "") {

            if (!is_numeric($inputCliente) || !is_numeric($inputPin)) {
                $pingInvalido = true;
                $respuesta = false;

            } else {
                $datosPinSoporte = $this->validarPinSoporteClienteModelo((int)$inputCliente, (int)$inputPin);

                if ($datosPinSoporte) {
                    $Consultacliente = true;
                    $respuesta = true;
                    $codigoCliente = $datosPinSoporte['codigo_cliente'];

                    $consultaServeruser = [
                        "server_customers_id" => $datosPinSoporte['server_customers_id'],
                        "clientes_id" => $datosPinSoporte['clientes_id'],
                        "db" => $datosPinSoporte['db'],
                        "codigo_cliente" => $datosPinSoporte['codigo_cliente'],
                        "planes_id" => $datosPinSoporte['planes_id'],
                        "sistema_id" => $datosPinSoporte['sistema_id']
                    ];

                    $GLOBALS['db'] = $datosPinSoporte['db'] === "" ? $GLOBALS['DB_MAIN'] : $datosPinSoporte['db'];

                } else {
                    $pingInvalido = true;
                    $respuesta = false;
                }
            }

        } else if ($inputCliente === "" && $inputPin === "") {

            $query_server = "SELECT 
                    COALESCE(s.server_customers_id, '0') AS server_customers_id, 
                    COALESCE(s.db, '" . DB_MAIN . "') AS db, 
                    s.codigo_cliente
                FROM users AS u
                LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
                WHERE BINARY u.email = '$username'";

            $respuesta = true;

        } else {
            $respuesta = false;
        }

        if ($respuesta) {

            if (!$Consultacliente) {
                $resultServerUser = mainModel::connectionLogin()->query($query_server);

                if ($resultServerUser && $resultServerUser->num_rows > 0) {
                    $consultaServeruser = $resultServerUser->fetch_assoc();
                    $codigoCliente = $consultaServeruser['codigo_cliente'];
                    $GLOBALS['db'] = $consultaServeruser['db'] === "" ? $GLOBALS['DB_MAIN'] : $consultaServeruser['db'];
                }
            }

            if ($consultaServeruser) {

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
                        $_SESSION['codigo_bitacora_sd'] = $codigoB;
                        $_SESSION['identidad'] = $row['identidad'];
                        $_SESSION['codigoCliente'] = $codigoCliente;
                        $_SESSION['session_time'] = time();

                        if ($Consultacliente) {
                            $_SESSION['server_customers_id'] = $consultaServeruser['server_customers_id'];
                        } else {
                            $_SESSION['server_customers_id'] = $row['server_customers_id'];
                        }

                        $resultPlanSistema = mainModel::getPlanSistema()->fetch_assoc();
                        $_SESSION['planes_id'] = $resultPlanSistema['plan_id'];
                        $_SESSION['planes_id_sistema'] = $resultPlanSistema['planes_id'];

                        if ($mantenimiento) {
                            $_SESSION['modo_soporte'] = "SI";
                        } else {
                            $_SESSION['modo_soporte'] = "NO";
                        }

                        $_SESSION['db_cliente'] = $consultaServeruser['db'];

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

    private function validarPinSoporteClienteModelo($codigoCliente, $pin) {
        try {
            $mysqli = mainModel::connectionDBLocal(DB_MAIN);

            $query = "SELECT 
                            sc.server_customers_id,
                            sc.clientes_id,
                            sc.codigo_cliente,
                            sc.db,
                            sc.planes_id,
                            sc.sistema_id,
                            sc.validar,
                            sc.estado
                      FROM pin p
                      INNER JOIN server_customers sc
                            ON sc.server_customers_id = p.server_customers_id
                            AND sc.codigo_cliente = p.codigo_cliente
                      WHERE p.codigo_cliente = ?
                      AND p.pin = ?
                      AND p.fecha_hora_inicio <= NOW()
                      AND p.fecha_hora_fin > NOW()
                      AND sc.estado = 1
                      LIMIT 1";

            $stmt = $mysqli->prepare($query);

            if (!$stmt) {
                error_log("Error validarPinSoporteClienteModelo prepare: " . $mysqli->error);
                return false;
            }

            $stmt->bind_param("ii", $codigoCliente, $pin);

            if (!$stmt->execute()) {
                error_log("Error validarPinSoporteClienteModelo execute: " . $stmt->error);
                $stmt->close();
                $mysqli->close();
                return false;
            }

            $result = $stmt->get_result();

            if ($result->num_rows <= 0) {
                $stmt->close();
                $mysqli->close();
                return false;
            }

            $data = $result->fetch_assoc();

            $stmt->close();
            $mysqli->close();

            return $data;

        } catch (Throwable $e) {
            error_log("Error validarPinSoporteClienteModelo: " . $e->getMessage());
            return false;
        }
    }

    private function invalidarPinSoporteClienteModelo($serverCustomersId, $codigoCliente, $pin) {
        try {
            $mysqli = mainModel::connectionDBLocal(DB_MAIN);

            $fechaActual = date("Y-m-d H:i:s");

            $query = "UPDATE pin
                      SET fecha_hora_fin = ?
                      WHERE server_customers_id = ?
                      AND codigo_cliente = ?
                      AND pin = ?
                      AND fecha_hora_fin > NOW()";

            $stmt = $mysqli->prepare($query);

            if (!$stmt) {
                error_log("Error invalidarPinSoporteClienteModelo prepare: " . $mysqli->error);
                return false;
            }

            $stmt->bind_param("siii", $fechaActual, $serverCustomersId, $codigoCliente, $pin);
            $result = $stmt->execute();

            $stmt->close();
            $mysqli->close();

            return $result;

        } catch (Throwable $e) {
            error_log("Error invalidarPinSoporteClienteModelo: " . $e->getMessage());
            return false;
        }
    }

    private function obtenerNombreSistemaCorreo() {
        $nombreSistema = "IZZY";

        if (defined('COMPANY') && trim(COMPANY) != "") {
            $company = trim(COMPANY);

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
            $correo_tipo_id = 1;
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
    
    public function cerrar_sesion_controlador($tokenParam = null){
        if(session_status() !== PHP_SESSION_ACTIVE){
            session_start(['name'=>'SD']);
        }

        $tokenSesion = $_SESSION['server_token'] ?? '';
        $token = $tokenParam ?: $tokenSesion;
        $hora = date("H:i:s");

        $datos = [
            "usuario" => $_SESSION['user_sd'] ?? '',
            "token_s" => $_SESSION['token_sd'] ?? '',
            "token" => $token,
            "codigo" => $_SESSION['codigo_bitacora_sd'] ?? '',
            "hora" => $hora,
        ];

        @mainModel::guardar_historial_accesos("Cierre de Sesion");

        $ok = 0;

        try{
            $ok = loginModel::cerrar_sesion_modelo($datos);
        }catch(Throwable $e){
            $ok = 0;
        }

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

        return ($ok == 1) ? 1 : 0;
    }

    public function cerrar_sesion_controlador_json($tokenParam = null){
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
            return json_encode([
                'ok' => true,
                'message' => 'Sesión cerrada.',
                'redirect' => SERVERURL . 'login/'
            ], JSON_UNESCAPED_UNICODE);
        }
    }	
    
    public function forzar_cierre_sesion_controlador(){
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['name'=>'SD']);
        }
        
        $usuario = isset($_SESSION['user_sd']) ? $_SESSION['user_sd'] : 'Desconocido';
        
        mainModel::guardar_historial_accesos("Cierre de Sesión Forzado - Usuario: $usuario");
        
        $_SESSION = array();
        
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
        
        session_destroy();
        
        header("Location: ".SERVERURL."login/");
        exit();
    }
    
    public function validar_pago_pendiente_main_server_controlador(){
        $username = isset($_POST['inputEmail']) ? mainModel::cleanString($_POST['inputEmail']) : "";
        
        $query_server = "SELECT 
                COALESCE(s.server_customers_id, '0') AS server_customers_id, 
                COALESCE(s.db, '" . DB_MAIN . "') AS db, 
                s.codigo_cliente
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

        if($GLOBALS['db'] == DB_MAIN_LOGIN_CONTROLADOR){
            $datos = 1;

        }else{			
            $result_pagoVencido = loginModel::validar_cliente_pagos_vencidos_main_server_modelo();

            $row = $result_validar_cliente->fetch_assoc();

            $validar = $row['validar'] ?? 0;
            
            if($validar == 0){
                $datos = 1;

            }else{
                if($result_pagoVencido->num_rows >= 1){
                    $datos = [
                        0 => "",
                        1 => "ErrorP",
                    ];	

                }else{
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