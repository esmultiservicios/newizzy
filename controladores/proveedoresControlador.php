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
					"text"  => $validacion['mensaje'],
					"type"  => "error",
					"funcion" => "window.location.href = '".$validacion['redireccion']."'"
				]);
			}
		
			// Sanitizar
			$nombre = trim(mainModel::cleanString($_POST['nombre_proveedores'] ?? ''));
			$rtn    = trim(mainModel::cleanString($_POST['rtn_proveedores'] ?? ''));
			$fecha  = mainModel::cleanString($_POST['fecha_proveedores'] ?? '');
			$depto  = isset($_POST['departamento_proveedores']) ? (int)$_POST['departamento_proveedores'] : 0;
			$muni   = isset($_POST['municipio_proveedores']) ? (int)$_POST['municipio_proveedores'] : 0;
			$local  = mainModel::cleanString($_POST['dirección_proveedores'] ?? '');
			$tel    = mainModel::cleanString($_POST['telefono_proveedores'] ?? '');
			$correo = mainModel::cleanStringStrtolower($_POST['correo_proveedores'] ?? '');
			$colab  = $_SESSION['colaborador_id_sd'] ?? 1;
			$freg   = date("Y-m-d H:i:s");
			$estado = 1;
		
			// Validaciones duras
			if ($nombre === '' || $rtn === '') {
				return mainModel::showNotification([
					"type"  => "error",
					"title" => "Campos obligatorios",
					"text"  => "Nombre y RTN del proveedor son obligatorios."
				]);
			}
		
			// RTN: exactamente 14 dígitos numéricos (según tu tabla char(14))
			if (!preg_match('/^\d{14}$/', $rtn)) {
				return mainModel::showNotification([
					"type"  => "error",
					"title" => "RTN inválido",
					"text"  => "El RTN debe contener exactamente 14 dígitos numéricos."
				]);
			}
		
			// Teléfono (opcional) máximo 8 dígitos
			if ($tel !== '' && !preg_match('/^\d{1,8}$/', $tel)) {
				return mainModel::showNotification([
					"type"  => "error",
					"title" => "Teléfono inválido",
					"text"  => "El teléfono debe tener máximo 8 dígitos numéricos."
				]);
			}
		
			// Correo (opcional) formato
			if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
				return mainModel::showNotification([
					"type"  => "error",
					"title" => "Correo inválido",
					"text"  => "El formato del correo no es válido."
				]);
			}
		
			// Límite por plan (si aplica)
			$mainModel = new mainModel();
			$planConfig = $mainModel->getPlanConfiguracionMainModel();
			if (isset($planConfig['proveedores'])) {
				$limite = (int)$planConfig['proveedores'];
				if ($limite === 0) {
					return $mainModel->showNotification([
						"type"  => "error",
						"title" => "Acceso restringido",
						"text"  => "Su plan actual no permite registrar proveedores."
					]);
				}
				$totalRegs = (int)proveedoresModelo::getTotalProveedoresRegistrados();
				if ($totalRegs >= $limite) {
					return $mainModel->showNotification([
						"type"  => "error",
						"title" => "Límite alcanzado",
						"text"  => "Límite de proveedores alcanzado (Máximo: $limite)."
					]);
				}
			}
		
			// Duplicado por RTN
			if (proveedoresModelo::valid_proveedores_modelo($rtn)->num_rows > 0) {
				return mainModel::showNotification([
					"type"  => "error",
					"title" => "Duplicado",
					"text"  => "Ya existe un proveedor registrado con ese RTN."
				]);
			}
		
			// Armar datos
			$datos = [
				"nombre"          => $nombre,
				"rtn"             => $rtn,
				"fecha"           => $fecha ?: date("Y-m-d"),
				"departamento_id" => $depto,
				"municipio_id"    => $muni,
				"localidad"       => $local,
				"telefono"        => $tel,
				"correo"          => $correo,
				"colaborador_id"  => $colab,
				"fecha_registro"  => $freg,
				"estado"          => $estado,
			];
		
			// Insert
			$nuevoID = proveedoresModelo::agregar_proveedores_model($datos);
			if (!$nuevoID) {
				return mainModel::showNotification([
					"title" => "Error",
					"text"  => "No se pudo registrar el proveedor",
					"type"  => "error"
				]);
			}
		
			// Historial (corrijo el módulo y el texto)
			mainModel::guardarHistorial([
				"modulo" => 'Proveedores',
				"colaboradores_id" => $_SESSION['colaborador_id_sd'],
				"status" => "Registro",
				"observacion" => "Se registró el proveedor {$nombre} con RTN {$rtn}",
				"fecha_registro" => date("Y-m-d H:i:s")
			]);
		
			return mainModel::showNotification([
				"type"    => "success",
				"title"   => "Registro exitoso",
				"text"    => "Proveedor registrado correctamente",
				"form"    => "formProveedores",
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

			$estado = isset($_POST['proveedores_activo']) && $_POST['proveedores_activo'] == 'on' ? 1 : 0;	
			
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