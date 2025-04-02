<?php  
    $peticionAjax = true;
    require_once "configGenerales.php";
    require_once "mainModel.php";
    
    $insMainModel = new mainModel();
    $nomina_id = $_POST['nomina_id'];
    $empresa_id = $_POST['empresa_id'];
    $categoria_gastos_id = 0;
    
    // CONSULTAMOS EL TOTAL DEL NETO DEL DETALLE DE LA NOMINA
    $result_saldos = $insMainModel->getTotalesNominaDetalle($nomina_id);

    $response = []; // Array para la respuesta

    if ($result_saldos->num_rows > 0 ) {
        $row_saldos = $result_saldos->fetch_assoc();

        $query = $insMainModel->actualizarNomina($nomina_id, $row_saldos['neto']);

        if ($query) {
            $insMainModel->actualizarNominaDetalles($nomina_id);

            // CONSULTAMOS LOS COLABORADORES QUE ESTAN EN LA NOMINA
            $result_colaboradores = $insMainModel->GetColaboradoresNomina($nomina_id);

            while ($consulta2 = $result_colaboradores->fetch_assoc()) {
                // ACTUALIZAMOS EL ESTADO DE LA ASISTENCIA DE LOS EMPLEADOS
                $insMainModel->ActualizarEstadoAsistencia($consulta2['colaboradores_id']);
                $datos_vale = [
                    "colaboradores_id" => $consulta2['colaboradores_id'],
                    "nomina_id" => $nomina_id,
                    "estado" => "1"                
                ];
                $insMainModel->actualizarVales($datos_vale);
            }

            // CONSULTAMOS LA CUENTA QUE TIENE CONFIGURADA LA NOMINA
            if (!isset($_SESSION['user_sd'])) { 
                session_start(['name'=>'SD']); 
            }
                    
            $consulta_cuenta = $insMainModel->getCuentaIdNomina($nomina_id)->fetch_assoc();
            $cuentas_id = $consulta_cuenta['cuentas_id'];
            
            // Datos del registro
            $tipo_egreso = 2;
            $fecha = date("Y-m-d");
            $fecha_registro = date("Y-m-d H:i:s");
            $factura = "Nomina ".$nomina_id;
            $subtotal = $row_saldos['neto'];
            $total = $row_saldos['neto'];
            $estado = 1;
            $observacion = "Pago de Nomina ".$nomina_id;
            $colaboradores_id = $_SESSION['colaborador_id_sd'];
            $proveedores_id = 1;

            // Agregamos los gastos
            $datos = [
                "proveedores_id" => $proveedores_id,
                "cuentas_id" => $cuentas_id,
                "empresa_id" => $empresa_id,
                "tipo_egreso" => $tipo_egreso,
                "fecha" => $fecha,
                "factura" => $factura,
                "subtotal" => $subtotal,
                "isv" => 0,
                "descuento" => 0,
                "nc" => 0,
                "total" => $total,
                "observacion" => $observacion,
                "estado" => $estado,
                "fecha_registro" => $fecha_registro,
                "colaboradores_id" => $colaboradores_id,
                "categoria_gastos_id" => $categoria_gastos_id
            ];

            $resultEgresos = $insMainModel->validEgresosCuentasMainModel($datos);

            if ($resultEgresos->num_rows == 0) {
                $query = $insMainModel->agregarEgresosMainModel($datos);
                        
                if ($query) {
                    $consulta_ingresos_contabilidad = $insMainModel->consultaSaldoMovimientosMainModel($cuentas_id)->fetch_assoc();
                    $saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) ? $consulta_ingresos_contabilidad['saldo'] : 0;    
                    $ingreso = 0;
                    $egreso = $total;
                    $saldo = $saldo_consulta - $egreso;

                    // Agregamos los movimientos de la cuenta
                    $datos_movimientos = [
                        "cuentas_id" => $cuentas_id,
                        "empresa_id" => $empresa_id,
                        "fecha" => $fecha,
                        "ingreso" => $ingreso,
                        "egreso" => $egreso,
                        "saldo" => $saldo,
                        "colaboradores_id" => $colaboradores_id,
                        "fecha_registro" => $fecha_registro
                    ];

                    $insMainModel->agregarMovimientosMainModel($datos_movimientos);
                }

                // Respondemos con éxito y el nomina_id generado
                $response = [
                    'status' => 1,
                    'nomina_id' => $nomina_id,
                    'message' => 'La nómina se ha generado correctamente.'
                ];
            } else {
                $response = [
                    'status' => 2,
                    'message' => 'No se pudo registrar los egresos.'
                ];
            }
        } else {
            $response = [
                'status' => 3,
                'message' => 'No se pudo actualizar la nómina.'
            ];
        }
    } else {
        $response = [
            'status' => 4,
            'message' => 'No existe un detalle generado para esta nómina.'
        ];
    }

    echo json_encode($response);