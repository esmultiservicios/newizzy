<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class ingresosContabilidadModelo extends mainModel{

		protected function agregar_ingresos_contabilidad_modelo($datos){			
			$insert = "INSERT INTO ingresos (
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
                    fecha_registro
                ) VALUES (
                    '".$datos['ingresos_id']."',
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
                    '".$datos['fecha_registro']."'
                )";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function agregar_movimientos_contabilidad_modelo($datos){
			$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");

			$insert = "INSERT INTO movimientos_cuentas (
                    movimientos_cuentas_id,
                    cuentas_id,
                    empresa_id,
                    fecha,
                    ingreso,
                    egreso,
                    saldo,
                    colaboradores_id,
                    fecha_registro
                ) VALUES (
                    '$movimientos_cuentas_id',
                    '".$datos['cuentas_id']."',
                    '".$datos['empresa_id']."',
                    '".$datos['fecha']."',
                    '".$datos['ingreso']."',
                    '".$datos['egreso']."',
                    '".$datos['saldo']."',
                    '".$datos['colaboradores_id']."',
                    '".$datos['fecha_registro']."'
                )";
			
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

        protected function valid_cliente_id_cuentas_contabilidad($clientes_id){
			$query = "SELECT clientes_id
				FROM clientes
				WHERE clientes_id = '$clientes_id'
                LIMIT 1";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

        protected function obtener_ingreso_anulacion_modelo($ingresos_id){
			$query = "SELECT
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
                    fecha_registro
                FROM ingresos
                WHERE ingresos_id = '$ingresos_id'
                LIMIT 1";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

		protected function consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id, $empresa_id = 0){
			$whereEmpresa = "";

			if((int)$empresa_id > 0){
				$whereEmpresa = " AND empresa_id = '$empresa_id' ";
			}

			$query = "SELECT ingreso, egreso, saldo
				FROM movimientos_cuentas
				WHERE cuentas_id = '$cuentas_id'
				$whereEmpresa
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
			$query = "SELECT ingresos_id 
                FROM ingresos 
                WHERE factura = '".$datos['factura']."' 
                AND clientes_id = '".$datos['clientes_id']."'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);			
			
			return $sql;			
		}

		protected function getTotalIngresosRegistrados() {
			try {
				$conexion = $this->connection();
				
				$primerDiaMes = date('Y-m-01');
				$ultimoDiaMes = date('Y-m-t');

				$query = "SELECT COUNT(ingresos_id) AS total 
						  FROM ingresos 
                          WHERE estado = 1
						  AND CAST(fecha_registro AS DATE) BETWEEN '$primerDiaMes' AND '$ultimoDiaMes'";
				
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar ingresos: " . $conexion->error);
				}
				
				$fila = $resultado->fetch_assoc();

				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalIngresosRegistrados: " . $e->getMessage());
				return 0;
			}
		}

		protected function columna_existe_modelo($conexion, $tabla, $columna){
			$tabla = $conexion->real_escape_string($tabla);
			$columna = $conexion->real_escape_string($columna);

			$query = "SHOW COLUMNS FROM `$tabla` LIKE '$columna'";
			$result = $conexion->query($query);

			return ($result && $result->num_rows > 0);
		}

		protected function anular_ingreso_con_reintegro_modelo($datosCancel, $datosEgreso, $datosMov){
			$conexion = mainModel::connection();

			try {
				$columnaImpuestoEgreso = "impuesto";

				if(!$this->columna_existe_modelo($conexion, "egresos", "impuesto")){
					if($this->columna_existe_modelo($conexion, "egresos", "isv")){
						$columnaImpuestoEgreso = "isv";
					}else{
						throw new Exception("No existe columna impuesto/isv en egresos.");
					}
				}

				$columnaCategoriaEgreso = "categoria_gastos_id";

				if(!$this->columna_existe_modelo($conexion, "egresos", "categoria_gastos_id")){
					if($this->columna_existe_modelo($conexion, "egresos", "categoria_gastos")){
						$columnaCategoriaEgreso = "categoria_gastos";
					}else{
						throw new Exception("No existe columna categoria_gastos_id/categoria_gastos en egresos.");
					}
				}

				$conexion->begin_transaction();

				// 1. Actualizar ingreso a estado 0
				$queryUpdateIngreso = "UPDATE ingresos
					SET estado = ?,
						observacion = ?
					WHERE ingresos_id = ?
					AND estado = 1";

				$stmtIngreso = $conexion->prepare($queryUpdateIngreso);

				if(!$stmtIngreso){
					throw new Exception("Error prepare ingreso: " . $conexion->error);
				}

				$stmtIngreso->bind_param(
					"isi",
					$datosCancel['estado'],
					$datosCancel['observacion'],
					$datosCancel['ingresos_id']
				);

				if(!$stmtIngreso->execute()){
					throw new Exception("Error execute ingreso: " . $stmtIngreso->error);
				}

				if($stmtIngreso->affected_rows <= 0){
					throw new Exception("No se actualizó el ingreso. Puede que ya esté anulado o no exista.");
				}

				$stmtIngreso->close();

				// 2. Insertar egreso de reintegro
				$queryInsertEgreso = "INSERT INTO egresos (
						egresos_id,
						cuentas_id,
						proveedores_id,
						empresa_id,
						tipo_egreso,
						fecha,
						factura,
						factura_pdf,
						subtotal,
						descuento,
						nc,
						$columnaImpuestoEgreso,
						total,
						observacion,
						estado,
						colaboradores_id,
						fecha_registro,
						$columnaCategoriaEgreso
					) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

				$stmtEgreso = $conexion->prepare($queryInsertEgreso);

				if(!$stmtEgreso){
					throw new Exception("Error prepare egreso: " . $conexion->error);
				}

				$categoriaValor = isset($datosEgreso[$columnaCategoriaEgreso]) ? (int)$datosEgreso[$columnaCategoriaEgreso] : 0;

				$stmtEgreso->bind_param(
					"iiiiisssdddddsiisi",
					$datosEgreso['egresos_id'],
					$datosEgreso['cuentas_id'],
					$datosEgreso['proveedores_id'],
					$datosEgreso['empresa_id'],
					$datosEgreso['tipo_egreso'],
					$datosEgreso['fecha'],
					$datosEgreso['factura'],
					$datosEgreso['factura_pdf'],
					$datosEgreso['subtotal'],
					$datosEgreso['descuento'],
					$datosEgreso['nc'],
					$datosEgreso['isv'],
					$datosEgreso['total'],
					$datosEgreso['observacion'],
					$datosEgreso['estado'],
					$datosEgreso['colaboradores_id'],
					$datosEgreso['fecha_registro'],
					$categoriaValor
				);

				if(!$stmtEgreso->execute()){
					throw new Exception("Error execute egreso: " . $stmtEgreso->error);
				}

				$stmtEgreso->close();

				// 3. Insertar movimiento de cuenta
				$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");

				$queryInsertMovimiento = "INSERT INTO movimientos_cuentas (
						movimientos_cuentas_id,
						cuentas_id,
						empresa_id,
						fecha,
						ingreso,
						egreso,
						saldo,
						colaboradores_id,
						fecha_registro
					) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

				$stmtMovimiento = $conexion->prepare($queryInsertMovimiento);

				if(!$stmtMovimiento){
					throw new Exception("Error prepare movimiento: " . $conexion->error);
				}

				$stmtMovimiento->bind_param(
					"iiisdddis",
					$movimientos_cuentas_id,
					$datosMov['cuentas_id'],
					$datosMov['empresa_id'],
					$datosMov['fecha'],
					$datosMov['ingreso'],
					$datosMov['egreso'],
					$datosMov['saldo'],
					$datosMov['colaboradores_id'],
					$datosMov['fecha_registro']
				);

				if(!$stmtMovimiento->execute()){
					throw new Exception("Error execute movimiento: " . $stmtMovimiento->error);
				}

				$stmtMovimiento->close();

				$conexion->commit();

				return true;

			} catch (Exception $e) {
				try {
					$conexion->rollback();
				} catch (Exception $rollbackError) {}

				error_log("Error en anular_ingreso_con_reintegro_modelo: " . $e->getMessage());

				return false;
			}
		}

		protected function validar_reversion_ingreso_existente_modelo($ingresos_id, $empresa_id){
			$conexion = mainModel::connection();

			$ingresos_id = (int)$ingresos_id;
			$empresa_id = (int)$empresa_id;

			$facturaReversion = "REV-ING-".$ingresos_id;
			$facturaReversion = $conexion->real_escape_string($facturaReversion);

			$query = "
				SELECT egresos_id
				FROM egresos
				WHERE empresa_id = '$empresa_id'
				  AND factura = '$facturaReversion'
				  AND estado = 1
				LIMIT 1
			";

			$sql = $conexion->query($query) or die($conexion->error);

			return $sql;
		}

		protected function reversar_ingreso_con_egreso_modelo($datosEgreso, $datosMov){
			$conexion = mainModel::connection();

			$egresos_id = (int)$datosEgreso['egresos_id'];
			$cuentas_id = (int)$datosEgreso['cuentas_id'];
			$proveedores_id = (int)$datosEgreso['proveedores_id'];
			$empresa_id = (int)$datosEgreso['empresa_id'];
			$tipo_egreso = (int)$datosEgreso['tipo_egreso'];
			$fecha = $conexion->real_escape_string($datosEgreso['fecha']);
			$factura = $conexion->real_escape_string($datosEgreso['factura']);
			$factura_pdf = $datosEgreso['factura_pdf'] !== null ? "'".$conexion->real_escape_string($datosEgreso['factura_pdf'])."'" : "NULL";
			$subtotal = (float)$datosEgreso['subtotal'];
			$descuento = (float)$datosEgreso['descuento'];
			$nc = (float)$datosEgreso['nc'];
			$isv = (float)$datosEgreso['isv'];
			$total = (float)$datosEgreso['total'];
			$observacionEgreso = $conexion->real_escape_string($datosEgreso['observacion']);
			$estadoEgreso = (int)$datosEgreso['estado'];
			$colaboradores_id = (int)$datosEgreso['colaboradores_id'];
			$fecha_registro = $conexion->real_escape_string($datosEgreso['fecha_registro']);
			$categoria_gastos_id = isset($datosEgreso['categoria_gastos_id']) ? (int)$datosEgreso['categoria_gastos_id'] : 1;

			$queryInsertEgreso = "
				INSERT INTO egresos (
					egresos_id,
					cuentas_id,
					proveedores_id,
					empresa_id,
					tipo_egreso,
					fecha,
					factura,
					factura_pdf,
					subtotal,
					descuento,
					nc,
					impuesto,
					total,
					observacion,
					estado,
					colaboradores_id,
					fecha_registro,
					categoria_gastos_id
				) VALUES (
					'$egresos_id',
					'$cuentas_id',
					'$proveedores_id',
					'$empresa_id',
					'$tipo_egreso',
					'$fecha',
					'$factura',
					$factura_pdf,
					'$subtotal',
					'$descuento',
					'$nc',
					'$isv',
					'$total',
					'$observacionEgreso',
					'$estadoEgreso',
					'$colaboradores_id',
					'$fecha_registro',
					'$categoria_gastos_id'
				)
			";

			$resultInsertEgreso = $conexion->query($queryInsertEgreso);

			if(!$resultInsertEgreso){
				return [
					"success" => false,
					"message" => "No se pudo registrar el egreso de reversión: ".$conexion->error
				];
			}

			$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");

			$mov_cuentas_id = (int)$datosMov['cuentas_id'];
			$mov_empresa_id = (int)$datosMov['empresa_id'];
			$mov_fecha = $conexion->real_escape_string($datosMov['fecha']);
			$mov_ingreso = (float)$datosMov['ingreso'];
			$mov_egreso = (float)$datosMov['egreso'];
			$mov_saldo = (float)$datosMov['saldo'];
			$mov_colaboradores_id = (int)$datosMov['colaboradores_id'];
			$mov_fecha_registro = $conexion->real_escape_string($datosMov['fecha_registro']);

			$queryInsertMovimiento = "
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
				) VALUES (
					'$movimientos_cuentas_id',
					'$mov_cuentas_id',
					'$mov_empresa_id',
					'$mov_fecha',
					'$mov_ingreso',
					'$mov_egreso',
					'$mov_saldo',
					'$mov_colaboradores_id',
					'$mov_fecha_registro'
				)
			";

			$resultInsertMovimiento = $conexion->query($queryInsertMovimiento);

			if(!$resultInsertMovimiento){
				return [
					"success" => false,
					"message" => "El egreso fue registrado, pero no se pudo registrar el movimiento de cuenta: ".$conexion->error
				];
			}

			return [
				"success" => true,
				"egresos_id" => $egresos_id,
				"movimientos_cuentas_id" => $movimientos_cuentas_id
			];
		}

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