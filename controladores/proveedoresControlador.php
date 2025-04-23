<?php
    if($peticionAjax){
        require_once "../modelos/proveedoresModelo.php";
    }else{
        require_once "./modelos/proveedoresModelo.php";
    }
	
	class proveedoresControlador extends proveedoresModelo{
		public function agregar_proveedores_controlador(){
			// Validar sesión primero
			$validacion = mainModel::validarSesion();
			if($validacion['error']) {
				return mainModel::showNotification([
					"title" => "Error de sesión",
					"text" => $validacion['mensaje'],
					"type" => "error",
					"funcion" => "window.location.href = '".$validacion['redireccion']."'"
				]);
			}
			
			$nombre = mainModel::cleanString($_POST['nombre_proveedores']);
			$rtn = mainModel::cleanString($_POST['rtn_proveedores']);
			$fecha = mainModel::cleanString($_POST['fecha_proveedores']);			
			$departamento_id = isset($_POST['departamento_proveedores']) ? intval($_POST['departamento_proveedores']) : 0;
			$municipio_id = isset($_POST['municipio_proveedores']) ? intval($_POST['municipio_proveedores']) : 0;	
			$localidad = mainModel::cleanString($_POST['dirección_proveedores']);
			$telefono = mainModel::cleanString($_POST['telefono_proveedores']);
			$correo = mainModel::cleanStringStrtolower($_POST['correo_proveedores']);
			$colaborador_id = $_SESSION['colaborador_id_sd'];
			$fecha_registro = date("Y-m-d H:i:s");
			$estado = 1;			

			$datos = [
				"nombre" => $nombre,
				"rtn" => $rtn,
				"fecha" => $fecha,
				"departamento_id" => $departamento_id,
				"municipio_id" => $municipio_id,
				"localidad" => $localidad,
				"telefono" => $telefono,
				"correo" => $correo,
				"colaborador_id" => $colaborador_id,
				"fecha_registro" => $fecha_registro,
				"estado" => $estado,				
			];
			
			if(!proveedoresModelo::agregar_proveedores_model($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el proveedor",
					"type" => "error"
				]);
			}

			// Registrar en historial
			mainModel::guardarHistorial([
				"modulo" => 'Clientes',
				"colaboradores_id" => $_SESSION['colaborador_id_sd'],
				"status" => "Registro",
				"observacion" => "Se registró el cliente {$datos['nombre']} con RTN {$datos['rtn']}",
				"fecha_registro" => date("Y-m-d H:i:s")
			]);
			
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Proveedor registrado correctamente",           
				"form" => "formProveedores",
				"funcion" => "listar_proveedores();getDepartamentoProveedores();getMunicipiosProveedores(0);getProveedorIngresos();getProveedorEgresos();listar_proveedores_ingresos_contabilidad_buscar();listar_proveedores_compras_buscar();listar_proveedores_egresos_contabilidad_buscar();"
			]);								
		}
		
		public function edit_proveedores_controlador(){
			$proveedores_id = $_POST['proveedores_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['nombre_proveedores']);		
			$departamento_id = isset($_POST['departamento_proveedores']) ? intval($_POST['departamento_proveedores']) : 0;
			$municipio_id = isset($_POST['municipio_proveedores']) ? intval($_POST['municipio_proveedores']) : 0;
			$localidad = mainModel::cleanString($_POST['dirección_proveedores']);
			$telefono = mainModel::cleanString($_POST['telefono_proveedores']);
			$correo = mainModel::cleanStringStrtolower($_POST['correo_proveedores']);
			$rtn = mainModel::cleanString($_POST['rtn_proveedores']);

			$estado = isset($_POST['colaboradores_activo']) ? 1 : 0;	
			
			$datos = [
				"proveedores_id" => $proveedores_id,
				"nombre" => $nombre,
				"departamento_id" => $departamento_id,
				"municipio_id" => $municipio_id,
				"localidad" => $localidad,
				"telefono" => $telefono,
				"correo" => $correo,
				"estado" => $estado,
				"rtn" => $rtn,
			];

			if(!proveedoresModelo::edit_proveedores_modelo($datos)){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo actualizar el proveedor",                
				]);
			}

			// Registrar en historial
			mainModel::guardarHistorial([
				"modulo" => 'Clientes',
				"colaboradores_id" => $_SESSION['colaborador_id_sd'],
				"status" => "Edición",
				"observacion" => "Se editó el proveedor {$datos['nombre']} con RTN {$datos['rtn']}",
				"fecha_registro" => date("Y-m-d H:i:s")
			]);
			
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Proveedor actualizado correctamente",
				"funcion" => "listar_proveedores();getDepartamentoProveedores();getMunicipiosProveedores(0);"
			]);
		}
		
		public function delete_proveedores_controlador(){
			$proveedores_id = $_POST['proveedores_id'];
			
			$campos = ['nombre', 'rtn'];
			$tabla = "proveedores";;
			$condicion = "proveedores_id = {$proveedores_id}";

			$proveedor = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($proveedor)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Proveedor no encontrado"
				]);
				exit();
			}
			
			$nombre = $proveedor[0]['nombre'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(proveedoresModelo::valid_proveedores_compras($proveedores_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El proveedor {$nombre} tiene compras asociadas"
				]);
				exit();                
			}

			if(!proveedoresModelo::delete_proveedores_modelo($proveedores_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el proveedor {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Proveedor {$nombre} eliminado correctamente"
			]);
			exit();					
		}		
	}