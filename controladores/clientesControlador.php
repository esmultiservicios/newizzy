<?php
if($peticionAjax){
    require_once "../modelos/clientesModelo.php";
}else{
    require_once "./modelos/clientesModelo.php";
}

class clientesControlador extends clientesModelo{
    public function agregar_clientes_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
            
        $nombre = mainModel::cleanString($_POST['nombre_clientes']);
        $rtn = mainModel::cleanString($_POST['identidad_clientes']);
        $fecha = mainModel::cleanString($_POST['fecha_clientes']);            
        $departamento_id = isset($_POST['departamento_cliente']) ? intval($_POST['departamento_cliente']) : 0;
        $municipio_id = isset($_POST['municipio_cliente']) ? intval($_POST['municipio_cliente']) : 0;                    
        $localidad = mainModel::cleanString($_POST['dirección_clientes']);
        $telefono = mainModel::cleanString($_POST['telefono_clientes']);
        $correo = mainModel::cleanStringStrtolower($_POST['correo_clientes']);
        $estado_clientes = 1;
        $empresa = "";
        
        $colaborador_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");

        $datos = [
            "nombre" => $nombre,
            "rtn" => $rtn,
            "fecha" => $fecha,
            "departamento_id" => $departamento_id,
            "municipio_id" => $municipio_id,
            "localidad" => $localidad,
            "telefono" => $telefono,
            "correo" => $correo,
            "estado_clientes" => $estado_clientes,
            "colaborador_id" => $colaborador_id,
            "fecha_registro" => $fecha_registro,            
            "empresa" => $empresa
        ];
        
        $query = clientesModelo::agregar_clientes_modelo($datos);
        
        if($query){
            //GUARDAR HISTORIAL
            $datos = [
                "modulo" => 'Clientes',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],        
                "status" => "Registro",
                "observacion" => "Se registro el cliente {$nombre} con el RTN {$rtn}",
                "fecha_registro" => date("Y-m-d H:i:s")
            ];    
            
            mainModel::guardarHistorial($datos);

            $alert = [
                "title" => "Registro almacenado",
                "text" => "El registro se ha almacenado correctamente",
                "type" => "success",
                "form" => "formClientes",
                "funcion" => "listar_clientes();getDepartamentoClientes();getMunicipiosClientes(0);listar_clientes_factura_buscar();listar_clientes_cotizacion_buscar();listar_colaboradores_buscar_compras();"
            ];
            
            return mainModel::showNotification($alert);
        }else{
            $alert = [
                "title" => "Ocurrio un error inesperado",
                "text" => "No hemos podido procesar su solicitud",
                "type" => "error"
            ];                
            
            return mainModel::showNotification($alert);
        }            
    }

    public function registrar_cliente_autonomo_controlador() {       
        // Verificar que todos los campos POST estén presentes
        $required = ['user_empresa', 'user_name', 'user_telefono', 'email', 'user_pass'];
        foreach ($required as $field) {
            if (!isset($_POST[$field])) { // Paréntesis faltante corregido
                http_response_code(400);
                echo json_encode([
                    'estado' => false,
                    'type'=> 'error',
                    'title' => 'Campos faltantes',
                    'mensaje' => 'Faltan campos obligatorios'                    
                ]);
                exit;
            }
        }
    
        // Limpiar datos
        $empresa = mainModel::cleanString($_POST['user_empresa']);
        $nombre = mainModel::cleanString($_POST['user_name']);
        $telefono = mainModel::cleanString($_POST['user_telefono']);
        $correo = mainModel::cleanStringStrtolower($_POST['email']);
        $password = mainModel::cleanString($_POST['user_pass']);
        
        // Validaciones básicas
        if (empty($nombre) || empty($empresa) || empty($telefono) || empty($correo) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'estado' => false,
                'type'=> 'error',
                'title' => 'Campos faltantes',                
                'mensaje' => 'Todos los campos son obligatorios'
            ]);
            exit;
        }
        
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'estado' => false,
                'type'=> 'error',
                'title' => 'Campos faltantes',                
                'mensaje' => 'El correo electrónico no es válido'
            ]);
            exit;
        }
        
        // Verificar correo único - USANDO mysqli_num_rows EN LUGAR DE rowCount()
        $check_email = mainModel::ejecutar_consulta_simple("SELECT clientes_id FROM clientes WHERE correo = '$correo'");
        if ($check_email->num_rows > 0) { // Cambiado rowCount() por num_rows
            http_response_code(400);
            echo json_encode([
                'estado' => false,
                'type'=> 'error',
                'title' => 'Campos faltantes',                
                'mensaje' => 'Este correo electrónico ya está registrado'
            ]);
            exit;
        }
        
        // Configurar datos del cliente
        $datos_cliente = [
            "nombre" => $nombre,
            "rtn" => "",
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
        
        // Registrar el cliente principal
        $clientes_id = clientesModelo::agregar_clientes_modelo($datos_cliente);
        if (!$clientes_id) {
            http_response_code(500);
            echo json_encode([
                'estado' => false,
                'type'=> 'error',
                'title' => 'Error',                
                'mensaje' => 'Error al registrar cliente principal'
            ]);
            exit;
        }

        // Generar nombre de BD según tu requerimiento
        $nombre_db = mainModel::generateDatabaseName($empresa);

        // Obtener el próximo server_customers_id
        $resultado = mainModel::ejecutar_consulta_simple("SELECT MAX(server_customers_id) as ultimo_id FROM server_customers");
        $fila = $resultado->fetch_assoc();
        $server_customers_id = ($fila && $fila['ultimo_id']) ? $fila['ultimo_id'] + 1 : 1;

        // Generar código de cliente único
        $codigo_cliente = mainModel::generarCodigoUnico($clientes_id);

        // Verificar que el código no exista (por si hay colisión)
        $verificar = mainModel::ejecutar_consulta_simple("SELECT COUNT(*) as existe FROM server_customers WHERE codigo_cliente = '$codigo_cliente'");
        if ($verificar->fetch_assoc()['existe'] > 0) {
            // Si existe, generamos uno alternativo
            $codigo_cliente = (int)(date('Ymd') . substr($clientes_id, -4));
        }

        // Valores fijos según tu estructura
        $planes_id = 1;
        $sistema_id = 1;
        $validar = 1; // 1 = Sí
        $estado = 1; // 1 = Activo

        // Insertar en server_customers con TODOS los campos
        $insert = "INSERT INTO server_customers 
                (server_customers_id, clientes_id, codigo_cliente, db, planes_id, sistema_id, validar, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mainModel::connection()->prepare($insert);
        $stmt->bind_param("iiisiiii", 
            $server_customers_id,
            $clientes_id,
            $codigo_cliente,
            $nombre_db,
            $planes_id,
            $sistema_id,
            $validar,
            $estado
        );

        if ($stmt->execute()) {
            echo json_encode([
                'estado' => true,
                'type'=> 'info',
                'title' => 'DB generada',                
                'mensaje' => 'Su base de datos '.$nombre_db.' se ha registrado correctamente.',
                'datos' => [
                    'server_customers_id' => $server_customers_id,
                    'clientes_id' => $clientes_id,
                    'codigo_cliente' => $codigo_cliente,
                    'nombre_db' => $nombre_db,
                    'planes_id' => $planes_id,
                    'sistema_id' => $sistema_id,
                    'validar' => $validar,
                    'estado' => $estado
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'estado' => false,
                'type'=> 'error',
                'title' => 'Error',                
                'mensaje' => 'Error al registrar en server_customers',
                'error' => $stmt->error
            ]);
        }
        
        // Respuesta exitosa
        echo json_encode([
            'estado' => true,
            'type'=> 'success',
            'title' => 'Exito',              
            'mensaje' => 'Registro completado exitosamente',
            'email' => $correo
        ]);
        exit;
    }
    
    public function edit_clientes_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
        
        $clientes_id = $_POST['clientes_id'];
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_clientes']);
        $rtn = mainModel::cleanString($_POST['identidad_clientes']);            
        $departamento_id = isset($_POST['departamento_cliente']) ? intval($_POST['departamento_cliente']) : 0;
        $municipio_id = isset($_POST['municipio_cliente']) ? intval($_POST['municipio_cliente']) : 0;
        $localidad = mainModel::cleanString($_POST['dirección_clientes']);
        $telefono = mainModel::cleanString($_POST['telefono_clientes']);
        $correo = mainModel::cleanStringStrtolower($_POST['correo_clientes']);
        
        if (isset($_POST['clientes_activo'])){
            $estado = $_POST['clientes_activo'];
        }else{
            $estado = 2;
        }            
        
        $datos = [
            "clientes_id" => $clientes_id,
            "nombre" => $nombre,
            "rtn" => $rtn,
            "departamento_id" => $departamento_id,
            "municipio_id" => $municipio_id,
            "localidad" => $localidad,
            "telefono" => $telefono,
            "correo" => $correo,
            "estado" => $estado
        ];            
                    
        $query = clientesModelo::edit_clientes_modelo($datos);
        
        if($query){    
            //GUARDAR HISTORIAL
            $datos = [
                "modulo" => 'Clientes',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],        
                "status" => "Edición",
                "observacion" => "Se edito el cliente {$nombre} con el RTN {$rtn}",
                "fecha_registro" => date("Y-m-d H:i:s")
            ];    
            
            mainModel::guardarHistorial($datos);

            $alert = [
                "title" => "Registro modificado",
                "text" => "El registro se ha modificado correctamente",
                "type" => "success",
                "form" => "formClientes",
                "funcion" => "listar_clientes();getDepartamentoClientes();getMunicipiosClientes(0);"
            ];
            
            return mainModel::showNotification($alert);
        }else{
            $alert = [
                "title" => "Ocurrio un error inesperado",
                "text" => "No hemos podido procesar su solicitud",
                "type" => "error"
            ];                
            
            return mainModel::showNotification($alert);
        }
    }
    
    public function delete_clientes_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
        
        $clientes_id = $_POST['clientes_id'];
        
        $campos = ['nombre', 'rtn'];
        $resultados = mainModel::consultar_tabla('clientes', $campos, "clientes_id = {$clientes_id}");
        
        if (!empty($resultados)) {
            $primerResultado = $resultados[0];
            $nombre = isset($primerResultado['nombre']) ? $primerResultado['nombre'] : null;
            $rtn = isset($primerResultado['rtn']) ? $primerResultado['rtn'] : null;
        } else {
            $nombre = null;
            $rtn = null;
        }
                        
        $result_valid_clientes = clientesModelo::valid_clientes_facturas_modelo($clientes_id);
        
        if($result_valid_clientes->num_rows==0){
            $query = clientesModelo::delete_clientes_modelo($clientes_id);
                            
            if($query){
                //GUARDAR HISTORIAL
                $datos = [
                    "modulo" => 'Clientes',
                    "colaboradores_id" => $_SESSION['colaborador_id_sd'],        
                    "status" => "Eliminar",
                    "observacion" => "Se elimino el cliente {$nombre} con el RTN {$rtn}",
                    "fecha_registro" => date("Y-m-d H:i:s")
                ];    
                
                mainModel::guardarHistorial($datos);
    
                header('Content-Type: application/json');
                echo json_encode([
                    "status" => "success",
                    "title" => "Registro eliminado",
                    "message" => "El cliente {$nombre} ha sido eliminado correctamente",
                ]);
                exit();
            }else{
                header('Content-Type: application/json');
                echo json_encode([
                    "status" => "error",
                    "title" => "Error",
                    "message" => "No se pudo eliminar el cliente {$nombre}"
                ]);
                exit();                
            }                
        }else{
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "No se puede eliminar",
                "message" => "El cliente {$nombre} tiene facturas asociadas y no puede ser eliminado"
            ]);
            exit();                
        }
    }
}