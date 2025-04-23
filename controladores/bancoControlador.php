<?php
    if($peticionAjax){
        require_once "../modelos/bancoModelo.php";
    }else{
        require_once "./modelos/bancoModelo.php";
    }
	
	class bancoControlador extends bancoModelo{
		public function agregar_banco_controlador(){
			$nombre = mainModel::cleanString($_POST['confbanco']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			if(bancoModelo::valid_banco_modelo($nombre)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el banco",
				]);               
			}

			if(!bancoModelo::agregar_banco_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el banco",
					"type" => "error"
				]);
			}
						
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Banco registrado correctamente",
				"funcion" => "listar_banco_contabilidad();"
			]);			
		}
		
		public function edit_banco_controlador(){
			$banco_id = $_POST['banco_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['confbanco']);
			
			if (isset($_POST['confbanco_activo'])){
				$estado = $_POST['confbanco_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"banco_id" => $banco_id,
				"nombre" => $nombre,
				"estado" => $estado,				
			];		

			if(!bancoModelo::edit_banco_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el banco",
					"type" => "error"
				]);
			}
						
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Banco actualizado correctamente",
				"funcion" => "listar_banco_contabilidad();"
			]);			
		}
		
		public function delete_banco_controlador(){
			$banco_id = $_POST['banco_id'];
			
			$campos = ['nombre'];
			$tabla = "bancos";;
			$condicion = "banco_id = {$banco_id}";

			$banco = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($banco)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Banco no encontrado"
				]);
				exit();
			}
			
			$nombre = $banco[0]['nombre'] ?? '';

			if(bancoModelo::valid_banco_pagos_modelo($banco_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El banco {$nombre} tiene pagos asociados"
				]);
				exit();                
			}

			if(!bancoModelo::delete_banco_modelo($banco_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el banco {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Banco {$nombre} eliminado correctamente"
			]);
			exit();							
		}
	}