<?php
// usuarioControlador.php

if($peticionAjax){
    require_once "../modelos/usuarioModelo.php";
    require_once "../core/correo/sendEmail.php";
    require_once "../core/correo/NotificationService.php";
}else{
    require_once "./modelos/usuarioModelo.php";
    require_once "./core/correo/sendEmail.php";
    require_once "./core/correo/NotificationService.php";
}

class usuarioControlador extends usuarioModelo{

    private function obtenerDetalleUsuarioNotificacion($users_id){
        $conexion = mainModel::connection();
        $detalle = null;
        try {
            $stmt = $conexion->prepare("
                SELECT
                    u.users_id,
                    u.email,
                    u.privilegio_id,
                    u.tipo_user_id,
                    u.empresa_id,
                    u.estado,
                    u.server_customers_id,
                    COALESCE(c.nombre, u.email) AS nombre,
                    COALESCE(p.nombre, CONCAT('Privilegio #', u.privilegio_id)) AS privilegio,
                    COALESCE(t.nombre, CONCAT('Tipo #', u.tipo_user_id)) AS tipo_usuario,
                    COALESCE(e.nombre, CONCAT('Empresa #', u.empresa_id)) AS empresa
                FROM users u
                LEFT JOIN colaboradores c ON c.colaboradores_id = u.colaboradores_id
                LEFT JOIN privilegio p ON p.privilegio_id = u.privilegio_id
                LEFT JOIN tipo_user t ON t.tipo_user_id = u.tipo_user_id
                LEFT JOIN empresa e ON e.empresa_id = u.empresa_id
                WHERE u.users_id = ?
                LIMIT 1
            ");
            if ($stmt) {
                $id = (int)$users_id;
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $detalle = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log("usuarioControlador obtenerDetalleUsuarioNotificacion: ".$e->getMessage());
        }
        return $detalle;
    }

    private function obtenerNombreCatalogoUsuario($tabla, $idCampo, $id, $fallback){
        $permitidos = [
            'privilegio' => 'privilegio_id',
            'tipo_user' => 'tipo_user_id',
            'empresa' => 'empresa_id'
        ];
        if (!isset($permitidos[$tabla]) || $permitidos[$tabla] !== $idCampo) {
            return $fallback;
        }
        $conexion = mainModel::connection();
        try {
            $sql = "SELECT nombre FROM `{$tabla}` WHERE `{$idCampo}` = ? LIMIT 1";
            $stmt = $conexion->prepare($sql);
            if ($stmt) {
                $id = (int)$id;
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row && trim((string)$row['nombre']) !== '') {
                    return trim((string)$row['nombre']);
                }
            }
        } catch (Throwable $e) {
            error_log("usuarioControlador obtenerNombreCatalogoUsuario: ".$e->getMessage());
        }
        return $fallback;
    }

    private function notificarAuditoriaUsuario($asunto, $resumen, array $detalles = [], array $cambios = [], $tipo = 'audit'){
        try {
            $service = new NotificationService();
            $dbActual = $service->currentDbName();
            $empresaId = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

            if ($dbActual === '') {
                return ['sent' => false, 'count' => 0, 'message' => 'No se pudo determinar la base actual.'];
            }

            // Fuera de Asignación de Planes, la auditoría del usuario se envía
            // únicamente a la tabla notificaciones de la base donde ocurrió la acción.
            return $service->notifyAdmins($dbActual, $empresaId, $asunto, $resumen, $detalles, $cambios, $tipo);
        } catch (Throwable $e) {
            error_log("usuarioControlador notificarAuditoriaUsuario: ".$e->getMessage());
            return false;
        }
    }

    private function notificarUsuarioDirecto($email, $nombre, $asunto, $resumen, array $detalles = [], array $cambios = [], $tipo = 'security', $sensible = false, $nota = ''){
        try {
            $service = new NotificationService();
            return $service->notifyUser(
                $service->currentDbName(),
                isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0,
                $email,
                $nombre,
                $asunto,
                $resumen,
                $detalles,
                $cambios,
                $tipo,
                $sensible,
                $nota
            );
        } catch (Throwable $e) {
            error_log("usuarioControlador notificarUsuarioDirecto: ".$e->getMessage());
            return false;
        }
    }


    /*----------- Controlador para agregar usuario -----------*/
    public function agregar_usuario_controlador() {     
        
        // Validar sesión primero
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $sendEmail = new sendEmail();

        $users_id = isset($_SESSION['users_id_sd']) ? (int)$_SESSION['users_id_sd'] : 0;
        $empresa_id_sesion = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
        $server_customers_id = isset($_SESSION['server_customers_id']) ? (int)$_SESSION['server_customers_id'] : 0;

        if ($users_id <= 0 || $empresa_id_sesion <= 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Sesión inválida",
                "text" => "No se pudo identificar el usuario o empresa de la sesión."
            ]);
        }

        // Datos del colaborador nuevo o existente
        $es_nuevo_colaborador = isset($_POST['es_nuevo_colaborador']) ? mainModel::cleanString($_POST['es_nuevo_colaborador']) : "0";

        $colaborador_id = 0;
        
        if($es_nuevo_colaborador == '1') {

            // Procesar nuevo colaborador
            $nombre = isset($_POST['nombre_colaborador']) ? mainModel::cleanString($_POST['nombre_colaborador']) : "";
            $identidad = isset($_POST['identidad_colaborador']) ? mainModel::cleanString($_POST['identidad_colaborador']) : "";
            $telefono = isset($_POST['telefono_colaborador']) ? mainModel::cleanString($_POST['telefono_colaborador']) : "";
            $fecha_ingreso = isset($_POST['fecha_ingreso_colaborador']) ? mainModel::cleanString($_POST['fecha_ingreso_colaborador']) : "";
            $puesto_id = isset($_POST['puesto_colaborador']) ? mainModel::cleanString($_POST['puesto_colaborador']) : "";
            $empresa_id_colab = $empresa_id_sesion;

            $fecha_registro = date("Y-m-d H:i:s");    
            $estado = 1;

            // Si la identidad está vacía, generamos una única
            if (empty($identidad) || $identidad == "0") {
                do {
                    $identidad = "C-" . rand(10000000, 99999999);
                } while (usuarioModelo::valid_colaborador_modelo($identidad)->num_rows > 0);
            }  
                  
            if (empty($nombre)) {
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error",
                    "text" => "El nombre no debe estar vacío"
                ]);
            }
            
            if (empty($puesto_id) || $puesto_id == 0) {
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error",
                    "text" => "El puesto no debe estar vacío"
                ]);
            }            

            // Validar identidad única
            if(!empty($identidad)) {
                $result_identidad = mainModel::ejecutar_consulta_simple("
                    SELECT colaboradores_id 
                    FROM colaboradores 
                    WHERE identidad = '$identidad'
                    LIMIT 1
                ");
                
                if($result_identidad && $result_identidad->num_rows > 0) {
                    return mainModel::showNotification([
                        "type" => "error",
                        "title" => "Error",
                        "text" => "Ya existe un colaborador con esta identidad"
                    ]);
                }
            }
            
            // Crear nuevo colaborador
            $datos_colaborador = [
                "nombre" => $nombre,              
                "identidad" => $identidad,
                "telefono" => $telefono,                
                "puesto" => $puesto_id,                
                "estado" => $estado,
                "fecha_registro" => $fecha_registro,    
                "empresa" => $empresa_id_colab,
                "fecha_ingreso" => $fecha_ingreso,    
                "fecha_egreso" => ""  
            ];
            
            $colaborador_id = usuarioModelo::agregar_colaborador_modelo($datos_colaborador);

            if(!$colaborador_id) {
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error",
                    "text" => "No se pudo guardar el colaborador"
                ]);
            }

        } else {

            // Usar colaborador existente
            $colaborador_id = isset($_POST['colaboradores_id']) ? mainModel::cleanString($_POST['colaboradores_id']) : "";
            
            if(empty($colaborador_id)) {
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error",
                    "text" => "Debe seleccionar un colaborador existente"
                ]);
            }
        }
        
        // Datos del usuario
        $privilegio_id = isset($_POST['privilegio_id']) ? mainModel::cleanString($_POST['privilegio_id']) : "";            
        $pass = mainModel::generar_password_complejo();
        $contraseña_generada = mainModel::encryption($pass);    
        $correo_usuario = isset($_POST['correo_usuario']) ? mainModel::cleanStringStrtolower($_POST['correo_usuario']) : "";
        $empresa = isset($_POST['empresa_usuario']) ? mainModel::cleanString($_POST['empresa_usuario']) : $empresa_id_sesion;
        $tipo_user = isset($_POST['tipo_user']) ? mainModel::cleanString($_POST['tipo_user']) : "";            
        $estado = isset($_POST['estado_usuario']) ? 1 : 2;    

        if ($correo_usuario == "" || !filter_var($correo_usuario, FILTER_VALIDATE_EMAIL)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Correo inválido",
                "text" => "Debe ingresar un correo de usuario válido."
            ]);
        }

        if (empty($privilegio_id) || $privilegio_id == 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Debe seleccionar un privilegio para el usuario."
            ]);
        }

        if (empty($tipo_user) || $tipo_user == 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Debe seleccionar el tipo de usuario."
            ]);
        }
    
        // Validar correo duplicado
        if(usuarioModelo::valid_correo_modelo($correo_usuario)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Lo sentimos este correo ya ha sido registrado, por favor corregir"
            ]);
        }
        
        // Obtener configuración del plan
        $planConfig = usuarioModelo::getPlanConfiguracion();

        // Solo validar si existe plan configurado
		if (isset($planConfig['usuarios'])) {
            $limiteBase = (int)($planConfig['usuarios'] ?? 0);
            $usuariosExtras = (int)usuarioModelo::getTotalUsuariosExtras();
            $limiteTotal = $limiteBase + $usuariosExtras;
            $totalUsuarios = (int)usuarioModelo::getTotalUsuarios();

            // Caso 1: Límite base es 0
            if ($limiteBase === 0) {
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan no incluye la creación de usuarios."
                ]);
            }

            // Caso 2: Validar límite total
            if ($totalUsuarios >= $limiteTotal) {
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Límite de usuarios excedido (Máximo: $limiteBase + $usuariosExtras extras)."
                ]);
            }
		}
        
        // Validar que el colaborador no tenga usuario
        if(usuarioModelo::valid_user_modelo($colaborador_id)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Lo sentimos este colaborador ya tiene un usuario registrado"
            ]);
        }
        
        // Datos para crear el usuario
        $datos_usuario = [
            "colaborador_id" => $colaborador_id,
            "privilegio_id" => $privilegio_id,                
            "pass" => $contraseña_generada,                
            "email" => $correo_usuario,                
            "tipo_user" => $tipo_user,                
            "estado" => $estado,
            "empresa" => $empresa,
            "server_customers_id" => $server_customers_id
        ];
        
        // Crear usuario
        $usuario_id = usuarioModelo::agregar_usuario_modelo($datos_usuario);
                
        if($usuario_id) {

            // Guardar en DB principal si es necesario
            if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
                $this->guardarUsuarioEnDBPrincipal($colaborador_id, $correo_usuario, $contraseña_generada, $server_customers_id);
            }
            
            // Enviar correo de bienvenida al usuario creado
            $this->enviarCorreoBienvenida(
                $correo_usuario,
                $pass,
                $privilegio_id,
                $empresa_id_sesion,
                $users_id,
                $sendEmail,
                $colaborador_id
            );

            $colaboradorDataNoti = usuarioModelo::get_colaborador_info($colaborador_id);
            $nombreNuevoUsuario = trim((string)($colaboradorDataNoti['nombre'] ?? 'Usuario'));
            $this->notificarAuditoriaUsuario(
                'Nuevo usuario creado',
                'Se creó un nuevo usuario y las credenciales fueron enviadas únicamente al usuario correspondiente.',
                [
                    'Usuario'=>$correo_usuario,
                    'Nombre'=>$nombreNuevoUsuario,
                    'Privilegio'=>$this->obtenerNombreCatalogoUsuario('privilegio','privilegio_id',$privilegio_id,'#'.$privilegio_id),
                    'Tipo de usuario'=>$this->obtenerNombreCatalogoUsuario('tipo_user','tipo_user_id',$tipo_user,'#'.$tipo_user),
                    'Empresa'=>$this->obtenerNombreCatalogoUsuario('empresa','empresa_id',$empresa,'#'.$empresa),
                    'Estado'=>(int)$estado === 1 ? 'Activo' : 'Inactivo'
                ],
                [],
                'success'
            );
            
            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro exitoso",
                "text" => "Usuario registrado correctamente",
                "form" => "formUsers",
                "funcion" => "listar_usuarios();"
            ]);

        } else {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo registrar el usuario"
            ]);
        }
    }
    
    /*----------- Controlador para editar usuario -----------*/
    public function edit_user_controlador(){

        // Validar sesión primero
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $usuarios_id = isset($_POST['usuarios_id']) ? mainModel::cleanString($_POST['usuarios_id']) : "";
    
        $correo = isset($_POST['correo_usuario']) ? mainModel::cleanStringStrtolower($_POST['correo_usuario']) : "";
        $tipo_user = isset($_POST['tipo_user']) ? mainModel::cleanString($_POST['tipo_user']) : "";
        $privilegio_id = isset($_POST['privilegio_id']) ? mainModel::cleanString($_POST['privilegio_id']) : "";
        $empresa_usuario = isset($_POST['empresa_usuario']) ? mainModel::cleanString($_POST['empresa_usuario']) : "";
        $server_customers_id = isset($_POST['server_customers_id']) ? mainModel::cleanString($_POST['server_customers_id']) : "";            
        $estado = isset($_POST['estado_usuario']) ? 1 : 2;    

        if ($usuarios_id == "") {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se recibió el ID del usuario."
            ]);
        }

        $usuarioAnterior = $this->obtenerDetalleUsuarioNotificacion($usuarios_id);

        if ($correo == "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Correo inválido",
                "text" => "Debe ingresar un correo válido."
            ]);
        }
        
        $datos = [
            "usuarios_id" => $usuarios_id,                
            "email" => $correo,                
            "tipo_user" => $tipo_user,    
            "privilegio_id" => $privilegio_id,    
            "empresa_id" => $empresa_usuario,
            "estado" => $estado                
        ];
        
        if(usuarioModelo::edit_user_modelo($datos)) {    

            if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
                $correoAnteriorMain = mainModel::cleanStringStrtolower($usuarioAnterior['email'] ?? $correo);
                $conexionMain = mainModel::connectionLogin();
                $stmtMain = $conexionMain->prepare("
                    UPDATE users 
                    SET estado = ?
                    WHERE email = ?
                      AND server_customers_id = ?
                ");
                if ($stmtMain) {
                    $estadoMain = (int)$estado;
                    $serverMain = (int)$server_customers_id;
                    $stmtMain->bind_param("isi", $estadoMain, $correoAnteriorMain, $serverMain);
                    $stmtMain->execute();
                    $stmtMain->close();
                }
            }

            $usuarioNuevo = $this->obtenerDetalleUsuarioNotificacion($usuarios_id);
            $cambiosUsuario = [];
            if ($usuarioAnterior) {
                if (strtolower(trim((string)($usuarioAnterior['email'] ?? ''))) !== strtolower(trim((string)$correo))) {
                    $cambiosUsuario['Correo'] = ['anterior'=>$usuarioAnterior['email'] ?? '', 'nuevo'=>$correo];
                }
                if ((int)($usuarioAnterior['privilegio_id'] ?? 0) !== (int)$privilegio_id) {
                    $cambiosUsuario['Privilegio'] = [
                        'anterior'=>$usuarioAnterior['privilegio'] ?? ('#'.($usuarioAnterior['privilegio_id'] ?? '')),
                        'nuevo'=>$this->obtenerNombreCatalogoUsuario('privilegio','privilegio_id',$privilegio_id,'#'.$privilegio_id)
                    ];
                }
                if ((int)($usuarioAnterior['tipo_user_id'] ?? 0) !== (int)$tipo_user) {
                    $cambiosUsuario['Tipo / permisos'] = [
                        'anterior'=>$usuarioAnterior['tipo_usuario'] ?? ('#'.($usuarioAnterior['tipo_user_id'] ?? '')),
                        'nuevo'=>$this->obtenerNombreCatalogoUsuario('tipo_user','tipo_user_id',$tipo_user,'#'.$tipo_user)
                    ];
                }
                if ((int)($usuarioAnterior['empresa_id'] ?? 0) !== (int)$empresa_usuario) {
                    $cambiosUsuario['Empresa'] = [
                        'anterior'=>$usuarioAnterior['empresa'] ?? ('#'.($usuarioAnterior['empresa_id'] ?? '')),
                        'nuevo'=>$this->obtenerNombreCatalogoUsuario('empresa','empresa_id',$empresa_usuario,'#'.$empresa_usuario)
                    ];
                }
                if ((int)($usuarioAnterior['estado'] ?? 0) !== (int)$estado) {
                    $cambiosUsuario['Estado'] = [
                        'anterior'=>(int)($usuarioAnterior['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo',
                        'nuevo'=>(int)$estado === 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreUsuario = trim((string)($usuarioNuevo['nombre'] ?? $usuarioAnterior['nombre'] ?? 'Usuario'));
            $correoDestino = trim((string)($usuarioNuevo['email'] ?? $correo));
            if ($cambiosUsuario) {
                $this->notificarUsuarioDirecto(
                    $correoDestino,
                    $nombreUsuario,
                    'Tu usuario IZZY fue actualizado',
                    'Se realizaron cambios en la configuración de tu usuario.',
                    ['Usuario'=>$correoDestino],
                    $cambiosUsuario,
                    'security',
                    false,
                    'Si no reconoce este cambio, comuníquese con el administrador de su empresa.'
                );
            }
            $this->notificarAuditoriaUsuario(
                'Usuario actualizado',
                'Se actualizó un usuario del sistema.',
                ['Usuario'=>$correoDestino, 'Nombre'=>$nombreUsuario],
                $cambiosUsuario,
                'audit'
            );

            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro exitoso",
                "text" => "Usuario actualizado correctamente",
                "funcion" => "listar_usuarios();"
            ]);

        } else {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo actualizar el usuario"
            ]);
        }
    }

    /*----------- Controlador para eliminar usuario -----------*/
    public function delete_user_controlador(){

        // Validar sesión primero
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return json_encode([
                "status" => "error",
                "title" => "Error de sesión",
                "message" => $validacion['mensaje']
            ]);
        }

        $usuarios_id = isset($_POST['users_id']) ? mainModel::cleanString($_POST['users_id']) : "";
        
        if ($usuarios_id == "") {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se recibió el usuario a eliminar."
            ]);
        }

        // Validar si existe el usuario
        $usuario_info = usuarioModelo::get_usuario_info($usuarios_id);

        if(!$usuario_info) {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Usuario no encontrado"
            ]);
        }

        // Validar si el usuario tiene registros en bitácora
        if(usuarioModelo::valid_user_bitacora($usuarios_id)) {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se puede eliminar porque el usuario tiene registros en bitácora"
            ]);
        }

        // Intentar eliminar
        if(usuarioModelo::delete_user_modelo($usuarios_id)) {

            if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
                $correoEliminar = mainModel::cleanStringStrtolower($usuario_info['email'] ?? '');
                $serverEliminar = (int)($usuario_info['server_customers_id'] ?? 0);
                if ($correoEliminar !== '' && $serverEliminar > 0) {
                    $stmtMain = mainModel::connectionLogin()->prepare("DELETE FROM users WHERE email = ? AND server_customers_id = ?");
                    if ($stmtMain) {
                        $stmtMain->bind_param("si", $correoEliminar, $serverEliminar);
                        $stmtMain->execute();
                        $stmtMain->close();
                    }
                }
            }

            if (!empty($usuario_info['email'])) {
                $this->notificarUsuarioDirecto(
                    $usuario_info['email'],
                    $usuario_info['nombre'] ?? 'Usuario',
                    'Tu usuario IZZY fue eliminado',
                    'Tu cuenta de usuario fue eliminada del sistema.',
                    ['Usuario'=>$usuario_info['email']],
                    [],
                    'warning',
                    false,
                    'Si considera que esta acción no corresponde, comuníquese con el administrador de su empresa.'
                );
            }
            $this->notificarAuditoriaUsuario(
                'Usuario eliminado',
                'Se eliminó un usuario del sistema.',
                ['Usuario'=>$usuario_info['email'] ?? '', 'Nombre'=>$usuario_info['nombre'] ?? ''],
                [],
                'audit'
            );

            return json_encode([
                "status" => "success",
                "title" => "Eliminado",
                "message" => "Usuario eliminado correctamente",
                "funcion" => "listar_usuarios();"
            ]);

        } else {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el usuario"
            ]);
        }
    }

    /*----------- Controlador para resetear contraseña -----------*/
    public function resetear_contrasena_controlador() {

        // Validar sesión primero
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return json_encode([
                "status" => "error",
                "title" => "Error de sesión",
                "message" => $validacion['mensaje']
            ]);
        }

        $users_id = isset($_POST['users_id']) ? mainModel::cleanString($_POST['users_id']) : "";
        $server_customers_id = isset($_POST['server_customers_id']) ? mainModel::cleanString($_POST['server_customers_id']) : "";
        
        if ($users_id == "") {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se recibió el usuario para restablecer contraseña."
            ]);
        }

        // Generar nueva contraseña
        $nueva_pass = mainModel::generar_password_complejo();
        $pass_encriptada = mainModel::encryption($nueva_pass);
        
        // Actualizar contraseña
        if(usuarioModelo::resetear_password_modelo($users_id, $pass_encriptada)) {

            // Obtener información del usuario para enviar correo y sincronizar el espejo.
            $info_usuario = $this->obtenerInfoUsuarioParaCorreo($users_id);

            // Actualizar en la base de datos principal si es necesario.
            // Se identifica por correo + cliente porque el users_id local puede ser distinto al de DB_MAIN.
            if($GLOBALS['db'] !== $GLOBALS['DB_MAIN'] && $info_usuario && !empty($info_usuario['email'])) {
                $conexionMain = mainModel::connectionLogin();
                $stmtMain = $conexionMain->prepare("
                    UPDATE users
                    SET password = ?
                    WHERE email = ?
                      AND server_customers_id = ?
                ");
                if ($stmtMain) {
                    $serverMain = (int)$server_customers_id;
                    $correoMain = mainModel::cleanStringStrtolower($info_usuario['email']);
                    $stmtMain->bind_param("ssi", $pass_encriptada, $correoMain, $serverMain);
                    $stmtMain->execute();
                    $stmtMain->close();
                }
            }

            // Enviar la nueva credencial únicamente al usuario afectado.
            if($info_usuario && !empty($info_usuario['email'])) {
                $sendEmail = new sendEmail();

                $this->enviarCorreoResetPassword(
                    $info_usuario['email'],
                    $nueva_pass,
                    $info_usuario['nombre'],
                    $sendEmail
                );
                $this->notificarAuditoriaUsuario(
                    'Contraseña de usuario restablecida',
                    'Se restableció la contraseña de un usuario. La nueva credencial fue enviada únicamente al usuario correspondiente.',
                    ['Usuario'=>$info_usuario['email'], 'Nombre'=>$info_usuario['nombre'] ?? ''],
                    [],
                    'security'
                );
            }
            
            return json_encode([
                "status" => "success",
                "title" => "Contraseña restablecida",
                "message" => "La contraseña ha sido restablecida correctamente"
            ]);

        } else {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo restablecer la contraseña"
            ]);
        }
    }

    /*----------- Funciones privadas auxiliares -----------*/
    private function guardarUsuarioEnDBPrincipal($colaborador_id, $correo, $password, $server_customers_id){

        // Obtener datos del colaborador local
        $colaborador = usuarioModelo::get_colaborador_info($colaborador_id);
        
        if(!$colaborador) {
            return false;
        }
        
        $conexion_main = mainModel::connectionLogin();
        
        try {
            $conexion_main->autocommit(false);
            
            // Insertar colaborador en DB principal
            $colaboradores_id_main = mainModel::correlativoLogin("colaboradores_id", "colaboradores");
            $puestos_id_default = 5;
            
            $stmt_colab = $conexion_main->prepare("
                INSERT INTO colaboradores 
                (
                    colaboradores_id,
                    puestos_id,
                    nombre,
                    identidad,
                    estado,
                    telefono,
                    empresa_id,
                    fecha_registro,
                    fecha_ingreso
                ) 
                VALUES (?, ?, ?, ?, 1, ?, 1, NOW(), NOW())
            ");
            
            $stmt_colab->bind_param("iisss", 
                $colaboradores_id_main,
                $puestos_id_default,
                $colaborador['nombre'],
                $colaborador['identidad'],
                $colaborador['telefono']
            );
            
            if(!$stmt_colab->execute()) {
                throw new Exception("Error al guardar colaborador en DB principal");
            }
            
            // Insertar usuario en DB principal
            $privilegio_id_default = 4;
            $tipo_user_default = 4;
            $users_id_main = mainModel::correlativoLogin("users_id", "users");
            
            $stmt_user = $conexion_main->prepare("
                INSERT INTO users 
                (
                    users_id,
                    colaboradores_id,
                    privilegio_id,
                    password,
                    email,
                    tipo_user_id,
                    estado,
                    fecha_registro,
                    empresa_id,
                    server_customers_id
                ) 
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), 1, ?)
            ");
            
            $stmt_user->bind_param("iiissii", 
                $users_id_main,
                $colaboradores_id_main,
                $privilegio_id_default,
                $password,
                $correo,
                $tipo_user_default,
                $server_customers_id
            );
            
            if(!$stmt_user->execute()) {
                throw new Exception("Error al guardar usuario en DB principal");
            }
            
            $conexion_main->commit();
            return true;
            
        } catch(Exception $e) {
            $conexion_main->rollback();
            return false;

        } finally {
            $conexion_main->autocommit(true);
        }
    }
    
    private function enviarCorreoBienvenida($correo_usuario, $pass, $privilegio_id, $empresa_id, $users_id, $sendEmail, $colaborador_id){
        $colaboradorData = usuarioModelo::get_colaborador_info($colaborador_id);
        $colaborador_nombre = $colaboradorData ? trim((string)$colaboradorData['nombre']) : "Usuario";
        $privilegioData = usuarioModelo::get_privilegio_info($privilegio_id);
        $privilegio_nombre = $privilegioData ? trim((string)$privilegioData['nombre']) : "";
        $empresaData = usuarioModelo::get_empresa_info($empresa_id);
        $empresa_nombre = $empresaData ? trim((string)$empresaData['nombre']) : "Empresa";

        return $this->notificarUsuarioDirecto(
            $correo_usuario,
            $colaborador_nombre,
            'Tu usuario IZZY fue creado',
            'Tu cuenta fue creada correctamente. Utiliza estas credenciales para ingresar al sistema.',
            [
                'Usuario'=>$correo_usuario,
                'Contraseña temporal'=>$pass,
                'Privilegio'=>$privilegio_nombre,
                'Empresa'=>$empresa_nombre
            ],
            [],
            'security',
            true,
            'Por seguridad, cambia tu contraseña después del primer inicio de sesión y no compartas tus credenciales.'
        );
    }

    private function enviarCorreoResetPassword($correo, $nueva_pass, $nombre_usuario, $sendEmail) {
        return $this->notificarUsuarioDirecto(
            $correo,
            $nombre_usuario,
            'Restablecimiento de contraseña',
            'Tu contraseña de IZZY fue restablecida correctamente.',
            [
                'Usuario'=>$correo,
                'Contraseña temporal'=>$nueva_pass
            ],
            [],
            'security',
            true,
            'Por seguridad, cambia esta contraseña después de iniciar sesión y no la compartas con nadie.'
        );
    }

    private function obtenerInfoUsuarioParaCorreo($users_id) {
        return usuarioModelo::get_usuario_info($users_id);
    }
    
}