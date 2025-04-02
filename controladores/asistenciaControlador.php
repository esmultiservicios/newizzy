<?php
    if($peticionAjax){
        require_once "../modelos/asistenciaModelo.php";
    }else{
        require_once "./modelos/asistenciaModelo.php";
    }
	
	class asistenciaControlador extends asistenciaModelo {
		public function agregar_asistencia_controlador(){
			$colaborador = $_POST['asistencia_empleado'];
			$marcarAsistencia_id = $_POST['marcarAsistencia_id'];

			if(isset($_POST['fecha'])){ 
				$fecha = $_POST['fecha'];
			}else{
				$fecha = date("Y-m-d H:i:s");
			}

			$datos_comentario = [
				"colaborador" => $colaborador,
				"fecha" => $fecha,				
			];

			//OBTENEMOS EL COMENTARIO PREVIO
			$result_comentario = asistenciaModelo::getComentarioAsistenciaModelo($datos_comentario)->fetch_assoc();
			$_comentario = isset($result_comentario['comentario']) ? $result_comentario['comentario'] : "";

			if($_comentario == "")
			{
				$comentario = mainModel::cleanString($_POST['comentario']);
			}else{
				$comentario = $_comentario.' - '.mainModel::cleanString($_POST['comentario']);
			}

			if($marcarAsistencia_id == 0){
				$hora = $_POST['hora'];
			}else{
				$hora = $_POST['horagi'];
			}				
			
			$estado = 0;
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"colaborador" => $colaborador,
				"fecha" => $fecha,
				"horai" => $hora,
				"horaf" => "",
				"comentario" => $comentario,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$resultHorai = asistenciaModelo::valid_asistencia_horai_modelo($datos);

			if($resultHorai->num_rows==0){//NO SE HA REGISTRADO LA FECHA DE ENTRADA
				$query = asistenciaModelo ::agregar_asistencia_modelo($datos);
				
				if($query){
					if($marcarAsistencia_id == 0){
						$alert = [
							"alert" => "clear",
							"title" => "Registro almacenado",
							"text" => "El registro se ha almacenado correctamente",
							"type" => "success",
							"btn-class" => "btn-primary",
							"btn-text" => "¡Bien Hecho!",
							"form" => "formAsistencia",
							"id" => "proceso_asistencia",
							"valor" => "Registro",
							"funcion" => "listar_asistencia();getColaboradores();",
							"modal" => "",	
						];
					}else{
						$alert = [
							"alert" => "clear",
							"title" => "Registro almacenado",
							"text" => "El registro se ha almacenado correctamente",
							"type" => "success",
							"btn-class" => "btn-primary",
							"btn-text" => "¡Bien Hecho!",
							"form" => "formAsistencia",
							"id" => "proceso_asistencia",
							"valor" => "Registro",
							"funcion" => "listar_asistencia();getColaboradores();",
							"modal" => "modal_registrar_asistencia",	
						];						
					}
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
				$consultaHoraf = asistenciaModelo::valid_asistencia_horaf_modelo($datos)->fetch_assoc();
				$consultaHoraf['horaf'];

				$hora = $_POST['hora'];

				if($consultaHoraf['horaf']=="")//NO SE HA REGISTRADO LA FECHA DE SALIDA
				{
					$datos = [
						"colaborador" => $colaborador,
						"fecha" => $fecha,
						"horai" => "",
						"horaf" => $hora,
						"estado" => $estado,
						"comentario" => $comentario,
						"fecha_registro" => $fecha_registro,				
					];

					$query = asistenciaModelo ::update_asistencia_marcaje_modelo($datos);

					if($query){
						if($marcarAsistencia_id == 0){
							$alert = [
								"alert" => "clear",
								"title" => "Registro almacenado",
								"text" => "El registro se ha almacenado correctamente",
								"type" => "success",
								"btn-class" => "btn-primary",
								"btn-text" => "¡Bien Hecho!",
								"form" => "formAsistencia",
								"id" => "proceso_asistencia",
								"valor" => "Registro",
								"funcion" => "listar_asistencia();getColaboradores();",
								"modal" => "",	
							];
						}else{
							$alert = [
								"alert" => "clear",
								"title" => "Registro almacenado",
								"text" => "El registro se ha almacenado correctamente",
								"type" => "success",
								"btn-class" => "btn-primary",
								"btn-text" => "¡Bien Hecho!",
								"form" => "formAsistencia",
								"id" => "proceso_asistencia",
								"valor" => "Registro",
								"funcion" => "listar_asistencia();getColaboradores();",
								"modal" => "modal_registrar_asistencia",	
							];						
						}
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
						"title" => "Marcaje completado",
						"text" => "Lo sentimos su marcaje ha sido completado",
						"type" => "error",
						"btn-class" => "btn-danger",					
					];				
				}
			}
			
			return mainModel::sweetAlert($alert);			
		}	
	}
?>