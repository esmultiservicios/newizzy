<?php
    if($peticionAjax){
        require_once "../modelos/tipoUsuarioAccesosModelo.php";
    }else{
        require_once "./modelos/tipoUsuarioAccesosModelo.php";
    }
	
	class tipoUsuarioAccesosControlador extends tipoUsuarioAccesosModelo{
		public function agregar_tipoUsuarioAccesos_controlador(){
			$privilegio_id = $_POST['permisos_tipo_user_id'];
			$permisos_nombre = $_POST['permisos_nombre'];
			$fecha_registro = date("Y-m-d H:i:s");	
			
			//######################INICIO REGISTRO######################
			//GUARDAR
			if (isset($_POST['opcion_guardar'])){
				$opcion_guardar = $_POST['opcion_guardar'];
			}else{
				$opcion_guardar = 2;
			}
			$opcion = "guardar";	
			
			$datos_opcion_guardar = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_guardar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_guardar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_guardar);
			
			if($result_opcion_guardar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_guardar);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_guardar);
			}	

			//MODIFICAR
			if (isset($_POST['opcion_editar'])){
				$opcion_editar = $_POST['opcion_editar'];
			}else{
				$opcion_editar = 2;
			}
			$opcion = "editar";	
			
			$datos_opcion_editar = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_editar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_modificar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_editar);
			
			if($result_opcion_modificar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_editar);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_editar);
			}	

			//ELIMINAR
			if (isset($_POST['opcion_eliminar'])){
				$opcion_eliminar = $_POST['opcion_eliminar'];
			}else{
				$opcion_eliminar = 2;
			}
			$opcion = "eliminar";	
			
			$datos_opcion_eliminar = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_eliminar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_eliminar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_eliminar);
			
			if($result_opcion_eliminar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_eliminar);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_eliminar);
			}	

			//CONSULTAR
			if (isset($_POST['opcion_consultar'])){
				$opcion_consultar = $_POST['opcion_consultar'];
			}else{
				$opcion_consultar = 2;
			}
			$opcion = "consultar";	
			
			$datos_opcion_consultar = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_consultar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_consultar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_consultar);
			
			if($result_opcion_consultar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_consultar);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_consultar);
			}	

			//IMPRIMIR
			if (isset($_POST['opcion_imprimir'])){
				$opcion_imprimir = $_POST['opcion_imprimir'];
			}else{
				$opcion_imprimir = 2;
			}
			$opcion = "imprimir";	
			
			$datos_opcion_imprimir = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_imprimir,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_imprimir = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_imprimir);
			
			if($result_opcion_imprimir->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_imprimir);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_imprimir);
			}

			//CREAR
			if (isset($_POST['opcion_crear'])){
				$opcion_crear = $_POST['opcion_crear'];
			}else{
				$opcion_crear = 2;
			}
			$opcion = "crear";	
			
			$datos_opcion_crear = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_crear,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_crear = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_crear);
			
			if($result_opcion_crear->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_crear);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_crear);
			}

			//REPORTES
			if (isset($_POST['opcion_reportes'])){
				$opcion_reportes = $_POST['opcion_reportes'];
			}else{
				$opcion_reportes = 2;
			}
			$opcion = "reportes";	
			
			$datos_opcion_reportes = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_reportes,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_reportes = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_reportes);
			
			if($result_opcion_reportes->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_reportes);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_reportes);
			}	

			//ACTUALIZAR
			if (isset($_POST['opcion_actualizar'])){
				$opcion_actualizar = $_POST['opcion_actualizar'];
			}else{
				$opcion_actualizar = 2;
			}
			$opcion = "actualizar";	
			
			$datos_opcion_actualizar = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_actualizar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_actualizar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_actualizar);
			
			if($result_opcion_actualizar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_actualizar);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_actualizar);
			}	
			
			//SELECCIONAR
			if (isset($_POST['opcion_view'])){
				$opcion_view = $_POST['opcion_view'];
			}else{
				$opcion_view = 2;
			}
			$opcion = "view";	
			
			$datos_opcion_view = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_actualizar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_actualizar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_view);
			
			if($result_opcion_actualizar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_view);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_view);
			}
			
			//PAGAR
			if (isset($_POST['opcion_pay'])){
				$opcion_pay = $_POST['opcion_pay'];
			}else{
				$opcion_pay = 2;
			}
			$opcion = "pay";	
			
			$datos_opcion_pay = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_pay,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_actualizar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_pay);
			
			if($result_opcion_actualizar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_pay);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_pay);
			}	
			
			//CAMBIAR CONTRASEÑA
			if (isset($_POST['opcion_cambiar'])){
				$opcion_cambiar = $_POST['opcion_cambiar'];
			}else{
				$opcion_cambiar = 2;
			}
			$opcion = "cambiar";	
			
			$datos_opcion_cambiar = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_cambiar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_cambiar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_cambiar);
			
			if($result_opcion_cambiar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_cambiar);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_cambiar);
			}	
			
			//CANCELAR
			if (isset($_POST['opcion_cancelar'])){
				$opcion_cancelar = $_POST['opcion_cancelar'];
			}else{
				$opcion_cancelar = 2;
			}
			$opcion = "cancelar";	
			
			$datos_opcion_cancelar = [
				"tipo_user_id" => $privilegio_id,
				"tipo_permiso" => $opcion,
				"estado" => $opcion_cancelar,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_opcion_cancelar = tipoUsuarioAccesosModelo::valid_tipoUsuarioAccesos_modelo($datos_opcion_cancelar);
			
			if($result_opcion_cancelar->num_rows>0){
				tipoUsuarioAccesosModelo::edit_tipoUsuarioAccesos_modelo($datos_opcion_cancelar);
			}else{
				tipoUsuarioAccesosModelo::agregar_tipoUsuarioAccesos_modelo($datos_opcion_cancelar);
			}				
			//######################FIN REGISTRO######################

			$alert = [
				"alert" => "clear",
				"title" => "Registro almacenado",
				"text" => "El registro se ha almacenado correctamente",
				"type" => "success",
				"btn-class" => "btn-primary",
				"btn-text" => "¡Bien Hecho!",
				"form" => "formPermisos",
				"id" => "pro_permisos",
				"valor" => "Asignar Permisos: ".$permisos_nombre,
				"funcion" => "listar_tipo_usuario();getPermisosControl(".$privilegio_id.",'".$permisos_nombre."');",
				"modal" => "",	
			];
			
			return mainModel::sweetAlert($alert);			
		}
	}
?>	