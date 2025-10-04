<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class ingresosContabilidadModelo extends mainModel{
		protected function agregar_ingresos_contabilidad_modelo($datos){			
			$insert = "INSERT INTO ingresos VALUES('".$datos['ingresos_id']."','".$datos['cuentas_id']."','".$datos['clientes_id']."','".$datos['empresa_id']."','".$datos['tipo_ingreso']."','".$datos['fecha']."','".$datos['factura']."','".$datos['subtotal']."','".$datos['descuento']."','".$datos['nc']."','".$datos['isv']."','".$datos['total']."','".$datos['observacion']."','".$datos['estado']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."','".$datos['recibide']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function agregar_movimientos_contabilidad_modelo($datos){
			$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
			$insert = "INSERT INTO movimientos_cuentas VALUES('$movimientos_cuentas_id','".$datos['cuentas_id']."','".$datos['empresa_id']."','".$datos['fecha']."','".$datos['ingreso']."','".$datos['egreso']."','".$datos['saldo']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
		
		protected function edit_ingresos_contabilidad_modelo($datos){
			$update = "UPDATE ingresos
			SET
				factura = '".$datos['factura']."',
				fecha = '".$datos['fecha']."',
				observacion = '".$datos['observacion']."'				
			WHERE ingresos_id = '".$datos['ingresos_id']."'";

			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;
		}

		// 1) Anular ingreso: estado y observación (igual que egresos)
		protected function cancel_ingresos_contabilidad_modelo($datos){
			$update = "
				UPDATE ingresos
				SET estado = '".$datos['estado']."',
					observacion = '".$datos['observacion']."'
				WHERE ingresos_id = '".$datos['ingresos_id']."'
			";
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			return $sql;			
		}		
		
		protected function valid_clientes_cuentas_contabilidad($nombre){
			$query = "SELECT clientes_id
				FROM clientes
				WHERE nombre = '$nombre'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
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
		
		protected function delete_ingresos_contabilidad_modelo($cuentas_id){
			$delete = "DELETE FROM ingresos WHERE cuentas_id = '$cuentas_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_ingreso_cuentas_modelo($datos){
			$query = "SELECT ingresos_id FROM ingresos WHERE factura = '".$datos['factura']."' AND clientes_id = '".$datos['clientes_id']."'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);			
			
			return $sql;			
		}

		protected function getTotalIngresosRegistrados() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Obtener el primer y último día del mes actual
				$primerDiaMes = date('Y-m-01');  // Ej: 2024-06-01
				$ultimoDiaMes = date('Y-m-t');   // Ej: 2024-06-30

				// Consulta SQL para contar clientes activos (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(ingresos_id) AS total 
						  FROM ingresos WHERE estado = 1
						  AND CAST(fecha_registro AS DATE) BETWEEN '$primerDiaMes' AND '$ultimoDiaMes'";
				
				// Ejecutar consulta
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar ingresos: " . $conexion->error);
				}
				
				// Obtener el total
				$fila = $resultado->fetch_assoc();
				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalIngresosRegistrados: " . $e->getMessage());
				return 0; // Retorna 0 si hay error para no bloquear el sistema
			}
		}

		// Inserta un egreso espejo (idéntico a agregar_egresos_contabilidad_modelo del modelo de egresos)
		protected function agregar_egreso_por_anulacion_modelo($datos){
			$insert = "INSERT INTO egresos VALUES(
				'".$datos['egresos_id']."',
				'".$datos['cuentas_id']."',
				'".$datos['proveedores_id']."',
				'".$datos['empresa_id']."',
				'".$datos['tipo_egreso']."',
				'".$datos['fecha']."',
				'".$datos['factura']."',
				'".$datos['factura_pdf']."',
				'".$datos['subtotal']."',
				'".$datos['descuento']."',
				'".$datos['nc']."',
				'".$datos['isv']."',
				'".$datos['total']."',
				'".$datos['observacion']."',
				'".$datos['estado']."',
				'".$datos['colaboradores_id']."',
				'".$datos['fecha_registro']."',
				'".$datos['categoria_gastos']."'
			)";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			return $sql;			
		}

	}