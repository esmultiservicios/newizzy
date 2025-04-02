<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class aperturaCajaModelo extends mainModel{
		protected function agregar_apertura_caja_modelo($datos) {
			// Genera el ID de apertura
			$apertura_id = mainModel::correlativo("apertura_id", "apertura");

			// Consulta SQL para insertar una nueva apertura de caja
			$insert = "
				INSERT INTO apertura (
					apertura_id, 
					colaboradores_id, 
					fecha, 
					factura_inicial, 
					factura_final, 
					apertura, 
					neto, 
					estado, 
					fecha_registro,
					empresa_id
				) 
				VALUES (
					'$apertura_id', 
					'".$datos['colaboradores_id']."', 
					'".$datos['fecha']."', 
					'".$datos['factura_inicial']."', 
					'".$datos['factura_final']."', 
					'".$datos['monto']."', 
					'".$datos['neto']."', 
					'".$datos['estado']."', 
					'".$datos['fecha_registro']."',
					'".$datos['empresa_id_sd']."'
				)
			";

			// Ejecuta la consulta y maneja errores
			$sql = mainModel::connection()->query($insert) 
				or die(mainModel::connection()->error);

			return $sql;
		}

		
		protected function agregar_ingresos_contabilidad_modelo($datos) {
			// Genera el ID de ingresos
			$ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");

			// Consulta SQL para insertar un nuevo ingreso en contabilidad
			$insert = "
				INSERT INTO ingresos (
					ingresos_id, 
					cuentas_id, 
					clientes_id, 
					empresa_id, 
					tipo_ingreso, 
					fecha, 
					factura, 
					subtotal, 
					descuento, 
					nc, 
					impuesto, 
					total, 
					observacion, 
					estado, 
					colaboradores_id, 
					fecha_registro, 
					recibide
				) 
				VALUES (
					'$ingresos_id', 
					'".$datos['cuentas_id']."', 
					'".$datos['clientes_id']."', 
					'".$datos['empresa_id']."', 
					'".$datos['tipo_ingreso']."', 
					'".$datos['fecha']."', 
					'".$datos['factura']."', 
					'".$datos['subtotal']."', 
					'".$datos['descuento']."', 
					'".$datos['nc']."', 
					'".$datos['isv']."', 
					'".$datos['total']."', 
					'".$datos['observacion']."', 
					'".$datos['estado']."', 
					'".$datos['colaboradores_id']."', 
					'".$datos['fecha_registro']."', 
					'".$datos['recibide']."'
				)
			";

			// Ejecuta la consulta y maneja errores
			$sql = mainModel::connection()->query($insert) 
				or die(mainModel::connection()->error);

			return $sql;
		}


		protected function getNombreClienteModelo($clientes_id){
			$result = mainModel::getNombreCliente($clientes_id);
			
			return $result;	
		}
		
		protected function agregar_movimientos_contabilidad_modelo($datos) {
			// Genera el ID para movimientos de cuentas
			$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");

			// Consulta SQL para insertar un nuevo movimiento en contabilidad
			$insert = "
				INSERT INTO movimientos_cuentas (
					movimientos_cuentas_id, 
					cuentas_id, 
					empresa_id, 
					fecha, 
					ingreso, 
					egreso, 
					saldo, 
					colaboradores_id, 
					fecha_registro
				) 
				VALUES (
					'$movimientos_cuentas_id', 
					'".$datos['cuentas_id']."', 
					'".$datos['empresa_id']."', 
					'".$datos['fecha']."', 
					'".$datos['ingreso']."', 
					'".$datos['egreso']."', 
					'".$datos['saldo']."', 
					'".$datos['colaboradores_id']."', 
					'".$datos['fecha_registro']."'
				)
			";

			// Ejecuta la consulta y maneja errores
			$sql = mainModel::connection()->query($insert) 
				or die(mainModel::connection()->error);

			return $sql;
		}

		
		protected function consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id){
			$query = "SELECT ingreso, egreso, saldo
				FROM movimientos_cuentas
				WHERE cuentas_id = '$cuentas_id'
				ORDER BY movimientos_cuentas_id DESC LIMIT 1";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

		protected function valid_ingreso_cuentas_modelo($datos){
			$query = "SELECT ingresos_id 
				FROM ingresos 
				WHERE factura = '".$datos['factura']."' AND clientes_id = '".$datos['clientes_id']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function valid_apertura_caja_modelo($datos){
			$query = "SELECT apertura_id 
				FROM apertura 
				WHERE colaboradores_id = '".$datos['colaboradores_id']."' AND estado = 1";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

			return $sql;
		}
		
		protected function valid_open_caja($datos){
			$query = "SELECT apertura 
				FROM cajas 
				WHERE apertura_id = '".$datos['apertura_id']."' AND estado = 1";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function cerrar_caja_modelo($datos){
			$update = "UPDATE apertura
			SET 
				factura_inicial = '".$datos['factura_inicial']."',
				factura_final = '".$datos['factura_final']."',	
				neto = '".$datos['neto']."',					
				estado = '".$datos['estado']."'
			    WHERE fecha = '".$datos['fecha']."' AND colaboradores_id = '".$datos['colaboradores_id']."' AND estado = 1";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;				
		}
		
		protected function consultar_factura_inicial($apertura_id){
			$query = "SELECT f.number AS 'numero', sf.prefijo AS 'prefijo', sf.rango_final As 'rango_final', sf.relleno AS 'relleno'
				FROM facturas AS f
				INNER JOIN secuencia_facturacion AS sf
				ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
				WHERE apertura_id = '$apertura_id' AND estado = 2
				ORDER BY f.facturas_id ASC LIMIT 1";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

			return $sql;			
		}
		
		protected function consultar_factura_final($apertura_id){
			$query = "SELECT f.number AS 'numero', sf.prefijo AS 'prefijo', sf.rango_final As 'rango_final', sf.fecha_limite AS 'fecha_limite', sf.relleno AS 'relleno'
				FROM facturas AS f
				INNER JOIN secuencia_facturacion AS sf
				ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
				WHERE apertura_id = '$apertura_id' AND estado = 2 
				ORDER BY f.facturas_id DESC LIMIT 1";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

			return $sql;			
		}	

		protected function consulta_facturas($apertura_id){
			$query = "SELECT facturas_id
			FROM facturas
			WHERE apertura_id = '$apertura_id' AND estado = 2";
		
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function consulta_detalles_facturas($facturas_id){
			$result = mainModel::getDetalleFactura($facturas_id);
			
			return $result;			
		}

		protected function neto_factura($datos){
			$query = "SELECT SUM(importe) AS 'neto'
				FROM facturas
				WHERE fecha = '".$datos['fecha']."' AND usuario = '".$datos['colaboradores_id']."' AND estado = 2";
		
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
		
		protected function valid_config_apertura_modelo($accion){
			$query = "SELECT activar AS validar
				FROM config
				WHERE accion = '$accion'";

			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;
		}			
	}
?>