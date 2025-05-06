<?php	
    $peticionAjax = true;
    require_once "configGenerales.php";
    require_once "mainModel.php";
    
    $insMainModel = new mainModel();	

    // Validar sesión primero
    $validacion = $insMainModel->validarSesion();
    if($validacion['error']) {
        return $insMainModel->showNotification([
            "title" => "Error de sesión",
            "text" => $validacion['mensaje'],
            "type" => "error",
            "funcion" => "window.location.href = '".$validacion['redireccion']."'"
        ]);
    }
    
    // Obtener conexión usando la instancia ya creada
    $conexion = $insMainModel->connection();
    
    try {
        // Consulta para obtener el privilegio
        $stmt = $conexion->prepare("SELECT nombre FROM privilegio WHERE privilegio_id = ?");
        $stmt->bind_param("i", $_SESSION['privilegio_sd']);
        $stmt->execute();
        $resultadoPrivilegio = $stmt->get_result();
        
        $privilegio_colaborador = "";
        
        if ($resultadoPrivilegio->num_rows > 0) {
            $row = $resultadoPrivilegio->fetch_assoc();
            $privilegio_colaborador = $row['nombre'];
        }
        
        $estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

        $datos = [
            "privilegio_id" => $_SESSION['privilegio_sd'],
            "colaborador_id" => $_SESSION['colaborador_id_sd'],	
            "privilegio_colaborador" => $privilegio_colaborador,	
            "empresa_id" => $_SESSION['empresa_id_sd'],
            "estado" => $estado
        ];	
        
        // Consulta para obtener empresas
        $result = $insMainModel->getEmpresa($datos);

        $arreglo = array();
        $data = array();
        
        while($row = $result->fetch_assoc()){				
            $data[] = array( 
                "empresa_id"=>$row['empresa_id'],
                "razon_social"=>$row['razon_social'],
                "nombre"=>$row['nombre'],
                "telefono"=>$row['telefono'],
                "correo"=>$row['correo'],
                "rtn"=>$row['rtn'],
                "ubicacion"=>$row['ubicacion'],		
                "image"=>$row['logotipo'],
                "estado"=>$row['estado']
            );		
        }
        
        $arreglo = array(
            "echo" => 1,
            "totalrecords" => count($data),
            "totaldisplayrecords" => count($data),
            "data" => $data
        );

        echo json_encode($arreglo);
        
    } catch(Exception $e) {
        // Manejar errores
        $arreglo = array(
            "echo" => 0,
            "error" => $e->getMessage()
        );
        echo json_encode($arreglo);
    } finally {
        // Cerrar conexión si es necesario
        if(isset($stmt)) {
            $stmt->close();
        }
        if(isset($conexion)) {
            $conexion->close();
        }
    }