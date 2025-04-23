<?php
    if($peticionAjax){
        require_once "../modelos/contratoModelo.php";
    }else{
        require_once "./modelos/contratoModelo.php";
    }
	
	class contratoControlador extends contratoModelo{
		public function agregar_contrato_controlador(){
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
			$colaborador_id = mainModel::cleanString($_POST['contrato_colaborador_id']);
			$tipo_contrato_id = mainModel::cleanString($_POST['contrato_tipo_contrato_id']);
			$pago_planificado_id = mainModel::cleanString($_POST['contrato_pago_planificado_id']);
			$tipo_empleado_id = mainModel::cleanString($_POST['contrato_tipo_empleado_id']);
			$salario_mensual = mainModel::cleanString($_POST['contrato_salario_mensual']);
			$salario = mainModel::cleanString($_POST['contrato_salario']);
			$fecha_inicio = mainModel::cleanString($_POST['contrato_fecha_inicio']);
			$fecha_fin = mainModel::cleanString($_POST['contrato_fecha_fin']);
			$notas = mainModel::cleanString($_POST['contrato_notas']);
			$calculo_semanal = mainModel::cleanString(isset($_POST['calculo_semanal']) ? $_POST['calculo_semanal'] : 0);
			$usuario = $_SESSION['colaborador_id_sd'];
			$estado = 1;
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"colaborador_id" => $colaborador_id,
				"tipo_contrato_id" => $tipo_contrato_id,
				"pago_planificado_id" => $pago_planificado_id,
				"tipo_empleado_id" => $tipo_empleado_id,
				"salario_mensual" => $salario_mensual,
				"salario" => $salario,
				"fecha_inicio" => $fecha_inicio,
				"fecha_fin" => $fecha_fin,
				"notas" => $notas,
				"usuario" => $usuario,				
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
				"calculo_semanal" => $calculo_semanal,
			];
			
			if(contratoModelo::valid_contrato_modelo($colaborador_id)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el contrato",
				]);               
			}
			
			if(!contratoModelo::agregar_contrato_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el contrato",
					"type" => "error"
				]);
			}
						
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Contrato registrado correctamente",
				"funcion" => "listar_	contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();",
			]);			
		}
		
		public function edit_contrato_controlador(){
			$contrato_id = $_POST['contrato_id'];
			$salario = mainModel::cleanString($_POST['contrato_salario']);
			$fecha_inicio = mainModel::cleanString($_POST['contrato_fecha_inicio']);
			$fecha_fin = mainModel::cleanString($_POST['contrato_fecha_fin']);
			$notas = mainModel::cleanString($_POST['contrato_notas']);
			
			if (isset($_POST['contrato_activo'])){
				$estado = $_POST['contrato_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"contrato_id" => $contrato_id,
				"salario" => $salario,
				"fecha_inicio" => $fecha_inicio,
				"fecha_fin" => $fecha_fin,
				"notas" => $notas,
				"estado" => $estado,							
			];	
			
			if(!contratoModelo::edit_contrato_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el contrato",
					"type" => "error"
				]);
			}
						
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Contrato actualizado correctamente",
				"funcion" => "listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();",
			]);				
		}
		
		public function delete_contrato_controlador(){
			$contrato_id = $_POST['contrato_id'];
			
			$campos = ['nombre'];
			$tabla = "contrato";;
			$condicion = "contrato_id = {$contrato_id}";

			$contrato = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($contrato)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Contrato no encontrado"
				]);
				exit();
			}
			
			$nombre = $contrato[0]['nombre'] ?? '';

			if(contratoModelo::valid_contrato_nomina_modelo($contrato_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El registro {$nombre} tiene información almacenada"
				]);
				exit();                
			}

			if(!contratoModelo::delete_contrato_modelo($contrato_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el registro {$nombre}"
				]);
				exit();
			}							
		}
	}