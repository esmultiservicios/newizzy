<?php
    if($peticionAjax){
        require_once "../modelos/ubicacionModelo.php";
    }else{
        require_once "./modelos/ubicacionModelo.php";
    }
	
	class ubicacionControlador extends ubicacionModelo{
		public function agregar_ubicacion_controlador(){
			$ubicacion = mainModel::cleanStringConverterCase($_POST['ubicacion_ubicacion']);
			$empresa = mainModel::cleanStringConverterCase($_POST['empresa_ubicacion']);
			$estado = 1;
			
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"ubicacion" => $ubicacion,
				"empresa" => $empresa,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			if(ubicacionModelo::valid_ubicacion_modelo($ubicacion)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar la ubicacion",                
				]);                
			}

			if(!ubicacionModelo::agregar_ubicacion_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar la ubicacion",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Ubicacion registrada correctamente",           
				"form" => "formUbicacion",
				"funcion" => "listar_ubicacion();getEmpresaUbicacion();"
			]);
		}
		
		public function edit_ubicacion_controlador(){
			$ubicacion_id = $_POST['ubicacion_id'];
			$ubicacion = mainModel::cleanStringConverterCase($_POST['ubicacion_ubicacion']);
			
			if (isset($_POST['ubicacion_activo'])){
				$estado = $_POST['ubicacion_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"ubicacion_id" => $ubicacion_id,
				"ubicacion" => $ubicacion,
				"estado" => $estado,				
			];	

			if(!ubicacionModelo::edit_ubicacion_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar la ubicacion",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Ubicacion actualizada correctamente",           
				"form" => "formUbicacion",
				"funcion" => "listar_ubicacion();getEmpresaUbicacion();"
			]);
		}
		
		public function delete_ubicacion_controlador(){
			$ubicacion_id = $_POST['ubicacion_id'];
			
			$campos = ['ubicacion_id'];
			$tabla = "ubicacion";
			$condicion = "ubicacion_id = {$ubicacion_id}";

			$ubicacion = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($ubicacion)) {
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
			if(ubicacionModelo::valid_ubicacion_almacen_modelo($ubicacion_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "La ubicacion {$nombre} tiene almacenes asociados"
				]);
				exit();                
			}

			if(!ubicacionModelo::delete_ubicacion_modelo($ubicacion_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar la ubicacion {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Ubicacion {$nombre} eliminada correctamente"
			]);
			exit();		
		}
	}