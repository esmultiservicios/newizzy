<?php
    if($peticionAjax){
        require_once "../modelos/secuenciaFacturacionModelo.php";
    }else{
        require_once "./modelos/secuenciaFacturacionModelo.php";
    }
	
	class secuenciaFacturacionControlador extends secuenciaFacturacionModelo{
		public function agregar_secuencia_facturacion_controlador(){
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
			
			$empresa_id = mainModel::cleanString($_POST['empresa_secuencia']);
			$documento_id = mainModel::cleanString($_POST['documento_secuencia']);
			$cai = mainModel::cleanString($_POST['cai_secuencia']);			
			$prefijo = mainModel::cleanString($_POST['prefijo_secuencia']);
			$relleno = mainModel::cleanString($_POST['relleno_secuencia']);
			$incremento = mainModel::cleanString($_POST['incremento_secuencia']);
			$siguiente = mainModel::cleanString($_POST['siguiente_secuencia']);
			$rango_inicial = mainModel::cleanString($_POST['rango_inicial_secuencia']);
			$rango_final = mainModel::cleanString($_POST['rango_final_secuencia']);
			$fecha_activacion = mainModel::cleanString($_POST['fecha_activacion_secuencia']);
			$fecha_limite = mainModel::cleanString($_POST['fecha_limite_secuencia']);
			$usuario = mainModel::cleanString($_SESSION['colaborador_id_sd']);
			$fecha_registro = date("Y-m-d H:i:s");
			$activo = 1;	
			
			$datos = [
				"empresa_id" => $empresa_id,
				"documento_id" => $documento_id,
				"cai" => $cai,
				"prefijo" => $prefijo,
				"relleno" => $relleno,
				"incremento" => $incremento,
				"siguiente" => $siguiente,
				"rango_inicial" => $rango_inicial,
				"rango_final" => $rango_final,
				"fecha_activacion" => $fecha_activacion,
				"fecha_limite" => $fecha_limite,
				"activo" => $activo,
				"usuario" => $usuario,
				"fecha_registro" => $fecha_registro,					
			];			
			
			if(secuenciaFacturacionModelo::valid_secuencia_facturacion($empresa_id, $documento_id)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar la secuencia de facturacion",                
				]);                
			}

			$mainModel = new mainModel();
			$planConfig = $mainModel->getPlanConfiguracionMainModel();
			
			// Solo evaluar si existe configuración de plan
			if (isset($planConfig['secuencias'])) {
				$limiteSecuencias = (int)$planConfig['secuencias']; // No usamos ?? 0 aquí para no convertir "no definido" en 0
				
				// Caso 1: Límite es 0 (bloquear)
				if ($limiteSecuencias === 0) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Acceso restringido",
						"text" => "Su plan actual no permite registrar secuencias de facturacion."
					]);
				}
				
				// Caso 2: Si tiene límite > 0, validar disponibilidad
				$totalRegistrados = (int)secuenciaFacturacionModelo::getTotalSecuenciaRegistradas();
				
				if ($totalRegistrados >= $limiteSecuencias) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Límite alcanzado",
						"text" => "Límite de secuencias de facturacion alcanzado (Máximo: $limiteSecuencias). Actualiza tu plan."
					]);
				}
			}	

			if(!secuenciaFacturacionModelo::agregar_secuencia_facturacion_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar la secuencia de facturacion",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Secuencia de facturacion registrada correctamente",           
				"form" => "formSecuencia",
				"funcion" => "listar_secuencia_facturacion();getEmpresaSecuencia();getDocumentoSecuencia();"
			]);
		}
		
		public function edit_secuencia_facturacion_controlador(){
			$secuencia_facturacion_id = $_POST['secuencia_facturacion_id'];
			$cai = mainModel::cleanString($_POST['cai_secuencia']);			
			$prefijo = mainModel::cleanString($_POST['prefijo_secuencia']);
			$relleno = mainModel::cleanString($_POST['relleno_secuencia']);
			$incremento = mainModel::cleanString($_POST['incremento_secuencia']);
			$siguiente = mainModel::cleanString($_POST['siguiente_secuencia']);
			$rango_inicial = mainModel::cleanString($_POST['rango_inicial_secuencia']);
			$rango_final = mainModel::cleanString($_POST['rango_final_secuencia']);
			$fecha_activacion = mainModel::cleanString($_POST['fecha_activacion_secuencia']);
			$fecha_limite = mainModel::cleanString($_POST['fecha_limite_secuencia']);

			$activo = isset($_POST['estado_secuencia']) && $_POST['estado_secuencia'] == 'on' ? 1 : 0;		
			
			$datos = [
				"secuencia_facturacion_id" => $secuencia_facturacion_id,
				"cai" => $cai,
				"prefijo" => $prefijo,
				"relleno" => $relleno,
				"incremento" => $incremento,
				"siguiente" => $siguiente,
				"rango_inicial" => $rango_inicial,
				"rango_final" => $rango_final,
				"fecha_activacion" => $fecha_activacion,
				"fecha_limite" => $fecha_limite,
				"activo" => $activo,					
			];	

			if(!secuenciaFacturacionModelo::edit_secuencia_facturacion_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar la secuencia de facturacion",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Secuencia de facturacion actualizada correctamente",           
				"form" => "formSecuencia",
				"funcion" => "listar_secuencia_facturacion();getEmpresaSecuencia();getDocumentoSecuencia();"
			]);
		}
		
		public function delete_secuencia_facturacion_controlador(){
			$secuencia_facturacion_id = $_POST['secuencia_facturacion_id'];
			
			$campos = ['secuencia_facturacion_id'];
			$tabla = "secuencia_facturacion";
			$condicion = "secuencia_facturacion_id = {$secuencia_facturacion_id}";

			$puesto = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($puesto)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Puesto no encontrado"
				]);
				exit();
			}
			
			$nombre = $puesto[0]['puesto'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(secuenciaFacturacionModelo::valid_secuencia_facturacion_facturas($secuencia_facturacion_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El puesto {$nombre} tiene colaboradores asociados"
				]);
				exit();                
			}

			if(!secuenciaFacturacionModelo::delete_secuencia_facturacion_modelo($secuencia_facturacion_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el puesto {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Secuencia de facturacion {$nombre} eliminada correctamente"
			]);
			exit();
		}
	}