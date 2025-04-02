<?php
    if($peticionAjax){
        require_once "../modelos/tipoPagoModelo.php";
    }else{
        require_once "./modelos/tipoPagoModelo.php";
    }
	
	class tipoPagoControlador extends tipoPagoModelo{
		public function agregar_tipo_pago_controlador(){
			$nombre = mainModel::cleanStringConverterCase($_POST['confTipoPago']);
			
			if (isset($_POST['confCuentaTipoPago'])){
				$cuentas_id = $_POST['confCuentaTipoPago'];
			}else{
				$cuentas_id = 2;
			}

			if (isset($_POST['confTipoCuenta'])){
				$tipo_cuenta = $_POST['confTipoCuenta'];
			}else{
				$tipo_cuenta = 0;
			}			

			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"cuentas_id" => $cuentas_id,
				"tipo_cuenta" => $tipo_cuenta,
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$resultTipoPagoModelo = tipoPagoModelo::valid_tipo_pago_modelo($nombre);
			
			if($resultTipoPagoModelo->num_rows==0){
				$query = tipoPagoModelo::agregar_tipo_pago_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formConfTipoPago",
						"id" => "pro_tipoPago",
						"valor" => "Registro",	
						"funcion" => "listar_tipo_pago_contabilidad();getCuentaTipoPago();getTipoCuenta();",
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
		
		public function edit_tipo_pago_controlador(){
			$tipo_pago_id = $_POST['tipo_pago_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['confTipoPago']);
			
			if (isset($_POST['confCuentaTipoPago'])){
				$cuentas_id = $_POST['confCuentaTipoPago'];
			}else{
				$cuentas_id = 2;
			}

			if (isset($_POST['confTipoPago_activo'])){
				$estado = $_POST['confTipoPago_activo'];
			}else{
				$estado = 2;
			}

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"tipo_pago_id" => $tipo_pago_id,
				"cuentas_id" => $cuentas_id,
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];

			$query = tipoPagoModelo::edit_tipo_pago_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formConfTipoPago",	
					"id" => "pro_tipoPago",
					"valor" => "Editar",
					"funcion" => "listar_tipo_pago_contabilidad();getCuentaTipoPago();getTipoCuenta();",
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
		
		public function delete_tipo_pago_controlador(){
			$tipo_pago_id = $_POST['tipo_pago_id'];
			
			$result_valid_pagos_on_pagos_modelo = tipoPagoModelo::valid_tipo_pagos_on_pagos_modelo($tipo_pago_id);
			
			if($result_valid_pagos_on_pagos_modelo->num_rows==0 ){
				$query = tipoPagoModelo::delete_tipo_pago_modelo($tipo_pago_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formConfTipoPago",	
						"id" => "pro_tipoPago",
						"valor" => "Eliminar",
						"funcion" => "listar_tipo_pago_contabilidad();getCuentaTipoPago();getTipoCuenta();",
						"modal" => "modalConfTipoPago",
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