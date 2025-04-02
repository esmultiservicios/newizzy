<?php
    if($peticionAjax){
        require_once "../modelos/contratoModelo.php";
    }else{
        require_once "./modelos/contratoModelo.php";
    }
	
	class contratoControlador extends contratoModelo{
		public function agregar_contrato_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
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
			
			$resultContrato = contratoModelo::valid_contrato_modelo($colaborador_id);
			
			if($resultContrato->num_rows==0){
				$query = contratoModelo::agregar_contrato_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formContrato",
						"id" => "proceso_contrato",
						"valor" => "Registro",	
						"funcion" => "listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();",
						"modal" => "",
					];
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Ocurrio un error inesperado",
						"text" => "No hemos podido procesar su solicitud",
						"type" => "error",
						"btn-class" => "btn-danger",					
					];				
				}				
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Resgistro ya existe",
					"text" => "Lo sentimos este registro ya existe",
					"type" => "error",	
					"btn-class" => "btn-danger",						
				];				
			}
			
			return mainModel::sweetAlert($alert);
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

			$query = contratoModelo::edit_contrato_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formContrato",	
					"id" => "proceso_contrato",
					"valor" => "Editar",
					"funcion" => "listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();",
					"modal" => "",
				];
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Ocurrio un error inesperado",
					"text" => "No hemos podido procesar su solicitud",
					"type" => "error",
					"btn-class" => "btn-danger",					
				];				
			}			
			
			return mainModel::sweetAlert($alert);
		}
		
		public function delete_contrato_controlador(){
			$contrato_id = $_POST['contrato_id'];
			
			$result_valid_contrato_nomina_modelo = contratoModelo::valid_contrato_nomina_modelo($contrato_id);
			
			if($result_valid_contrato_nomina_modelo->num_rows==0 ){
				$query = contratoModelo::delete_contrato_modelo($contrato_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formContrato",	
						"id" => "proceso_contrato",
						"valor" => "Eliminar",
						"funcion" => "listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();",
						"modal" => "modal_registrar_contrato",
					];
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Ocurrio un error inesperado",
						"text" => "No hemos podido procesar su solicitud",
						"type" => "error",
						"btn-class" => "btn-danger",					
					];				
				}				
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Este registro cuenta con información almacenada",
					"text" => "No se puede eliminar este registro",
					"type" => "error",	
					"btn-class" => "btn-danger",						
				];				
			}
			
			return mainModel::sweetAlert($alert);			
		}
	}
?>