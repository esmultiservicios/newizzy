<?php
    if($peticionAjax){
        require_once "../modelos/privilegioModelo.php";
    }else{
        require_once "./modelos/privilegioModelo.php";
    }
	
	class privilegioControlador extends privilegioModelo{
		public function agregar_privilegio_controlador(){
			$nombre = mainModel::cleanStringConverterCase($_POST['privilegios_nombre']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			if(privilegioModelo::valid_privilegios_modelo($nombre)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el privilegio",                
				]);                
			}

			if(!privilegioModelo::agregar_privilegios_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el privilegio",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Privilegio registrado correctamente",           
				"form" => "formPrivilegios",
				"funcion" => "listar_privilegio();"
			]);		
		}
		
		public function edit_privilegio_controlador(){
			$privilegio_id = $_POST['privilegio_id_'];
			$nombre = mainModel::cleanStringConverterCase($_POST['privilegios_nombre']);
			
			$estado = isset($_POST['privilegio_activo']) && $_POST['privilegio_activo'] == 'on' ? 1 : 0;
			
			$datos = [
				"privilegio_id" => $privilegio_id,
				"nombre" => $nombre,
				"estado" => $estado,
			];		

			if(!privilegioModelo::edit_privilegio_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el privilegio",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Privilegio actualizado correctamente",
				"funcion" => "listar_privilegio();"
			]);		
		}
		
		public function delete_privilegio_controlador(){
			$privilegio_id = $_POST['privilegio_id_'];
			
			$campos = ['privilegio_id'];
			$tabla = "privilegio";
			$condicion = "privilegio_id = {$privilegio_id}";

			$privilegio = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($privilegio)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Privilegio no encontrado"
				]);
				exit();
			}
			
			$nombre = $privilegio[0]['privilegio_nombre'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(privilegioModelo::valid_privilegio_usuarios($privilegio_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El privilegio {$nombre} tiene usuarios asociados"
				]);
				exit();                
			}

			if(!privilegioModelo::delete_privilegio_modelo($privilegio_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el privilegio {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Privilegio {$nombre} eliminado correctamente"
			]);
			exit();	
		}		
	}