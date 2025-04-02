<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class egresosContabilidadModelo extends mainModel{
		protected function agregar_egresos_contabilidad_modelo($datos){
			$insert = "INSERT INTO egresos VALUES('".$datos['egresos_id']."','".$datos['cuentas_id']."','".$datos['proveedores_id']."','".$datos['empresa_id']."','".$datos['tipo_egreso']."','".$datos['fecha']."','".$datos['factura']."','".$datos['subtotal']."','".$datos['descuento']."','".$datos['nc']."','".$datos['isv']."','".$datos['total']."','".$datos['observacion']."','".$datos['estado']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."','".$datos['categoria_gastos']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function agregar_categoria_egresos_modelo($datos){
			$insert = "INSERT INTO `categoria_gastos`(`categoria_gastos_id`, `nombre`, `estado`, `usuario`, `date_write`) VALUES ('" . $datos['categoria_gastos_id'] . "','" . $datos['nombre'] . "','" . $datos['estado'] . "','" . $datos['usuario'] . "','" . $datos['date_write'] . "')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}		
		
		protected function agregar_movimientos_contabilidad_modelo($datos){
			$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
			$insert = "INSERT INTO movimientos_cuentas VALUES('$movimientos_cuentas_id','".$datos['cuentas_id']."','".$datos['empresa_id']."','".$datos['fecha']."','".$datos['ingreso']."','".$datos['egreso']."','".$datos['saldo']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function edit_egresos_contabilidad_modelo($datos){
			$update = "UPDATE egresos
			SET
				factura = '".$datos['factura']."',
				fecha = '".$datos['fecha']."',
				observacion = '".$datos['observacion']."'				
			WHERE egresos_id = '".$datos['egresos_id']."'";
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;
		}

		protected function edit_categoria_egresos_contabilidad_modelo($datos){
			$update = "UPDATE categoria_gastos
			SET
				nombre = '".$datos['nombre']."'				
			WHERE categoria_gastos_id = '".$datos['categoria_gastos_id']."'";

			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;
		}

		protected function cancel_egresos_contabilidad_modelo($datos){
			$update = "UPDATE egresos
			SET
				estado = '".$datos['estado']."'				
			WHERE egresos_id = '".$datos['egresos_id']."'";
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
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
		
		protected function delete_egresos_contabilidad_modelo($cuentas_id){
			$delete = "DELETE FROM egresos WHERE cuentas_id = '$cuentas_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_egresos_cuentas_modelo($datos){
			$query = "SELECT egresos_id FROM egresos WHERE factura = '".$datos['factura']."' AND proveedores_id = '".$datos['proveedores_id']."'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function valid_categoria_egresos_modelo($datos){
			$query = "SELECT categoria_gastos_id FROM categoria_gastos WHERE nombre = '".$datos['nombre']."'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}		
	}
?>