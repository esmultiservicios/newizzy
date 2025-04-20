<?php
if($peticionAjax){
    require_once "../modelos/usuarioModelo.php";
    require_once "../core/sendEmail.php";
}else{
    require_once "./modelos/usuarioModelo.php";
    require_once "./core/sendEmail.php";
}

class usuarioControlador extends usuarioModelo{
    /*----------- Controlador para agregar usuario -----------*/
    public function agregar_usuario_controlador() {
        if(!isset($_SESSION['user_sd'])) { 
            session_start(['name'=>'SD']); 
        }
    
        $sendEmail = new sendEmail();
        $users_id = $_SESSION['users_id_sd'];
    
        // Datos del colaborador (nuevo o existente)
        $es_nuevo_colaborador = mainModel::cleanString($_POST['es_nuevo_colaborador']);
        
        if($es_nuevo_colaborador == '1') {
            // Procesar nuevo colaborador
            $nombre = mainModel::cleanString($_POST['nombre_colaborador']);
            $apellido = mainModel::cleanString($_POST['apellido_colaborador']);
            $identidad = mainModel::cleanString($_POST['identidad_colaborador']);
            $telefono = mainModel::cleanString($_POST['telefono_colaborador']);
            $fecha_ingreso = mainModel::cleanString($_POST['fecha_ingreso_colaborador']);
            $puesto_id = mainModel::cleanString($_POST['puesto_colaborador']);
            
            // Validar identidad única
            $result_identidad = mainModel::ejecutar_consulta_simple("SELECT colaboradores_id FROM colaboradores WHERE identidad = '$identidad'");
            
            if($result_identidad->num_rows > 0) {
                return $this->crearAlertaSimple("Identidad duplicada", "Ya existe un colaborador con esta identidad", "error");
            }
            
            // Crear nuevo colaborador
            $colaborador_id = mainModel::correlativo("colaboradores_id", "colaboradores");
            $fecha_registro_colab = date("Y-m-d H:i:s");
            $estado_colab = 1;
            $empresa_id_colab = $_SESSION['empresa_id_sd'];
            
            $datos_colaborador = [
                "colaboradores_id" => $colaborador_id,
                "puestos_id" => $puesto_id,
                "nombre" => $nombre,
                "apellido" => $apellido,
                "identidad" => $identidad,
                "estado" => $estado_colab,
                "telefono" => $telefono,
                "empresa_id" => $empresa_id_colab,
                "fecha_registro" => $fecha_registro_colab,
                "fecha_ingreso" => $fecha_ingreso,
                "fecha_egreso" => ""
            ];
            
            $guardar_colaborador = mainModel::guardar_datos("colaboradores", $datos_colaborador);
            
            if(!$guardar_colaborador) {
                return $this->crearAlertaSimple("Error", "No se pudo guardar el colaborador", "error");
            }
        } else {
            // Usar colaborador existente
            $colaborador_id = mainModel::cleanString($_POST['colaboradores_id']);
        }
        
        // Datos del usuario
        $privilegio_id = mainModel::cleanString($_POST['privilegio_id']);            
        $nickname = "";    
        $pass = mainModel::generar_password_complejo();
        $contraseña_generada = mainModel::encryption($pass);    
        $correo_usuario = mainModel::cleanStringStrtolower($_POST['correo_usuario']);
        $empresa = mainModel::cleanString($_POST['empresa_usuario']);
        $tipo_user = mainModel::cleanString($_POST['tipo_user']);            
        $fecha_registro = date("Y-m-d H:i:s");
        $usuario_sistema = $_SESSION['colaborador_id_sd'];    
        $estado = isset($_POST['usuarios_activo']) ? 1 : 2;    
        $server_customers_id = $_SESSION['server_customers_id']; 
    
        // Validar correo duplicado
        $result_correo_usuario = usuarioModelo::valid_correo_modelo($correo_usuario);
    
        if($result_correo_usuario->num_rows == 0) {
            // Obtener configuración del plan
            $planConfig = usuarioModelo::getPlanConfiguracion();
            $limiteUsuarios = isset($planConfig['usuarios']) ? (int)$planConfig['usuarios'] : 0;
    
            // Obtener el total de usuarios extras
            $usuariosConfig = usuarioModelo::getTotalUsuariosExtras()->fetch_assoc();
            $usuariosExtras = isset($usuariosConfig['user_extra']) ? (int)$usuariosConfig['user_extra'] : 0;
    
            // Calcular el total de usuarios
            $totalLimiteUsuarios = $limiteUsuarios + $usuariosExtras;
    
            // Obtener total de usuarios activos
            $cantidad_usuario_sistema = usuarioModelo::getTotalUsuarios()->fetch_assoc();
            $total_usuarios_sistema = $cantidad_usuario_sistema['total_usuarios'];
            
            // Validar límite del plan (0 = ilimitado)
            if($limiteUsuarios == 0 || $total_usuarios_sistema < $totalLimiteUsuarios) {
                $result_usuario = usuarioModelo::valid_user_modelo($colaborador_id);
                
                if($result_usuario->num_rows == 0) {
                    $datos = [
                        "colaborador_id" => $colaborador_id,
                        "privilegio_id" => $privilegio_id,                
                        "nickname" => $nickname,
                        "pass" => $contraseña_generada,                
                        "correo" => $correo_usuario,                
                        "empresa" => $empresa,
                        "tipo_user" => $tipo_user,                
                        "estado" => $estado,
                        "fecha_registro" => $fecha_registro,                
                        "server_customers_id" => $server_customers_id
                    ];
                    
                    // Guardar en DB principal si es necesario
                    if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
                        $this->guardarUsuarioEnDBPrincipal($colaborador_id, $correo_usuario, $contraseña_generada, $fecha_registro, $server_customers_id);
                    }
    
                    $query = usuarioModelo::agregar_usuario_modelo($datos);
                    
                    if($query) {
                        $this->enviarCorreoBienvenida($correo_usuario, $pass, $privilegio_id, $empresa, $users_id, $sendEmail);
                        
                        $alert = [
                            "type" => "success",
                            "title" => "Registro almacenado",
                            "text" => "El registro se ha almacenado correctamente",                
                            "funcion" => "listar_usuarios();getTipoUsuario();getPrivilegio();getEmpresaUsers();getColaboradoresUsuario();",
                            "form" => "formUsers",
                        ];
                    } else {
                        $alert = $this->crearAlertaSimple("Ocurrio un error inesperado", "No hemos podido procesar su solicitud", "error");
                    }                
                } else {
                    $alert = $this->crearAlertaSimple("Resgistro ya existe", "Lo sentimos este registro ya existe", "error");
                }
            } else {
                $alert = $this->crearAlertaSimple("Límite de usuarios excedido", "Lo sentimos, ha excedido el límite de usuarios según su plan", "error");
            }
        } else {
            $alert = $this->crearAlertaSimple("Correo duplicado", "Lo sentimos este correo ya ha sido registrado, por favor corregir", "error");
        }
        
        return mainModel::showNotification($alert);
    }
    
    /*----------- Controlador para editar usuario -----------*/
    public function edit_user_controlador(){
        $usuarios_id = $_POST['usuarios_id'];
    
        $correo = mainModel::cleanStringStrtolower($_POST['correo_usuario']);
        $tipo_user = mainModel::cleanString($_POST['tipo_user']);
        $privilegio_id = mainModel::cleanString($_POST['privilegio_id']);
        $empresa_usuario = mainModel::cleanString($_POST['empresa_usuario']);
        $server_customers_id = $_POST['server_customers_id'];            

        if (isset($_POST['usuarios_activo'])){
            $estado = $_POST['usuarios_activo'];
        }else{
            $estado = 2;
        }    
        
        $datos = [
            "usuarios_id" => $usuarios_id,                
            "correo" => $correo,                
            "tipo_user" => $tipo_user,    
            "privilegio_id" => $privilegio_id,    
            "empresa_id" => $empresa_usuario,
            "estado" => $estado,                
        ];
        
        $query = usuarioModelo::edit_user_modelo($datos);
        
        if($query){    
            if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
                $updateDBMainUsers = "UPDATE users 
                    SET 
                        estado = '$estado'
                    WHERE email = '$correo' AND server_customers_id = '$server_customers_id'";
                
                mainModel::connectionLogin()->query($updateDBMainUsers);
            }

            $alert = [
                "type" => "success",
                "title" => "Registro modificado",
                "text" => "El registro se ha modificado correctamente",                
                "funcion" => "listar_usuarios();getTipoUsuario();getPrivilegio();getEmpresaUsers();getColaboradoresUsuario();"
            ];            
        }else{
            $alert = [
                "type" => "error",
                "title" => "Ocurrió un error inesperado",
                "text" => "No hemos podido procesar su solicitud"
            ];              
        }
        
        return mainModel::showNotification($alert);
    }

    /*----------- Controlador para eliminar usuario -----------*/
    public function delete_user_controlador(){
        $usuarios_id = $_POST['usuarios_id'];
        
        //Validar si existe el usuario
        $result_valid = usuarioModelo::valid_user_bitacora($usuarios_id);

        if (empty($result_valid)) {
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Colaborador no encontrado"
            ]);
            exit();
        }

        // Verificar si tiene registros asociados
        if($result_valid->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "title" => "Registro con información asociada",
                "message" => "No se puede eliminar porque tiene registros en bitácora"
            ]);
            exit();                
        }

        //Intentar eliminar
        $query = usuarioModelo::delete_user_modelo($usuarios_id);

        if($query) {
            if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
                $deleteDBMainUsers = "DELETE FROM users WHERE users_id = '$usuarios_id'";
                
                mainModel::connectionLogin()->query($deleteDBMainUsers);
            }

            echo json_encode([
                "status" => "success",
                "title" => "Eliminado",
                "message" => "Usuario eliminado correctamente",                    
                "funcion" => "listar_usuarios();getTipoUsuario();getPrivilegio();getEmpresaUsers();getColaboradoresUsuario();"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el colaborador"                    
            ]);
        }
        exit();
    }

    /*----------- Funciones privadas auxiliares -----------*/
    private function guardarUsuarioEnDBPrincipal($colaborador_id, $correo, $password, $fecha_registro, $server_customers_id){
        $query = "SELECT nombre, apellido, identidad, telefono FROM colaboradores WHERE colaboradores_id = '$colaborador_id'";
        $result = mainModel::connection()->query($query);
        $colaborador = $result->fetch_assoc();
        
        $colaboradores_id_consulta = mainModel::correlativoLogin("colaboradores_id", "colaboradores");
        $puestos_id_defualt = 5;
        $insertDBMainColaboradores = "INSERT INTO `colaboradores`(`colaboradores_id`, `puestos_id`, `nombre`, `apellido`, `identidad`, `estado`, `telefono`, `empresa_id`, `fecha_registro`, `fecha_ingreso`, `fecha_egreso`) VALUES ('$colaboradores_id_consulta','$puestos_id_defualt','".$colaborador['nombre']."','".$colaborador['apellido']."','".$colaborador['identidad']."','1','".$colaborador['telefono']."','1','$fecha_registro','$fecha_registro','')";
        
        mainModel::connectionLogin()->query($insertDBMainColaboradores);

        $privilegio_id_default = 4;
        $tipo_user_default = 4;
        $users_id_consulta = mainModel::correlativoLogin("users_id ", "users");
        $insertDBMainUsers = "INSERT INTO `users`(`users_id`, `colaboradores_id`, `privilegio_id`, `username`, `password`, `email`, `tipo_user_id`, `estado`, `fecha_registro`, `empresa_id`, `server_customers_id`) VALUES ('$users_id_consulta','$colaboradores_id_consulta','$privilegio_id_default','','$password','$correo','$tipo_user_default','1','$fecha_registro','1','$server_customers_id')";

        mainModel::connectionLogin()->query($insertDBMainUsers);
    }
    
    private function enviarCorreoBienvenida($correo_usuario, $pass, $privilegio_id, $empresa, $users_id, $sendEmail){
        $usuario_sistema = $_SESSION['colaborador_id_sd'];
        
        // Obtener datos del colaborador
        $queryColaborador = "SELECT nombre, apellido FROM colaboradores WHERE colaboradores_id = '$usuario_sistema'";
        $resultColaborador = mainModel::ejecutar_consulta($queryColaborador);
        $colaboradorData = $resultColaborador->fetch_assoc();
        $colaborador_nombre = !empty($colaboradorData) ? trim($colaboradorData['nombre'].' '.$colaboradorData['apellido']) : "";
        
        // Obtener datos del privilegio
        $queryPrivilegio = "SELECT nombre FROM privilegio WHERE privilegio_id = '$privilegio_id'";
        $resultPrivilegio = mainModel::ejecutar_consulta($queryPrivilegio);
        $privilegioData = $resultPrivilegio->fetch_assoc();
        $privilegio_nombre = !empty($privilegioData) ? trim($privilegioData['nombre']) : "";

        $empresa_id_sesion = $_SESSION['empresa_id_sd'];
        
        // Obtener datos de la empresa
        $queryEmpresa = "SELECT nombre FROM empresa WHERE empresa_id = '$empresa_id_sesion'";
        $resultEmpresa = mainModel::ejecutar_consulta($queryEmpresa);
        $empresaData = $resultEmpresa->fetch_assoc();
        $empresa_nombre = !empty($empresaData) ? strtoupper(trim($empresaData['nombre'])) : "";

        $correo_tipo_id = "1";
        $destinatarios = array($correo_usuario => $colaborador_nombre);
        $bccDestinatarios = $this->obtenerCorreosAdministradores($users_id);

        $asunto = "¡Bienvenido! Registro de Usuario Exitoso";
        $mensaje = '
            <div style="padding: 20px;">
                <p style="margin-bottom: 10px;">
                    ¡Hola '.$colaborador_nombre.'!
                </p>
                <p>Tu registro en el sistema de '.$empresa_nombre.' ha sido exitoso.</p>
                <p><strong>Tus credenciales de acceso son:</strong></p>
                <ul>
                    <li><strong>Usuario:</strong> '.$correo_usuario.'</li>
                    <li><strong>Contraseña temporal:</strong> '.$pass.'</li>
                    <li><strong>Privilegio:</strong> '.$privilegio_nombre.'</li>
                </ul>
                <p>Por seguridad, te recomendamos cambiar tu contraseña después del primer acceso.</p>
                <p>Atentamente,<br>El equipo de '.$empresa_nombre.'</p>
            </div>
        ';

        $archivos_adjuntos = [];
        $sendEmail->enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa, $archivos_adjuntos);
    }
    
    private function obtenerCorreosAdministradores($users_id){
        $bccDestinatarios = [];
        
        $result_correos_administradores = usuarioModelo::getCorrreosAdmin();
        
        if ($result_correos_administradores->num_rows > 0) {
            while ($row = $result_correos_administradores->fetch_assoc()) {
                $correo = $row["email"];
                $nombreCompleto = $row["nombre_completo"];
                if (!empty($correo)) {
                    $bccDestinatarios[$correo] = $nombreCompleto;
                }
            }
        }
        
        // Obtener datos del usuario revendedor
        $queryUsers = "SELECT email, colaboradores_id FROM users WHERE users_id = '$users_id' AND privilegio_id = 3";
        $resultUsers = mainModel::ejecutar_consulta($queryUsers);
        $usersData = $resultUsers->fetch_assoc();
        
        if (!empty($usersData)) {
            $correo_revendedor = $usersData['email'];
            $colaboradores_id_revendedor = $usersData['colaboradores_id'];
            
            // Obtener datos del colaborador revendedor
            $queryColaborador = "SELECT nombre, apellido FROM colaboradores WHERE colaboradores_id = '$colaboradores_id_revendedor'";
            $resultColaborador = mainModel::ejecutar_consulta($queryColaborador);
            $colaboradorData = $resultColaborador->fetch_assoc();
            
            if (!empty($colaboradorData)) {
                $nombre_revendedor = trim($colaboradorData['nombre'].' '.$colaboradorData['apellido']);
                $bccDestinatarios[$correo_revendedor] = $nombre_revendedor;
            }
        }
        
        return $bccDestinatarios;
    }
    
    private function crearAlertaSimple($title, $text, $type){
        return [
            "type" => $type,
            "title" => $title,
            "text" => $text                   
        ];
    }
}