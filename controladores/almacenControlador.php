<?php
    if($peticionAjax){
        require_once "../modelos/almacenModelo.php";
    }else{
        require_once "./modelos/almacenModelo.php";
    }
	
	class almacenControlador extends almacenModelo{
		public function agregar_almacen_controlador(){
			if (isset($_POST['almacen_empresa_id'])){
				$empresa = $_POST['almacen_empresa_id'];
			}else{
				$empresa = 1;
			}
			
			$almacen_almacen = mainModel::cleanStringConverterCase($_POST['almacen_almacen']);
			$ubicacion_almacen = mainModel::cleanStringConverterCase($_POST['ubicacion_almacen']);
			$estado = 1;
			$facturar_cero = $_POST['facturar_cero'];
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"almacen_almacen" => $almacen_almacen,
				"ubicacion_almacen" => $ubicacion_almacen,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,
				"empresa" => $empresa,	
				"facturar_cero"=>$facturar_cero,		
			];

			if(almacenModelo::valid_almacen_modelo($almacen_almacen)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el almacen",                
				]);                
			}

			$mainModel = new mainModel();
			$planConfig = $mainModel->getPlanConfiguracionMainModel();
			
			// Solo evaluar si existe configuración de plan
			if (isset($planConfig['almacenes'])) {
				$limiteAlmacenes = (int)$planConfig['almacenes']; // No usamos ?? 0 aquí para no convertir "no definido" en 0
				
				// Caso 1: Límite es 0 (bloquear)
				if ($limiteAlmacenes === 0) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Acceso restringido",
						"text" => "Su plan actual no permite registrar almacenes."
					]);
				}
				
				// Caso 2: Si tiene límite > 0, validar disponibilidad
				$totalRegistrados = (int)almacenModelo::getTotalAlmacenesRegistrados();
				
				if ($totalRegistrados >= $limiteAlmacenes) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Límite alcanzado",
						"text" => "Límite de almacenes alcanzado (Máximo: $limiteAlmacenes). Actualiza tu plan."
					]);
				}
			}

			if(!almacenModelo::agregar_almacen_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el almacen",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Almacen registrado correctamente",           
				"form" => "formAlmacen",
				"funcion" => "listar_almacen();getEmpresaAlmacen();getUbicacionAlmacen();"
			]);
		}
		
		public function edit_almacen_controlador(){
			$almacen_id = $_POST['almacen_id'];
			$almacen_almacen = mainModel::cleanStringConverterCase($_POST['almacen_almacen']);
			$estado = $_POST['val_almacen_activo'];
			$facturar_cero = $_POST['facturar_cero'];
						
			$datos = [
				"almacen_id" => $almacen_id,
				"almacen_almacen" => $almacen_almacen,
				"estado" => $estado,		
				"facturar_cero" => $facturar_cero,
			];	

			if(!almacenModelo::edit_almacen_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el almacen",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Almacen actualizado correctamente",           
				"form" => "formAlmacen",
				"funcion" => "listar_almacen();getEmpresaAlmacen();getUbicacionAlmacen();"
			]);
		}
		
		public function delete_almacen_controlador(){
			$almacen_id = $_POST['almacen_id'];
			
			$campos = ['almacen_id'];
			$tabla = "almacen";
			$condicion = "almacen_id = {$almacen_id}";

			$almacen = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($almacen)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Almacen no encontrado"
				]);
				exit();
			}
			
			$nombre = $almacen[0]['almacen_almacen'] ?? '';

			if(almacenModelo::valid_almacen_productos_modelo($almacen_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El almacen {$nombre} tiene productos asociados"
				]);
				exit();                
			}

			if(!almacenModelo::delete_almacen_modelo($almacen_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el almacen {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Almacen {$nombre} eliminado correctamente"
			]);
			exit();		
		}
	}