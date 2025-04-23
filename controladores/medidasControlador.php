<?php
    if($peticionAjax){
        require_once "../modelos/medidasModelo.php";
    }else{
        require_once "./modelos/medidasModelo.php";
    }
	
	class medidasControlador extends medidasModelo{
		public function agregar_medidas_controlador(){
			$medidas_medidas = mainModel::cleanStringConverterCase($_POST['medidas_medidas']);
			$descripcion_medidas = mainModel::cleanStringConverterCase($_POST['descripcion_medidas']);
			$estado = 1;
			
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"medidas_medidas" => $medidas_medidas,
				"descripcion_medidas" => $descripcion_medidas,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];

			if(medidasModelo::valid_medidas_modelo($medidas_medidas)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar la medida",                
				]);                
			}

			if(!medidasModelo::agregar_medidas_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar la medida",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Medida registrada correctamente",           
				"form" => "formMedidas",
				"funcion" => "listar_medidas();"
			]);
		}
		
		public function edit_medidas_controlador(){
			$medida_id = $_POST['medida_id'];
			$medidas_medidas = mainModel::cleanStringConverterCase($_POST['medidas_medidas']);
			$descripcion_medidas = mainModel::cleanStringConverterCase($_POST['descripcion_medidas']);
			
			if (isset($_POST['medidas_activo'])){
				$estado = $_POST['medidas_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"medida_id" => $medida_id,
				"medidas_medidas" => $medidas_medidas,
				"descripcion_medidas" => $descripcion_medidas,
				"estado" => $estado,				
			];	

			if(!medidasModelo::edit_medidas_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar la medida",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Medida actualizada correctamente",           
				"form" => "formMedidas",
				"funcion" => "listar_medidas();"
			]);
		}
		
		public function delete_medidas_controlador(){
			$medida_id = $_POST['medida_id'];
			
			$campos = ['medida_id'];
			$tabla = "medidas";
			$condicion = "medida_id = {$medida_id}";

			$ubicacion = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($medida)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Ubicacion no encontrado"
				]);
				exit();
			}
			
			$nombre = $ubicacion[0]['ubicacion_ubicacion'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(medidasModelo::valid_medidas_producto_modelo($medida_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "La medida {$nombre} tiene productos asociados"
				]);
				exit();                
			}

			if(!medidasModelo::delete_medidas_modelo($medida_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar la medida {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Medida {$nombre} eliminada correctamente"
			]);
			exit();			
		}
	}	