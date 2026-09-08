<?php
    if($peticionAjax){
        require_once "../modelos/tipoUsuarioModelo.php";
        require_once "../core/correo/NotificationService.php";
    }else{
        require_once "./modelos/tipoUsuarioModelo.php";
        require_once "./core/correo/NotificationService.php";
    }
	
	class tipoUsuarioControlador extends tipoUsuarioModelo{
        private function notificarTipo($titulo,$resumen,array $detalles=[],array $cambios=[],$tipo='audit',$tipoId=0){
            try{$svc=new NotificationService();$db=$svc->currentDbName();$ctx=$svc->clientContextFromDb($db);$r=$svc->notifyClientAndMain($db,0,$ctx['cliente_nombre']??'Cliente IZZY','IZZY · '.$titulo,$resumen,$detalles,$cambios,'IZZY · Auditoría · '.$titulo,$resumen,$detalles,$cambios,$tipo);if($tipoId>0)$svc->notifyAffectedUsersByField($db,0,'tipo_user_id',$tipoId,'IZZY · '.$titulo,$resumen,$cambios);return $r;}catch(Throwable $e){error_log('Tipo usuario - notificación: '.$e->getMessage());return [];}
        }
		public function agregar_tipo_usuario_controlador(){
			$nombre = mainModel::cleanStringConverterCase($_POST['tipo_usuario_nombre']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,					
			];
			
			if(tipoUsuarioModelo::valid_tipo_usuario_modelo(["nombre" => $nombre])->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el tipo de usuario",                
				]);                
			}

			if(!tipoUsuarioModelo::agregar_tipo_usuario_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el tipo de usuario",
					"type" => "error"
				]);
			}

            $this->notificarTipo(
                'Tipo de usuario creado',
                'Se creó un nuevo tipo de usuario/permisos.',
                ['Tipo de usuario'=>$nombre, 'Estado'=>'Activo'],
                [],
                'success'
            );

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Tipo de usuario registrado correctamente",           
				"form" => "formTipoUsuario",
				"funcion" => "listar_tipo_usuario();"
			]);			
		}
		
		public function edit_tipo_usuario_controlador(){
			$tipo_user_id = $_POST['tipo_user_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['tipo_usuario_nombre']);
			
			if (isset($_POST['tipo_usuario_activo'])){
				$estado = $_POST['tipo_usuario_activo'];
			}else{
				$estado = 2;
			}
			
            $tipoAnterior = null;
            try {
                $cnNoti = mainModel::connection();
                $stNoti = $cnNoti->prepare("SELECT nombre, estado FROM tipo_user WHERE tipo_user_id = ? LIMIT 1");
                if ($stNoti) {
                    $idNoti = (int)$tipo_user_id;
                    $stNoti->bind_param("i", $idNoti);
                    $stNoti->execute();
                    $tipoAnterior = $stNoti->get_result()->fetch_assoc();
                    $stNoti->close();
                }
            } catch (Throwable $e) {
                error_log('Tipo usuario - lectura anterior: '.$e->getMessage());
            }

			$datos = [
				"tipo_user_id" => $tipo_user_id,
				"nombre" => $nombre,
				"estado" => $estado,				
			];		

			if(!tipoUsuarioModelo::edit_tipo_usuario_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el tipo de usuario",
					"type" => "error"
				]);
			}

            $cambiosTipo = [];
            if ($tipoAnterior) {
                if (trim((string)$tipoAnterior['nombre']) !== trim((string)$nombre)) {
                    $cambiosTipo['Tipo / permisos'] = ['anterior'=>$tipoAnterior['nombre'], 'nuevo'=>$nombre];
                }
                if ((int)$tipoAnterior['estado'] !== (int)$estado) {
                    $cambiosTipo['Estado'] = [
                        'anterior'=>(int)$tipoAnterior['estado'] === 1 ? 'Activo' : 'Inactivo',
                        'nuevo'=>(int)$estado === 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }
            $this->notificarTipo(
                'Tipo de usuario actualizado',
                'Se actualizó un tipo de usuario/permisos.',
                ['Tipo de usuario'=>$nombre],
                $cambiosTipo,
                'security',
                (int)$tipo_user_id
            );

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Tipo de usuario actualizado correctamente",           
				"form" => "formTipoUsuario",
				"funcion" => "listar_tipo_usuario();"
			]);			
		}
		
		public function delete_tipo_usuario_controlador(){
			$tipo_user_id = $_POST['tipo_user_id'];
			
			$campos = ['tipo_user_id', 'nombre'];
			$tabla = "tipo_user";
			$condicion = "tipo_user_id = {$tipo_user_id}";

			$tipo_usuario = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($tipo_usuario)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Tipo de usuario no encontrado"
				]);
				exit();
			}
			
			$nombre = $tipo_usuario[0]['nombre'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(tipoUsuarioModelo::valid_tipo_user_usuarios($tipo_user_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El tipo de usuario {$nombre} tiene usuarios asociados"
				]);
				exit();                
			}

			if(!tipoUsuarioModelo::delete_tipo_usuario_modelo($tipo_user_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el tipo de usuario {$nombre}"
				]);
				exit();
			}
			
            $this->notificarTipo(
                'Tipo de usuario eliminado',
                'Se eliminó un tipo de usuario/permisos.',
                ['Tipo de usuario'=>$nombre, 'ID'=>(int)$tipo_user_id],
                [],
                'audit'
            );

			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Tipo de usuario {$nombre} eliminado correctamente"
			]);
			exit();			
		}
	}