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
			
			$resultAlmacen = almacenModelo::valid_almacen_modelo($almacen_almacen);
			
			if($resultAlmacen->num_rows==0){
				$query = almacenModelo::agregar_almacen_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formAlmacen",
						"id" => "pro_almacen",
						"valor" => "Registro",	
						"funcion" => "listar_almacen();getEmpresaAlmacen();getUbicacionAlmacen();",
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

			$query = almacenModelo::edit_almacen_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formAlmacen",	
					"id" => "pro_almacen",
					"valor" => "Editar",
					"funcion" => "listar_almacen();getEmpresaAlmacen();getUbicacionAlmacen();",
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
		
		public function delete_almacen_controlador(){
			$almacen_id = $_POST['almacen_id'];
			
			$result_almacen_productos = almacenModelo::valid_almacen_productos_modelo($almacen_id);
			
			if($result_almacen_productos->num_rows==0 ){
				$query = almacenModelo::delete_almacen_modelo($almacen_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formAlmacen",	
						"id" => "pro_almacen",
						"valor" => "Eliminar",
						"funcion" => "listar_almacen();getEmpresaAlmacen();getUbicacionAlmacen();",
						"modal" => "modal_almacen",
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