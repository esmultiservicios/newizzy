<?php
    if($peticionAjax){
        require_once "../modelos/cuentaContabilidadModelo.php";
    }else{
        require_once "./modelos/cuentaContabilidadModelo.php";
    }
	
	class cuentaContabilidadControlador extends cuentaContabilidadModelo{
		public function agregar_cuenta_contabilidad_controlador(){
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

			$codigo = mainModel::cleanStringStrtoupper($_POST['cuenta_codigo']);
			$nombre = mainModel::cleanString($_POST['cuenta_nombre']);
			$cuentas_activo = 1;
			
			$fecha_registro = date("Y-m-d H:i:s");
	
			$datos = [
				"codigo" => $codigo,
				"nombre" => $nombre,
				"estado" => $cuentas_activo,
				"fecha_registro" => $fecha_registro,
			];
			
			//VALIDAMOS EL REGISTRO
			if(cuentaContabilidadModelo::valid_cuenta_contable_modelo($nombre)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "La cuenta {$nombre} ya existe",                
				]);              
			}
			
			$mainModel = new mainModel();
			$planConfig = $mainModel->getPlanConfiguracionMainModel();
			
			// Solo evaluar si existe configuración de plan
			if (!empty($planConfig)) {
				$limiteCuentas = (int)($planConfig['cuentas'] ?? 0);
				
				// Caso 1: Límite es 0 (bloquear)
				if ($limiteCuentas === 0) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Acceso restringido",
						"text" => "Su plan actual no permite registrar cuentas contables."
					]);
				}
				
				// Caso 2: Si tiene límite > 0, validar disponibilidad
				$totalRegistrados = (int)cuentaContabilidadModelo::getTotalCuentasRegistradas();
				
				if ($totalRegistrados >= $limiteCuentas) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Límite alcanzado",
						"text" => "Límite de cuentas contables alcanzado (Máximo: $limiteCuentas). Actualiza tu plan."
					]);
				}
			}	

			if(!cuentaContabilidadModelo::agregar_cuenta_contabilidad_modelo($datos)){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar la cuenta {$nombre}",                
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Cuenta {$nombre} registrada correctamente",
				"funcion" => "listar_cuentas_contabilidad();"
			]);		
		}
		
		public function edit_cuentas_contabilidad_controlador(){;
			$cuentas_id = mainModel::cleanString($_POST['cuentas_id']);		
			$codigo = mainModel::cleanStringStrtoupper(ISSET($_POST['cuenta_codigo']) ? $_POST['cuenta_codigo'] : "");
			$nombre = mainModel::cleanStringConverterCase($_POST['cuenta_nombre']);

			if (isset($_POST['cuentas_activo'])){
				$cuentas_activo = $_POST['cuentas_activo'];
			}else{
				$cuentas_activo = 1;
			}			

			$datos = [
				"cuentas_id" => $cuentas_id,
				"nombre" => $nombre,
				"estado" => $cuentas_activo,				
			];
					
			if(!cuentaContabilidadModelo::edit_cuentas_contabilidad_modelo($datos)){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo actualizar la cuenta",                
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Cuenta actualizada correctamente",
				"funcion" => "listar_cuentas_contabilidad();"
			]);
		}
		
		public function delete_cuneta_contabilidad_controlador(){
	        $cuentas_id = mainModel::cleanString($_POST['cuentas_id']);	
			
			$campos = ['nombre'];
			$tabla = "cuentas";
			$condicion = "cuentas_id = {$cuentas_id}";

			$cuenta = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($cuenta)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Cuenta no encontrada"
				]);
				exit();
			}
			
			$nombre = $cuenta[0]['nombre'] ?? '';

			if(cuentaContabilidadModelo::valid_cuenta_contable_movimientos_modelo($cuentas_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "La cuenta {$nombre} tiene movimientos asociados"
				]);
				exit();                
			}

			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Cuenta {$nombre} eliminada correctamente"
			]);
			exit();						
		}		
	}