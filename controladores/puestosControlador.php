<?php
    if($peticionAjax){
        require_once "../modelos/puestosModelo.php";
    }else{
        require_once "./modelos/puestosModelo.php";
    }
	
	class puestosControlador extends puestosModelo{
		public function agregar_puestos_controlador(){
			$puesto = mainModel::cleanStringConverterCase($_POST['puesto']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"puesto" => $puesto,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			if(puestosModelo::valid_puestos_modelo($puesto)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo actualizar el puesto",                
				]);                
			}

			if(!puestosModelo::agregar_puestos_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el puesto",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Puesto registrado correctamente",           
				"form" => "formPuestos",
				"funcion" => "listar_puestos();"
			]);	
		}

		public function edit_puestos_controlador(){
			$puestos_id = $_POST['puestos_id'];
			
			if(isset($_POST['puesto'])){//COMPRUEBO SI LA VARIABLE ESTA DIFINIDA
				if($_POST['puesto'] == ""){
					$puesto = 0;
				}else{
					$puesto = mainModel::cleanStringConverterCase($_POST['puesto']);
				}
			}else{
				$puesto = 0;
			}			
			
			if (isset($_POST['puestos_activo'])){
				$estado = $_POST['puestos_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"puestos_id" => $puestos_id,
				"puesto" => $puesto,
				"estado" => $estado,				
			];		

			if(!puestosModelo::edit_puestos_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el puesto",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Puesto actualizado correctamente",           
				"form" => "formPuestos",
				"funcion" => "listar_puestos();"
			]);
		}
		
		public function delete_puestos_controlador(){
			$puesto_id = $_POST['puesto_id'];
			
			$campos = ['puesto_id'];
			$tabla = "puestos";
			$condicion = "puesto_id = {$puesto_id}";

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
			if(puestosModelo::valid_puestos_colaborador_modelo($puesto_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El puesto {$nombre} tiene colaboradores asociados"
				]);
				exit();                
			}

			if(!puestosModelo::delete_puestos_modelo($puesto_id)){
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
				"message" => "Puesto {$nombre} eliminado correctamente"
			]);
			exit();			
		}
	}