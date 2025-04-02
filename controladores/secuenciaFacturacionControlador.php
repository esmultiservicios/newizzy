<?php
    if($peticionAjax){
        require_once "../modelos/secuenciaFacturacionModelo.php";
    }else{
        require_once "./modelos/secuenciaFacturacionModelo.php";
    }
	
	class secuenciaFacturacionControlador extends secuenciaFacturacionModelo{
		public function agregar_secuencia_facturacion_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			$empresa_id = mainModel::cleanString($_POST['empresa_secuencia']);
			$documento_id = mainModel::cleanString($_POST['documento_secuencia']);
			$cai = mainModel::cleanString($_POST['cai_secuencia']);			
			$prefijo = mainModel::cleanString($_POST['prefijo_secuencia']);
			$relleno = mainModel::cleanString($_POST['relleno_secuencia']);
			$incremento = mainModel::cleanString($_POST['incremento_secuencia']);
			$siguiente = mainModel::cleanString($_POST['siguiente_secuencia']);
			$rango_inicial = mainModel::cleanString($_POST['rango_inicial_secuencia']);
			$rango_final = mainModel::cleanString($_POST['rango_final_secuencia']);
			$fecha_activacion = mainModel::cleanString($_POST['fecha_activacion_secuencia']);
			$fecha_limite = mainModel::cleanString($_POST['fecha_limite_secuencia']);
			$usuario = mainModel::cleanString($_SESSION['colaborador_id_sd']);
			$fecha_registro = date("Y-m-d H:i:s");
			$activo = 1;	
			
			$datos = [
				"empresa_id" => $empresa_id,
				"documento_id" => $documento_id,
				"cai" => $cai,
				"prefijo" => $prefijo,
				"relleno" => $relleno,
				"incremento" => $incremento,
				"siguiente" => $siguiente,
				"rango_inicial" => $rango_inicial,
				"rango_final" => $rango_final,
				"fecha_activacion" => $fecha_activacion,
				"fecha_limite" => $fecha_limite,
				"activo" => $activo,
				"usuario" => $usuario,
				"fecha_registro" => $fecha_registro,					
			];			
			
			$resultSecuenciaFacturacion = secuenciaFacturacionModelo::valid_secuencia_facturacion($empresa_id, $documento_id);
			
			if($resultSecuenciaFacturacion->num_rows==0){
				$query = secuenciaFacturacionModelo::agregar_secuencia_facturacion_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formSecuencia",
						"id" => "proceso_secuencia_facturacion",
						"valor" => "Registro",	
						"funcion" => "listar_secuencia_facturacion();getEmpresaSecuencia();getDocumentoSecuencia();",
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
		
		public function edit_secuencia_facturacion_controlador(){
			$secuencia_facturacion_id = $_POST['secuencia_facturacion_id'];
			$cai = mainModel::cleanString($_POST['cai_secuencia']);			
			$prefijo = mainModel::cleanString($_POST['prefijo_secuencia']);
			$relleno = mainModel::cleanString($_POST['relleno_secuencia']);
			$incremento = mainModel::cleanString($_POST['incremento_secuencia']);
			$siguiente = mainModel::cleanString($_POST['siguiente_secuencia']);
			$rango_inicial = mainModel::cleanString($_POST['rango_inicial_secuencia']);
			$rango_final = mainModel::cleanString($_POST['rango_final_secuencia']);
			$fecha_activacion = mainModel::cleanString($_POST['fecha_activacion_secuencia']);
			$fecha_limite = mainModel::cleanString($_POST['fecha_limite_secuencia']);

			if (isset($_POST['estado_secuencia'])){
				$activo = $_POST['estado_secuencia'];
			}else{
				$activo = 2;
			}	
			
			$datos = [
				"secuencia_facturacion_id" => $secuencia_facturacion_id,
				"cai" => $cai,
				"prefijo" => $prefijo,
				"relleno" => $relleno,
				"incremento" => $incremento,
				"siguiente" => $siguiente,
				"rango_inicial" => $rango_inicial,
				"rango_final" => $rango_final,
				"fecha_activacion" => $fecha_activacion,
				"fecha_limite" => $fecha_limite,
				"activo" => $activo,					
			];	

			$query = secuenciaFacturacionModelo::edit_secuencia_facturacion_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formSecuencia",	
					"id" => "proceso_secuencia_facturacion",
					"valor" => "Editar",
					"funcion" => "listar_secuencia_facturacion();getEmpresaSecuencia();getDocumentoSecuencia();",
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
		
		public function delete_secuencia_facturacion_controlador(){
			$secuencia_facturacion_id = $_POST['secuencia_facturacion_id'];
			
			$result_valid_secuencia_facturacion = secuenciaFacturacionModelo::valid_secuencia_facturacion_facturas($secuencia_facturacion_id);
			
			if($result_valid_secuencia_facturacion->num_rows==0){
				$query = secuenciaFacturacionModelo::delete_secuencia_facturacion_modelo($secuencia_facturacion_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formSecuencia",	
						"id" => "proceso_secuencia_facturacion",
						"valor" => "Eliminar",
						"funcion" => "listar_secuencia_facturacion();getEmpresaSecuencia();getDocumentoSecuencia();",
						"modal" => "modal_registrar_secuencias",
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