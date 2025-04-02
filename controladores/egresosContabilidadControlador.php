<?php
    if($peticionAjax){
        require_once "../modelos/egresosContabilidadModelo.php";
    }else{
        require_once "./modelos/egresosContabilidadModelo.php";
    }
	
	class egresosContabilidadControlador extends egresosContabilidadModelo{
		public function agregar_egresos_contabilidad_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			$proveedores_id = $_POST['proveedor_egresos'];
			$cuentas_id = mainModel::cleanStringConverterCase($_POST['cuenta_egresos']);
			$empresa_id = $_SESSION['empresa_id_sd'];
			$tipo_egreso = 2;//GASTOS
			$fecha = $_POST['fecha_egresos'];
			$factura = mainModel::cleanString($_POST['factura_egresos']);
			$subtotal = mainModel::cleanStringConverterCase($_POST['subtotal_egresos'] === "" ? 0 : $_POST['subtotal_egresos']);
			$isv = mainModel::cleanStringConverterCase($_POST['isv_egresos'] === "" ? 0 : $_POST['isv_egresos']);
			$descuento = mainModel::cleanStringConverterCase($_POST['descuento_egresos'] === "" ? 0 : $_POST['descuento_egresos']);
			$nc = mainModel::cleanStringConverterCase($_POST['nc_egresos'] === "" ? 0 : $_POST['nc_egresos']);
			$total = mainModel::cleanStringConverterCase($_POST['total_egresos'] === "" ? 0 : $_POST['total_egresos']);
			$observacion = mainModel::cleanString($_POST['observacion_egresos']);
			$categoria_gastos = mainModel::cleanString($_POST['categoria_gastos']);
			$estado = 1;
			$colaboradores_id = $_SESSION['colaborador_id_sd'];
			$fecha_registro = date("Y-m-d H:i:s");	
			$egresos_id = mainModel::correlativo("egresos_id", "egresos");

			$datos = [
				"egresos_id" => $egresos_id,
				"proveedores_id" => $proveedores_id === "" ? 0 : $proveedores_id,
				"cuentas_id" => $cuentas_id,
				"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
				"tipo_egreso" => $tipo_egreso,
				"fecha" => $fecha,
				"factura" => $factura,
				"subtotal" => $subtotal,
				"isv" => $isv,
				"descuento" => $descuento,
				"nc" => $nc,
				"total" => $total,
				"observacion" => $observacion,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,
				"colaboradores_id" => $colaboradores_id,
				"categoria_gastos" => $categoria_gastos
			];
			
			$resultEgresos = egresosContabilidadModelo::valid_egresos_cuentas_modelo($datos);
			
			if($resultEgresos->num_rows==0){
					$query = egresosContabilidadModelo::agregar_egresos_contabilidad_modelo($datos);
					
					if($query){
					//CONSULTAMOS EL SALDO DISPONIBLE PARA LA CUENTA
					$consulta_ingresos_contabilidad = egresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
					$saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) && $consulta_ingresos_contabilidad['saldo'] !== "" ? $consulta_ingresos_contabilidad['saldo'] : 0;
					
					$ingreso = 0;
					$egreso = $total;
					$saldo = $saldo_consulta - $egreso;
					
					//AGREGAMOS LOS MOVIMIENTOS DE LA CUENTA
					$datos_movimientos = [
						"cuentas_id" => $cuentas_id,
						"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
						"fecha" => $fecha,
						"ingreso" => $ingreso,
						"egreso" => $egreso,
						"saldo" => $saldo,
						"colaboradores_id" => $colaboradores_id,
						"fecha_registro" => $fecha_registro,				
					];
					
					egresosContabilidadModelo::agregar_movimientos_contabilidad_modelo($datos_movimientos);
				
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formEgresosContables",
						"id" => "pro_egresos_contabilidad",
						"valor" => "Registro",	
						"funcion" => "listar_gastos_contabilidad();getEmpresaEgresos(); getCuentaEgresos(); getProveedorEgresos();printGastos(".$egresos_id.");total_gastos_footer();",
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

		public function agregar_categoria_egresos_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			$categoria = $_POST['categoria'];	
			$estado = 1;
			$colaboradores_id = $_SESSION['colaborador_id_sd'];
			$fecha_registro = date("Y-m-d H:i:s");	
			$categoria_gastos_id = mainModel::correlativo("categoria_gastos_id ", "categoria_gastos");

			$datos = [
				"categoria_gastos_id" => $categoria_gastos_id,
				"nombre" => $categoria,
				"estado" => $estado,
				"usuario" => $colaboradores_id,
				"date_write" => $fecha_registro							
			];
			
			$resultCategoriaEgresos = egresosContabilidadModelo::valid_categoria_egresos_modelo($datos);
			
			if($resultCategoriaEgresos->num_rows==0){
				$query = egresosContabilidadModelo::agregar_categoria_egresos_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formCategoriaEgresos",
						"id" => "pro_categoriaEgresos",
						"valor" => "Registro",	
						"funcion" => "",
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

		public function edit_egresos_contabilidad_controlador(){
			$egresos_id = $_POST['egresos_id'];
			$proveedores_id = $_POST['proveedor_egresos'];
			$factura = mainModel::cleanString($_POST['factura_egresos']);
			$observacion = mainModel::cleanString($_POST['observacion_egresos']);
			$fecha = $_POST['fecha_egresos'];

			$datos = [
				"egresos_id" => $egresos_id,
				"proveedores_id" => $proveedores_id,
				"factura" => $factura,
				"fecha" => $fecha,
				"observacion" => $observacion,							
			];		
			
			$query = egresosContabilidadModelo::edit_egresos_contabilidad_modelo($datos);

			if($query){
				$alert = [
					"alert" => "clear",
					"title" => "Registro editado",
					"text" => "Registro editado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formEgresosContables",
					"id" => "pro_egresos_contabilidad",
					"valor" => "Registro",	
					"funcion" => "listar_gastos_contabilidad();getEmpresaEgresos(); getCuentaEgresos(); getProveedorEgresos();printGastos(".$egresos_id.")",
					"modal" => "modalEgresosContables",
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

		public function edit_categoria_egresos_contabilidad_controlador(){
			$categoria_gastos_id = $_POST['categoria_gastos_id'];
			$categoria = $_POST['categoria'];

			$datos = [
				"categoria_gastos_id" => $categoria_gastos_id,
				"nombre" => $categoria							
			];		
			
			$resultCategoriaEgresos = egresosContabilidadModelo::valid_categoria_egresos_modelo($datos);
			
			if($resultCategoriaEgresos->num_rows==0){
				echo "estas aqui";
				$query = egresosContabilidadModelo::edit_categoria_egresos_contabilidad_modelo($datos);

				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro editado",
						"text" => "Registro editado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formUpdateCategoriaEgresos",
						"id" => "pro_categoriaEgresos",
						"valor" => "Registro",	
						"funcion" => "listar_categoria_egresos();",
						"modal" => "modalUpdateCategoriasEgresos",
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

		public function cancel_egresos_contabilidad_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			$egresos_id = $_POST['egresos_id'];
			$proveedores_id = $_POST['proveedor_egresos'];
			$cuentas_id = mainModel::cleanStringConverterCase($_POST['cuenta_egresos']);
			$empresa_id = mainModel::cleanStringConverterCase($_POST['empresa_egresos']);
			$fecha = mainModel::cleanString($_POST['fecha_egresos']);
			$factura = mainModel::cleanStringConverterCase($_POST['factura_egresos']);
			$subtotal = mainModel::cleanStringConverterCase($_POST['subtotal_egresos']);
			$isv = mainModel::cleanStringConverterCase($_POST['isv_egresos']);
			$descuento = mainModel::cleanStringConverterCase($_POST['descuento_egresos']);
			$nc = mainModel::cleanStringConverterCase($_POST['nc_egresos']);
			$total = mainModel::cleanStringConverterCase($_POST['total_egresos']);
			$observacion = mainModel::cleanString($_POST['observacion_egresos']);
			$estado = 2;
			$tipo_egreso = 2;//GASTOS
			$colaboradores_id = $_SESSION['colaborador_id_sd'];
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"egresos_id" => $egresos_id,
				"proveedores_id" => $proveedores_id,
				"cuentas_id" => $cuentas_id,
				"empresa_id" => $empresa_id,
				"tipo_egreso" => $tipo_egreso,
				"fecha" => $fecha,
				"factura" => $factura,
				"subtotal" => $subtotal,
				"isv" => $isv,
				"descuento" => $descuento,
				"nc" => $nc,
				"total" => $total,
				"observacion" => $observacion,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$result_valid_egresos = egresosContabilidadModelo::valid_egresos_cuentas_modelo($puestos_id);
			
			if($result_valid_puestos_colaborador_modelo->num_rows>0 ){
				$query = egresosContabilidadModelo::cancel_egresos_contabilidad_modelo($puestos_id);
								
				if($query){
					//CONSULTAMOS EL SALDO DISPONIBLE PARA LA CUENTA
					$consulta_ingresos_contabilidad = egresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
					$saldo_consulta = $consulta_ingresos_contabilidad['saldo'];	
					$ingreso = $total;
					$egreso = 0;
					$saldo = $saldo_consulta + $ingreso;
					
					//AGREGAMOS LOS MOVIMIENTOS DE LA CUENTA
					$datos_movimientos = [
						"cuentas_id" => $cuentas_id,
						"empresa_id" => $empresa_id,
						"fecha" => $fecha,
						"ingreso" => $ingreso,
						"egreso" => $egreso,
						"saldo" => $saldo,
						"colaboradores_id" => $colaboradores_id,
						"fecha_registro" => $fecha_registro,				
					];
					
					egresosContabilidadModelo::agregar_movimientos_contabilidad_modelo($datos_movimientos);
					
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formEgresosContables",
						"id" => "pro_egresos_contabilidad",
						"valor" => "Eliminar",
						"funcion" => "listar_gastos_contabilidad();getEmpresaEgresos(); getCuentaEgresos(); getProveedorEgresos();",
						"modal" => "modalEgresosContables",
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