<?php
    if($peticionAjax){
        require_once "../modelos/privilegioModelo.php";
        require_once "../core/correo/NotificationService.php";
    }else{
        require_once "./modelos/privilegioModelo.php";
        require_once "./core/correo/NotificationService.php";
    }
	
	class privilegioControlador extends privilegioModelo{
        private function notificarPrivilegio($titulo,$resumen,array $detalles=[],array $cambios=[],$tipo='audit',$privilegioId=0){
            try{$svc=new NotificationService();$db=$svc->currentDbName();$ctx=$svc->clientContextFromDb($db);$r=$svc->notifyClientAndMain($db,0,$ctx['cliente_nombre']??'Cliente IZZY','IZZY · '.$titulo,$resumen,$detalles,$cambios,'IZZY · Auditoría · '.$titulo,$resumen,$detalles,$cambios,$tipo);if($privilegioId>0)$svc->notifyAffectedUsersByField($db,0,'privilegio_id',$privilegioId,'IZZY · '.$titulo,$resumen,$cambios);return $r;}catch(Throwable $e){error_log('Privilegio - notificación: '.$e->getMessage());return [];}
        }
		public function agregar_privilegio_controlador(){
			$nombre = mainModel::cleanStringConverterCase($_POST['privilegios_nombre']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			if(privilegioModelo::valid_privilegios_modelo($nombre)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el privilegio",                
				]);                
			}

			if(!privilegioModelo::agregar_privilegios_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el privilegio",
					"type" => "error"
				]);
			}

            $this->notificarPrivilegio(
                'Privilegio creado',
                'Se creó un nuevo privilegio.',
                ['Privilegio'=>$nombre, 'Estado'=>'Activo'],
                [],
                'success'
            );

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Privilegio registrado correctamente",           
				"form" => "formPrivilegios",
				"funcion" => "listar_privilegio();"
			]);		
		}
		
		public function edit_privilegio_controlador(){
			$privilegio_id = $_POST['privilegio_id_'];
			$nombre = mainModel::cleanStringConverterCase($_POST['privilegios_nombre']);
			
			$estado = isset($_POST['privilegio_activo']) && $_POST['privilegio_activo'] == 'on' ? 1 : 0;

            $privilegioAnterior = null;
            try {
                $cnNoti = mainModel::connection();
                $stNoti = $cnNoti->prepare("SELECT nombre, estado FROM privilegio WHERE privilegio_id = ? LIMIT 1");
                if ($stNoti) {
                    $idNoti = (int)$privilegio_id;
                    $stNoti->bind_param("i", $idNoti);
                    $stNoti->execute();
                    $privilegioAnterior = $stNoti->get_result()->fetch_assoc();
                    $stNoti->close();
                }
            } catch (Throwable $e) {
                error_log('Privilegio - lectura anterior: '.$e->getMessage());
            }
			
			$datos = [
				"privilegio_id" => $privilegio_id,
				"nombre" => $nombre,
				"estado" => $estado,
			];		

			if(!privilegioModelo::edit_privilegio_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el privilegio",
					"type" => "error"
				]);
			}

            $cambiosPrivilegio = [];
            if ($privilegioAnterior) {
                if (trim((string)$privilegioAnterior['nombre']) !== trim((string)$nombre)) {
                    $cambiosPrivilegio['Privilegio'] = ['anterior'=>$privilegioAnterior['nombre'], 'nuevo'=>$nombre];
                }
                if ((int)$privilegioAnterior['estado'] !== (int)$estado) {
                    $cambiosPrivilegio['Estado'] = [
                        'anterior'=>(int)$privilegioAnterior['estado'] === 1 ? 'Activo' : 'Inactivo',
                        'nuevo'=>(int)$estado === 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }
            $this->notificarPrivilegio(
                'Privilegio actualizado',
                'Se actualizó un privilegio.',
                ['Privilegio'=>$nombre],
                $cambiosPrivilegio,
                'security',
                (int)$privilegio_id
            );

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Privilegio actualizado correctamente",
				"funcion" => "listar_privilegio();"
			]);		
		}
		
		public function delete_privilegio_controlador(){
			$privilegio_id = $_POST['privilegio_id_'];
			
			$campos = ['privilegio_id', 'nombre'];
			$tabla = "privilegio";
			$condicion = "privilegio_id = {$privilegio_id}";

			$privilegio = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($privilegio)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Privilegio no encontrado"
				]);
				exit();
			}
			
			$nombre = $privilegio[0]['nombre'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(privilegioModelo::valid_privilegio_usuarios($privilegio_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El privilegio {$nombre} tiene usuarios asociados"
				]);
				exit();                
			}

			if(!privilegioModelo::delete_privilegio_modelo($privilegio_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el privilegio {$nombre}"
				]);
				exit();
			}
			
            $this->notificarPrivilegio(
                'Privilegio eliminado',
                'Se eliminó un privilegio.',
                ['Privilegio'=>$nombre, 'ID'=>(int)$privilegio_id],
                [],
                'audit'
            );

			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Privilegio {$nombre} eliminado correctamente"
			]);
			exit();	
		}		
	}