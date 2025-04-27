<?php
    if($peticionAjax){
        require_once "../modelos/tipoPagoModelo.php";
    }else{
        require_once "./modelos/tipoPagoModelo.php";
    }
	
	class tipoPagoControlador extends tipoPagoModelo{
		public function agregar_tipo_pago_controlador(){
			$nombre = mainModel::cleanStringConverterCase($_POST['confTipoPago']);
			
			if (isset($_POST['confCuentaTipoPago'])){
				$cuentas_id = $_POST['confCuentaTipoPago'];
			}else{
				$cuentas_id = 2;
			}

			if (isset($_POST['confTipoCuenta'])){
				$tipo_cuenta = $_POST['confTipoCuenta'];
			}else{
				$tipo_cuenta = 0;
			}			

			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"cuentas_id" => $cuentas_id,
				"tipo_cuenta" => $tipo_cuenta,
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];

			if(tipoPagoModelo::valid_tipo_pago_modelo($nombre)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el tipo de pago",                
				]);                
			}

			$mainModel = new mainModel();

            $planConfig = $mainModel->getPlanConfiguracionMainModel();
            
            // Solo verifica el límite si $planConfig NO está vacío
            if (!empty($planConfig)) {
                $limiteTipoPago = (int)($planConfig['tipo_pago'] ?? 0);
                $totalTipoPagoRegistrados = (int)tipoPagoModelo::getTotalTipoPagoRegistrados();
    
                if ($limiteTipoPago > 0 && $totalTipoPagoRegistrados >= $limiteTipoPago) {
                    return mainModel::showNotification([
                        "type" => "error",
                        "title" => "Error",
                        "text" => "Límite de tipos de pago alcanzado (Máximo: $limiteTipoPago). Actualiza tu plan."
                    ]);
                }
            }

			if(!tipoPagoModelo::agregar_tipo_pago_modelo($datos)){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el tipo de pago",                
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Tipo de pago registrado correctamente",
				"funcion" => "listar_tipo_pago_contabilidad();getCuentaTipoPago();getTipoCuenta();"
			]);		
		}
		
		public function edit_tipo_pago_controlador(){
			$tipo_pago_id = $_POST['tipo_pago_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['confTipoPago']);
			
			if (isset($_POST['confCuentaTipoPago'])){
				$cuentas_id = $_POST['confCuentaTipoPago'];
			}else{
				$cuentas_id = 2;
			}

			if (isset($_POST['confTipoPago_activo'])){
				$estado = $_POST['confTipoPago_activo'];
			}else{
				$estado = 2;
			}

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"tipo_pago_id" => $tipo_pago_id,
				"cuentas_id" => $cuentas_id,
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];

			
			if(!tipoPagoModelo::edit_tipo_pago_modelo($datos)){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el tipo de pago",                
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Tipo de pago actualizado correctamente",
				"funcion" => "listar_tipo_pago_contabilidad();getCuentaTipoPago();getTipoCuenta();",
			]);	
		}
		
		public function delete_tipo_pago_controlador(){
			$tipo_pago_id = $_POST['tipo_pago_id'];
			
			$campos = ['nombre'];
			$tabla = "tipo_pago";;
			$condicion = "tipo_pago_id = {$tipo_pago_id}";

			$proveedor = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($tipo_pago)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Proveedor no encontrado"
				]);
				exit();
			}
			
			$nombre = $tipo_pago[0]['nombre'] ?? '';

			if(tipoPagoModelo::valid_tipo_pagos_on_pagos_modelo($tipo_pago_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El tipo de pago {$nombre} tiene pagos asociados"
				]);
				exit();                
			}

			if(!tipoPagoModelo::delete_tipo_pago_modelo($tipo_pago_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el tipo de pago {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Proveedor {$nombre} eliminado correctamente"
			]);
			exit();						
		}
	}