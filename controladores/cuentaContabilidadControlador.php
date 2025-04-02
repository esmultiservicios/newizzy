<?php
    if($peticionAjax){
        require_once "../modelos/cuentaContabilidadModelo.php";
    }else{
        require_once "./modelos/cuentaContabilidadModelo.php";
    }
	
	class cuentaContabilidadControlador extends cuentaContabilidadModelo{
		public function agregar_cuenta_contabilidad_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			$codigo = mainModel::cleanStringStrtoupper($_POST['cuenta_codigo']);
			$nombre = mainModel::cleanString($_POST['cuenta_nombre']);
			$cuentas_activo = 1;
			
			$fecha_registro = date("Y-m-d H:i:s");
	
			$datos = [
				"codigo" => $codigo,
				"nombre" => $nombre,
				"estado" => $cuentas_activo,
				"fecha_registro" => $fecha_registro,
			];
			
			//VALIDAMOS QUE NO EXISTA LA CUENTA
			$resultPuestos = cuentaContabilidadModelo::valid_cuenta_contable_modelo($nombre);
			
			if($resultPuestos->num_rows==0){
				$query = cuentaContabilidadModelo::agregar_cuenta_contabilidad_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formCuentasContables",
						"id" => "pro_cuentas",
						"valor" => "Registro",	
						"funcion" => "listar_cuentas_contabilidad();getCuentaIngresos();getCuentaEgresos();",
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
		
		public function edit_productos_controlador(){;
			$cuentas_id = mainModel::cleanString($_POST['cuentas_id']);		
			$codigo = mainModel::cleanStringStrtoupper(ISSET($_POST['cuenta_codigo']) ? $_POST['cuenta_codigo'] : "");
			$nombre = mainModel::cleanStringConverterCase($_POST['cuenta_nombre']);

			if (isset($_POST['cuentas_activo'])){
				$cuentas_activo = $_POST['cuentas_activo'];
			}else{
				$cuentas_activo = 1;
			}			

			$datos = [
				"cuentas_id" => $cuentas_id,
				"nombre" => $nombre,
				"estado" => $cuentas_activo,				
			];
					
			$query = cuentaContabilidadModelo::edit_cuentas_contabilidad_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formCuentasContables",
					"id" => "pro_cuentas",
					"valor" => "Registro",	
					"funcion" => "listar_cuentas_contabilidad();",
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
		
		public function delete_cuneta_contabilidad_controlador(){
	        $cuentas_id = mainModel::cleanString($_POST['cuentas_id']);	
			
			$result_valid_puestos_colaborador_modelo = cuentaContabilidadModelo::valid_cuenta_contable_movimientos_modelo($cuentas_id);
			
			if($result_valid_puestos_colaborador_modelo->num_rows==0 ){
				$query = cuentaContabilidadModelo::delete_cuenta_contabilidad_modelo($cuentas_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formCuentasContables",
						"id" => "pro_cuentas",
						"valor" => "Registro",	
						"funcion" => "listar_cuentas_contabilidad();",
						"modal" => "modalCuentascontables",
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