<?php
    $peticionAjax = true;
    require_once "configGenerales.php";
    require_once "mainModel.php";
    require_once "Database.php";

    if (!isset($_SESSION['user_sd'])) {
        session_start(['name' => 'SD']);
    }

    $database = new Database();
    $insMainModel = new mainModel();
    $codigoCliente = $_POST['codigoCliente'];
    
    $generateNew = isset($_POST['generateNew']) ? $_POST['generateNew'] : 0; // Obtener el valor de generateNew
    $tablaPin = "pin";
    $camposPin = ["pin"];

    $generoPinNuevo = false;

    // Si generateNew es true, genera un nuevo PIN independientemente de la existencia del anterior
    if ($generateNew === "1") {
        // Generar un PIN aleatorio de 6 dígitos
        $pin = mt_rand(100000, 999999);
        $generoPinNuevo = true;
    } else {
        // Consultar si existe un PIN válido para el cliente
         $mysqliPin = $insMainModel->connection();

        $query = "SELECT pin FROM pin WHERE codigo_cliente = '$codigoCliente' AND fecha_hora_fin > NOW()";
    
        $resultPin = $mysqliPin->query($query) or die($mysqliPin->error);

        if($resultPin->num_rows>0){
            // Si existe un PIN válido, devolver ese PIN como respuesta JSON
            $consulta2 = $resultPin->fetch_assoc();

            header('Content-Type: application/json');
            echo json_encode(['pin' => $consulta2['pin']]);
            exit; // Terminar el script
        } else {
            // Si no existe un PIN válido, generar uno nuevo
            $pin = mt_rand(100000, 999999);
            $generoPinNuevo = true;
        }
    }

    if($generoPinNuevo){
        // Verificar si existe un PIN activo para el cliente
        $mysqliPin = $insMainModel->connection();

        $query = "SELECT pin FROM pin WHERE codigo_cliente = '$codigoCliente' AND fecha_hora_fin > NOW()";
    
        $resultPin = $mysqliPin->query($query) or die($mysqliPin->error);

        if($resultPin->num_rows>0){
            // Si hay un PIN activo, actualiza la fecha y hora de finalización para que venza
            $consulta2 = $resultPin->fetch_assoc();
            
            $pinAnterior = $consulta2['pin'];
            $fechaHoraActual = date("Y-m-d H:i:s");

            // Actualiza la fecha y hora de finalización del PIN anterior para que sea anterior a la fecha y hora actual
            $datosActualizacion = [
                "fecha_hora_fin" => $fechaHoraActual,
            ];

            $condicionesActualizacion = [
                "pin" => $pinAnterior
            ];

            $database->actualizarRegistros($tablaPin, $datosActualizacion, $condicionesActualizacion);

            //ACTUALIZAR EL PIN EN EL SERVIDOR PRINCIPAL
            $datos = [
				"fecha_hora_fin" => $fechaHoraActual,
				"codigo_cliente" => $codigoCliente,
				"pin" => $pinAnterior		
			];
            
            $insMainModel->actualizarPinServerP($datos);            
        }

        // Generar un nuevo PIN hasta que se encuentre uno único
        while (true) {
            //VERIFICAMOS QUE NO EXISTA EL PIN ANTES DE INGRESARLO
            $condicionesPin = [
                "pin" => $pin
            ];
            $orderBy = "";
            $tablaJoin = "";
            $condicionesJoin = [];
            $resultadoPin = $database->consultarTabla($tablaPin, $camposPin, $condicionesPin, $orderBy, $tablaJoin, $condicionesJoin);

            if (empty($resultadoPin)) {
                //INSERTAMOS EL PIN EN LA TABLA PIN
                $campoCorrelativo = "pin_id";
                $pin_id = $database->obtenerCorrelativo($tablaPin, $campoCorrelativo);
                // Obtener la fecha y hora actual
                $fechaHoraInicio = date("Y-m-d H:i:s");

                // Calcular la fecha y hora de fin agregando 60 segundos a la fecha de inicio
                $fechaHoraFin = date("Y-m-d H:i:s", strtotime($fechaHoraInicio) + (5 * 60));
                $server_customers_id = $_SESSION['server_customers_id'];

                $campos = ["pin_id", "server_customers_id", "codigo_cliente", "pin", "fecha_hora_inicio", "fecha_hora_fin"];
                $valores = [$pin_id, $server_customers_id, $codigoCliente, $pin, $fechaHoraInicio, $fechaHoraFin];
                $database->insertarRegistro($tablaPin, $campos, $valores);
                
                //INSERTARMOS EL PIN EN EL SERVIDOR PRINCIPAL
                $datos = [
                    "server_customers_id" => $server_customers_id,
                    "codigo_cliente" => $codigoCliente,
                    "pin" => $pin,
                    "fecha_hora_inicio" => $fechaHoraInicio,
                    "fecha_hora_fin" => $fechaHoraFin
                ];
                
                $insMainModel->insertarPinServerP($datos); 
                
                break;
            }else{
                // Generar un PIN aleatorio de 6 dígitos
                $pin = mt_rand(100000, 999999);
            }
        }
    }

    // Devolver el PIN como respuesta JSON
    header('Content-Type: application/json');
    echo json_encode(['pin' => $pin]);
?>