<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class pagoFacturaModelo extends mainModel{
		protected function agregar_pago_factura_modelo($datos){

			$importe = $datos['importe'];

			if($datos['abono']>0){
				$importe = $datos['abono'];
			}

			$pagos_id = mainModel::correlativo("pagos_id", "pagos");
			$insert = "INSERT INTO pagos 
				VALUES('$pagos_id','".$datos['facturas_id']."','".$datos['tipo_pago_id']."','".$datos['fecha']."',
				'".$importe."','".$datos['efectivo']."','".$datos['cambio']."','".$datos['tarjeta']."',
				'".$datos['usuario']."','".$datos['estado']."','".$datos['empresa']."','".$datos['fecha_registro']."')";				
			
			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $result;		
		}
		
		protected function agregar_pago_detalles_factura_modelo($datos){	

			$pagos_detalles_id = mainModel::correlativo("pagos_detalles_id", "pagos_detalles");
			$insert = "INSERT INTO pagos_detalles 
				VALUES('$pagos_detalles_id','".$datos['pagos_id']."','".$datos['tipo_pago_id']."','".$datos['banco_id']."','".$datos['efectivo']."','".$datos['descripcion1']."','".$datos['descripcion2']."','".$datos['descripcion3']."')";

			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
		
			return $result;			
		}
		
		protected function cancelar_pago_modelo($pagos_id){
			$estado = 2;//Pago CANCELADA
			$update = "UPDATE pagos
				SET
					estado = '$estado'
				WHERE pagos_id = '$pagos_id'";
			
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $result;				
		}
		
		protected function consultar_codigo_pago_modelo($facturas_id){
			$query = "SELECT pagos_id
				FROM pagos
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;			
		}

		protected function consultar_numero_factura_pago_modelo($facturas_id){
			$query = "SELECT number
				FROM facturas
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;			
		}		

		protected function getLastInserted(){
			$query = "SELECT MAX(pagos_id) AS id
			FROM pagos";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;			
		}
		
		protected function update_status_factura($facturas_id){
			$estado = 2;//FACTURA PAGADA
			$update = "UPDATE facturas
				SET
					estado = '$estado'
				WHERE facturas_id = '$facturas_id'";
			
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
		
			return $result;					
		}	

		protected function update_status_factura_cuentas_por_cobrar($facturas_id,$estado = 2,$importe = ''){ //DONDE 2 ES PAGO REALIZADO			
			if($importe != '' || $importe == 0){
				$importe = ', saldo = '.$importe;
			}

			$update = "UPDATE cobrar_clientes
				SET
					estado = '$estado'
					$importe
				WHERE facturas_id = '$facturas_id'";
			
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			return $result;					
		}
		
		protected function consultar_factura_cuentas_por_cobrar($facturas_id){
			$query = "SELECT *
				FROM cobrar_clientes
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
		
			return $result;				
		}	

		protected function consultar_factura_fecha($facturas_id){
			$query = "SELECT fecha
				FROM facturas
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;				
		}
		
		protected function consultar_tipo_factura($facturas_id){
			$query = "SELECT tipo_factura
				FROM facturas
				WHERE facturas_id = '$facturas_id'";

			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;	
		}

		protected function consultar_numero_factura($facturas_id){
			$query = "SELECT number, secuencia_facturacion_id
				FROM facturas
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;	
		}

		protected function valid_pagos_factura($facturas_id){
			$query = "SELECT pagos_id
				FROM pagos
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;				
		}
		
		protected function valid_pagos_detalles_facturas($pagos_id, $tipo_pago){
			$query = "SELECT pagos_detalles_id
					FROM pagos_detalles
					WHERE pagos_id = '$pagos_id' AND tipo_pago_id = '$tipo_pago'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;				
		}

		protected function secuencia_facturacion_modelo($empresa_id, $documento_id){
			$query = "SELECT secuencia_facturacion_id, prefijo, siguiente AS 'numero', rango_final, fecha_limite, incremento, relleno
			   FROM secuencia_facturacion
			   WHERE activo = '1' AND empresa_id = '$empresa_id' AND documento_id = '$documento_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;
		}	

		protected function consulta_cuenta_pago_modelo($tipo_pago_id){
			$query = "SELECT cuentas_id
			   FROM tipo_pago
			   WHERE tipo_pago_id = '$tipo_pago_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;
		}			
		
		protected function actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero){
			$update = "UPDATE secuencia_facturacion
				SET
					siguiente = '$numero'
				WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";

			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;				
		}	

		protected function actualizar_estado_factura_proforma_pagos_modelo($facturas_id){
			$update = "UPDATE facturas_proforma
				SET
					estado = '1'
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;				
		}	
		
		protected function actualizar_factura($datos){
			$update = "UPDATE facturas
			SET
				estado = '".$datos['estado']."'
			WHERE facturas_id = '".$datos['facturas_id']."'";

			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;					
		}

		protected function actualizar_Secuenciafactura_PagoModelo($datos){
			$update = "UPDATE facturas
			SET
				secuencia_facturacion_id = '".$datos['secuencia_facturacion_id']."',
				number = '".$datos['number']."',
				fecha = CURDATE()
			WHERE facturas_id = '".$datos['facturas_id']."'";

			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;					
		}		
		
	    //METODO QUE PERMITE AGREGAR EL INGRESO DEL PAGO DEL CLIENTE
		protected function agregar_ingresos_contabilidad_pagos_modelo($datos){	
			$ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");		
			$insert = "INSERT INTO ingresos VALUES('".$ingresos_id."','".$datos['cuentas_id']."','".$datos['clientes_id']."','".$datos['empresa_id']."','".$datos['tipo_ingreso']."','".$datos['fecha']."','".$datos['factura']."','".$datos['subtotal']."','".$datos['descuento']."','".$datos['nc']."','".$datos['isv']."','".$datos['total']."','".$datos['observacion']."','".$datos['estado']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."','".$datos['recibide']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function agregar_movimientos_contabilidad_pagos_modelo($datos){
			$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
			$insert = "INSERT INTO movimientos_cuentas VALUES('$movimientos_cuentas_id','".$datos['cuentas_id']."','".$datos['empresa_id']."','".$datos['fecha']."','".$datos['ingreso']."','".$datos['egreso']."','".$datos['saldo']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function consultar_saldo_movimientos_cuentas_pagos_contabilidad($cuentas_id){
			$query = "SELECT ingreso, egreso, saldo
				FROM movimientos_cuentas
				WHERE cuentas_id = '$cuentas_id'
				ORDER BY movimientos_cuentas_id DESC LIMIT 1";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

		protected function consultar_numero_factura_modelo($facturas_id){
			$query = "SELECT number FROM facturas WHERE facturas_id = '$facturas_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

		protected function consultar_factura_proforma_pagos_modelo($facturas_id){
			$result = mainModel::getConsultaFacturaProforma($facturas_id);
			
			return $result;			
		}		

		//funcion para realizar todos lo pagos de factura
		protected function agregar_pago_factura_base($res) {
			$existeProforma = $this->verificarProforma($res['facturas_id']);
			$proformaNombre = $existeProforma ? "Factura Proforma" : "Factura Electronica";
			
			if ($res['estado_factura'] == 2 || $res['multiple_pago'] == 1) {
				return $this->procesarPagoCredito($res, $proformaNombre);
			} else {
				return $this->procesarPagoContado($res);
			}
		}
	
		protected function verificarProforma($facturaId) {
			$result = $this->consultar_factura_proforma_pagos_modelo($facturaId);
			return $result->num_rows > 0;
		}
	
		protected function procesarPagoCredito($res, $proformaNombre) {
			$saldoCredito = $this->obtenerSaldoCredito($res['facturas_id']);
			
			if ($res['abono'] > $saldoCredito) {
				return $this->crearAlertaError("El abono es mayor al importe");
			}
	
			$nuevoSaldo = $this->actualizarSaldoCredito($res, $saldoCredito);
			$query = $this->agregar_pago_factura_modelo($res);
	
			if (!$query) {
				return $this->crearAlertaError("No hemos podido procesar su solicitud");
			}
	
			$pagoId = $this->getLastInserted()->fetch_assoc()['id'];
			$this->agregarDetallePago($pagoId, $res);
			$this->registrarIngresoContable($res);
	
			if ($res['multiple_pago'] == 1 && $nuevoSaldo > 0) {
				return $this->procesarPagoMultiple($res, $proformaNombre);
			} else {
				return $this->finalizarProcesoPago($res, $proformaNombre, $nuevoSaldo);
			}
		}
	
		protected function procesarPagoContado($res) {
			if ($this->valid_pagos_factura($res['facturas_id'])->num_rows > 0) {
				return $this->crearAlertaError("Habilite nuevamente la seccion de Pagos Multiples");
			}
	
			$query = $this->agregar_pago_factura_modelo($res);
			
			if (!$query) {
				return $this->crearAlertaError("No hemos podido procesar su solicitud");
			}
	
			$pagoId = $this->getLastInserted()->fetch_assoc()['id'];
			$this->agregarDetallePago($pagoId, $res);
			
			$this->update_status_factura($res['facturas_id']);
			$this->update_status_factura_cuentas_por_cobrar($res['facturas_id'], 2, 0);
			
			$datosUpdate = ["facturas_id" => $res['facturas_id'], "estado" => 2];
			$this->actualizar_factura($datosUpdate);
	
			$this->registrarHistorial("Se registro el pago para la factura al contado");
	
			return [
				"type" => "success",
				"title" => "Registro modificado",
				"text" => "El registro se ha modificado correctamente",                
				"funcion" => "printBill(".$res['facturas_id'].",".$res['print_comprobante'].");listar_cuentas_por_cobrar_clientes();mailBill(".$res['facturas_id'].");getCollaboradoresModalPagoFacturas();",
				"closeAllModals" => true
			];
		}
	
		protected function obtenerSaldoCredito($facturaId) {
			$result = $this->consultar_factura_cuentas_por_cobrar($facturaId);
			return $result->num_rows > 0 ? $result->fetch_assoc()['saldo'] : 0;
		}
	
		protected function actualizarSaldoCredito($res, $saldoCredito) {
			$nuevoSaldo = $saldoCredito - $res['abono'];
			$estado = ($nuevoSaldo == 0) ? 2 : 1;
			$this->update_status_factura_cuentas_por_cobrar($res['facturas_id'], $estado, $nuevoSaldo);
			return $nuevoSaldo;
		}
	
		protected function agregarDetallePago($pagoId, $res) {
			$datos = [
				"pagos_id" => $pagoId,
				"tipo_pago_id" => $res['tipo_pago_id'],
				"banco_id" => $res['banco_id'],
				"efectivo" => $res['importe'],
				"descripcion1" => $res['referencia_pago1'],
				"descripcion2" => $res['referencia_pago2'],
				"descripcion3" => $res['referencia_pago3'],
			];
			
			if ($this->valid_pagos_detalles_facturas($pagoId, $res['tipo_pago_id'])->num_rows == 0) {
				$this->agregar_pago_detalles_factura_modelo($datos);
			}
		}
	
		protected function registrarIngresoContable($res) {
			$cuenta = $this->consulta_cuenta_pago_modelo($res['tipo_pago_id'])->fetch_assoc();
			$factura = mainModel::getFactura($res['facturas_id'])->fetch_assoc();
			
			$datosIngreso = [
				"clientes_id" => $factura['clientes_id'],
				"cuentas_id" => $cuenta['cuentas_id'],
				"empresa_id" => $res['empresa'],
				"fecha" => date("Y-m-d"),
				"factura" => str_pad($factura['numero_factura'], $factura['relleno'], "0", STR_PAD_LEFT),
				"subtotal" => $res['abono'],
				"isv" => 0,
				"descuento" => 0,
				"nc" => 0,
				"total" => $res['abono'],
				"observacion" => "Ingresos por venta Cierre de Caja",
				"estado" => 1,
				"fecha_registro" => date("Y-m-d H:i:s"),
				"colaboradores_id" => $res['colaboradores_id'],
				"tipo_ingreso" => 2,
				"recibide" => ""                                
			];
			
			$this->agregar_ingresos_contabilidad_pagos_modelo($datosIngreso);
			$this->registrarMovimientoCuenta($cuenta['cuentas_id'], $res);
		}
	
		protected function registrarMovimientoCuenta($cuentaId, $res) {
			$saldo = $this->consultar_saldo_movimientos_cuentas_pagos_contabilidad($cuentaId)->fetch_assoc();
			$saldoActual = $saldo['saldo'] ?? 0;
			$nuevoSaldo = $saldoActual + $res['abono'];
			
			$datosMovimiento = [
				"cuentas_id" => $cuentaId,
				"empresa_id" => $res['empresa'],
				"fecha" => date("Y-m-d"),
				"ingreso" => $res['abono'],
				"egreso" => 0,
				"saldo" => $nuevoSaldo,
				"colaboradores_id" => $res['colaboradores_id'],
				"fecha_registro" => date("Y-m-d H:i:s"),                
			];
			
			$this->agregar_movimientos_contabilidad_pagos_modelo($datosMovimiento);
		}
	
		protected function procesarPagoMultiple($res, $proformaNombre) {
			$this->registrarHistorial("Se registro el pago para la factura al contado, con pagos múltiples");
			
			$alert = [
				"type" => "success",
				"title" => "Registro pago multiples almacenado",
				"text" => "El registro se ha almacenado correctamente",                
				"funcion" => "pago(".$res['facturas_id'].");saldoFactura(".$res['facturas_id'].")"
			];
	
			$documento = $this->getDocumentoSecuenciaFacturacion($proformaNombre)->fetch_assoc();
			$resultFactura = $this->consultar_numero_factura_modelo($res['facturas_id'])->fetch_assoc();
			
			if(empty($resultFactura['number'])) {
				$this->actualizarSecuenciaFactura($res, $proformaNombre, $documento);
			}
	
			return $alert;
		}
	
		protected function finalizarProcesoPago($res, $proformaNombre, $saldoNuevo) {
			$accion = "";
			
			if($saldoNuevo === 0) {
				$documento = $this->getDocumentoSecuenciaFacturacion("Factura Electronica")->fetch_assoc();
				$secuencia = $this->secuencia_facturacion_modelo($res['empresa'], $documento['documento_id'])->fetch_assoc();
				
				if($proformaNombre === "Factura Proforma") {
					$nuevoNumero = $secuencia['numero'] + $secuencia['incremento'];
					$this->actualizar_secuencia_facturacion_modelo($secuencia['secuencia_facturacion_id'], $nuevoNumero);
				}
				
				$this->actualizar_estado_factura_proforma_pagos_modelo($res['facturas_id']);
				$accion = "printBill({$res['facturas_id']})";
			}
			
			$this->registrarHistorial("Se registro el pago para la factura al contado");
			
			return [
				"type" => "success",
				"title" => "Registro almacenado",
				"text" => "El registro se ha almacenado correctamente",                
				"funcion" => "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();".$accion,
				"form" => "formEfectivoBill",
				"closeAllModals" => true
			];
		}
	
		protected function crearAlertaError($mensaje) {
			return [
				"type" => "error",
				"title" => "Ocurrió un error inesperado",
				"text" => $mensaje
			];
		}
	
		protected function registrarHistorial($observacion) {
			$datos = [
				"modulo" => 'Pagos',
				"colaboradores_id" => $_SESSION['colaborador_id_sd'],        
				"status" => "Registrar",
				"observacion" => $observacion,
				"fecha_registro" => date("Y-m-d H:i:s")
			];    
			
			mainModel::guardarHistorial($datos);
		}
	
		protected function actualizarSecuenciaFactura($res, $proformaNombre, $documento) {
			if($res['tipo_pago'] == 1) {
				$secuencia = $this->secuencia_facturacion_modelo($res['empresa'], $documento['documento_id'])->fetch_assoc();
				$numero = $secuencia['numero'];
				$noFactura = $secuencia['prefijo']."".str_pad($secuencia['numero'], $secuencia['relleno'], "0", STR_PAD_LEFT);
			} else {
				$secuencia = $this->consultar_numero_factura($res['facturas_id'])->fetch_assoc();
				$numero = $secuencia['number'];    
				$noFactura = $secuencia['prefijo']."".str_pad($secuencia['numero'], $secuencia['relleno'], "0", STR_PAD_LEFT);            
			}
	
			$datosUpdate = [
				"facturas_id" => $res['facturas_id'],
				"estado" => 2,
				"number" => $numero
			];    
	
			$this->actualizar_factura($datosUpdate);
		}
	}