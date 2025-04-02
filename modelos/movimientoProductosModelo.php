<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class movimientoProductosModelo extends mainModel{			
		protected function agregar_movimiento_productos_modelo($datos){
			$movimientos_id = mainModel::correlativo("movimientos_id", "movimientos");
			
			$insert = "INSERT INTO `movimientos`(`movimientos_id`, `productos_id`, `documento`, `cantidad_entrada`, `cantidad_salida`, `saldo`, `empresa_id`, `fecha_registro`, `clientes_id`, `comentario`, `almacen_id`, `lote_id`) 
			VALUES('$movimientos_id','".$datos['productos_id']."','Movimiento de Productos $movimientos_id','".$datos['cantidad_entrada']."','".$datos['cantidad_salida']."','".$datos['saldo']."','".$datos['empresa']."',NOW(),'".$datos['clientes_id']."','".$datos['comentario']."','".$datos['almacen_id']."','".$datos['lote_id']."')";			
			
			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $result;			
		}		

		protected function cantidad_producto_modelo($productos_id){
			$result = mainModel::getCantidadProductos($productos_id);
			
			return $result;			
		}
				

		public function verificar_empresa($empresa_id) {
			$query = mainModel::connection()->query("SELECT empresa_id FROM empresa WHERE empresa_id = '$empresa_id'");
			return $query->num_rows > 0; // Retorna true si existe, false si no
		}
		
		public function getSaldoPorLote($productos_id, $lote_id)
		{
			// Obtenemos la conexión a la base de datos
			$conexion = mainModel::connection();

			// Consulta SQL para obtener el saldo del producto en el lote específico
			$query = "SELECT saldo 
					FROM movimientos 
					WHERE productos_id = ? AND lote_id = ? 
					ORDER BY fecha_registro DESC LIMIT 1"; // FIFO (First In, First Out)

			// Preparamos la consulta
			$stmt = $conexion->prepare($query);
			
			// Vinculamos los parámetros (producto_id y lote_id)
			$stmt->bind_param("ii", $productos_id, $lote_id);
			
			// Ejecutamos la consulta
			$stmt->execute();
			
			// Obtenemos el resultado
			$result = $stmt->get_result();
			
			// Verificamos si existe un saldo para este producto en el lote especificado
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				return $row['saldo']; // Devolvemos el saldo
			} else {
				return 0; // Si no se encuentra el saldo, devolvemos 0 en lugar de null
			}
		}

		public function getSaldoProductosMovimientos($productos_id)
		{
			$mysqli = self::connection();
		
			// Consulta preparada para evitar inyecciones SQL
			$query = "SELECT COALESCE(SUM(m.cantidad_entrada), 0) - COALESCE(SUM(m.cantidad_salida), 0) AS saldo 
					  FROM movimientos AS m
					  INNER JOIN productos AS p ON m.productos_id = p.productos_id 
					  WHERE p.estado = 1 AND p.productos_id = ?";
		
			// Preparar y ejecutar la consulta
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("i", $productos_id);  // Bind para el parámetro del producto
			$stmt->execute();
		
			// Obtener el resultado y devolver el saldo
			$result = $stmt->get_result();
			return ($result && $row = $result->fetch_assoc()) ? $row['saldo'] : 0;
		}	

		protected function registrar_entrada_lote_modelo($datos) {
			$mysqli = mainModel::connection();

			// Verificar si la fecha de vencimiento está presente
			if (isset($datos['fecha_vencimiento']) && $datos['fecha_vencimiento'] !== null) {
				// Verificar si existe un lote con la fecha de vencimiento para el producto
				$checkLoteQuery = $mysqli->prepare("SELECT lote_id, cantidad FROM lotes 
													WHERE productos_id = ? AND fecha_vencimiento = ? AND estado = 'Activo'");
				$checkLoteQuery->bind_param("is", $datos['productos_id'], $datos['fecha_vencimiento']);
				$checkLoteQuery->execute();
				$resultLote = $checkLoteQuery->get_result();

				$nuevoSaldo = 0;				
		
				if ($resultLote->num_rows > 0) {
					$lote = $resultLote->fetch_assoc();
					$lote_id = $lote['lote_id'];
					$saldo = $lote['cantidad'];  // Saldo actual del lote
		
					// Actualizar el saldo sumando la cantidad de entrada
					$nuevoSaldo = $saldo + $datos['cantidad'];
		
					// Actualizar el lote con el nuevo saldo
					$updateLoteQuery = $mysqli->prepare("UPDATE lotes SET cantidad = ? WHERE lote_id = ?");
					$updateLoteQuery->bind_param("ii", $nuevoSaldo, $lote_id);
					$updateLoteQuery->execute();
				} else {
					// VERIFICAMOS SI EL NUMERO DE LOTE NO ESTÁ PRESENTE
					if (!isset($datos['movimiento_lote']) || empty(trim($datos['movimiento_lote']) || $datos['movimiento_lote'] == 0)) {
						// Generar número de lote único
						do {
							$fechaHora = date("YmdHis");
							$contador = rand(100, 999);
							$numero_lote = "LOT{$datos['productos_id']}{$fechaHora}{$contador}";
			
							$checkQuery = $mysqli->prepare("SELECT numero_lote FROM lotes WHERE numero_lote = ?");
							$checkQuery->bind_param("s", $numero_lote);
							$checkQuery->execute();
							$result = $checkQuery->get_result();
						} while ($result->num_rows > 0);
			
						// Insertar el nuevo lote
						$insertQuery = "INSERT INTO lotes (numero_lote, productos_id, cantidad, fecha_vencimiento, fecha_ingreso, almacen_id, empresa_id, estado) 
										VALUES (?, ?, ?, ?, NOW(), ?, ?, 'Activo')";
			
						$stmt = $mysqli->prepare($insertQuery);
						$stmt->bind_param("siisii", 
							$numero_lote, 
							$datos['productos_id'], 
							$datos['cantidad'], 
							$datos['fecha_vencimiento'], 
							$datos['almacen_id'], 
							$datos['empresa_id']
						);
			
						if ($stmt->execute()) {
							$lote_id = $mysqli->insert_id;
							$nuevoSaldo = $datos['cantidad'];  // El saldo inicial es igual a la cantidad
						} else {
							return false;
						}
					}
				}
			} else {
				$lote_id = $datos['movimiento_lote'] ?? 0;

				// Si no hay fecha de vencimiento, el lote no se maneja, obtener saldo desde movimientos
				$saldo = $this->getSaldoProductosMovimientos($datos['productos_id']);  // Ahora devuelve directamente el saldo
				$nuevoSaldo = $saldo + $datos['cantidad'];		
				
				if($lote_id != 0 ){							
					// Obtenemos el saldo actual del lote y producto
					$saldo = $this->getSaldoPorLote($datos['productos_id'], $lote_id);

					// Calculamos el nuevo saldo
					$nuevoSaldo = $saldo + $datos['cantidad'];

					// Realizamos el UPDATE para actualizar el saldo en la tabla 'lotes'
					$updateQuery = "UPDATE lotes 
									SET cantidad = ? 
									WHERE productos_id = ? AND lote_id = ?";
					// Preparamos la consulta
					$stmt = $mysqli->prepare($updateQuery);

					// Vinculamos los parámetros: nuevo saldo, productos_id y numero_lote
					$stmt->bind_param("iii", $nuevoSaldo, $datos['productos_id'], $lote_id);
					$stmt->execute();
				}
			}
		
			// Asegúrate de que siempre haya un saldo válido
			$cantidadSalida = 0;
			$documento = "";
		
			// Insertar movimiento
			$insertMovimiento = "INSERT INTO movimientos (productos_id, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro, almacen_id, lote_id, clientes_id, documento, comentario) 
								VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
		
			$stmtMovimiento = $mysqli->prepare($insertMovimiento);
			$stmtMovimiento->bind_param("iiiiiiiiss", 
				$datos['productos_id'],  // productos_id
				$datos['cantidad'],
				$cantidadSalida,
				$nuevoSaldo,
				$datos['empresa_id'],
				$datos['almacen_id'],
				$lote_id,
				$datos['clientes_id'],
				$documento,
				$datos['comentario']
			);
		
			if ($stmtMovimiento->execute()) {
				$movimientos_id = $mysqli->insert_id; // Obtener ID del movimiento insertado
		
				// Actualizar el campo documento con "Movimiento" + id del movimiento
				$nuevo_documento = "Movimiento " . $movimientos_id;
				$updateDocumento = $mysqli->prepare("UPDATE movimientos SET documento = ? WHERE movimientos_id = ?");
				$updateDocumento->bind_param("si", $nuevo_documento, $movimientos_id);
				$updateDocumento->execute();

				return ["success" => true, "message" => "Entrada registrada correctamente.", "movimientos_id" => $movimientos_id];
			} else {
				return ["success" => false, "message" => "Error al registrar el movimiento de entrada."];
			}
		}		
		
		protected function registrar_salida_lote_modelo($datos) {
			$mysqli = mainModel::connection();
		
			if (!empty($datos['movimiento_lote']) && $datos['movimiento_lote'] != 0) {
				// Si se envía un lote específico, usarlo
				$lote_id = $datos['movimiento_lote'];
				$checkLoteQuery = $mysqli->prepare("SELECT cantidad FROM lotes WHERE lote_id = ? AND estado = 'Activo'");
				$checkLoteQuery->bind_param("i", $lote_id);
				$checkLoteQuery->execute();
				$resultLote = $checkLoteQuery->get_result();
		
				if ($resultLote->num_rows > 0) {
					$lote = $resultLote->fetch_assoc();
					$saldo = $lote['cantidad'];
				} else {
					return ["status" => "error", "message" => "El lote especificado no es válido o está inactivo"];
				}
			} else {
				// Si no se envía lote, usar el más antiguo
				$checkLoteQuery = $mysqli->prepare("SELECT lote_id, cantidad FROM lotes 
													WHERE productos_id = ? AND estado = 'Activo' 
													ORDER BY fecha_vencimiento ASC LIMIT 1");
				$checkLoteQuery->bind_param("i", $datos['productos_id']);
				$checkLoteQuery->execute();
				$resultLote = $checkLoteQuery->get_result();
		
				if ($resultLote->num_rows > 0) {
					$lote = $resultLote->fetch_assoc();
					$lote_id = $lote['lote_id'];
					$saldo = $lote['cantidad'];
				} else {
					// No hay lote, obtener saldo de movimientos
					$saldo = $this->getSaldoProductosMovimientos($datos['productos_id']);
					$lote_id = 0;
				}
			}
		
			// Verificar si hay saldo suficiente para la salida
			if ($saldo >= $datos['cantidad']) {
				$cantidad_salida = $datos['cantidad'];
				$nuevo_saldo = $saldo - $datos['cantidad'];
		
				// Insertar movimiento de salida
				$insertMovimiento = "INSERT INTO movimientos (productos_id, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro, almacen_id, lote_id, clientes_id, documento, comentario) 
									 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
				$cantidadEntrada = 0;
				$documento = "";
		
				$stmtMovimiento = $mysqli->prepare($insertMovimiento);
				$stmtMovimiento->bind_param("iiiiiiiiss", 
					$datos['productos_id'], 
					$cantidadEntrada,
					$cantidad_salida, 
					$nuevo_saldo, 
					$datos['empresa_id'], 
					$datos['almacen_id'], 
					$lote_id,
					$datos['clientes_id'],
					$documento,
					$datos['comentario']
				);
		
				if ($stmtMovimiento->execute()) {
					$movimientos_id = $mysqli->insert_id;
					$nuevo_documento = "Movimiento " . $movimientos_id;
					$updateDocumento = $mysqli->prepare("UPDATE movimientos SET documento = ? WHERE movimientos_id = ?");
					$updateDocumento->bind_param("si", $nuevo_documento, $movimientos_id);
					$updateDocumento->execute();
		
					// Si se usó un lote, actualizar su saldo
					if ($lote_id > 0) {
						$updateLote = $mysqli->prepare("UPDATE lotes SET cantidad = ? WHERE lote_id = ?");
						$updateLote->bind_param("ii", $nuevo_saldo, $lote_id);
						$updateLote->execute();
		
						// Si el saldo del lote es 0, marcarlo como inactivo
						if ($nuevo_saldo == 0) {
							$updateEstadoLote = $mysqli->prepare("UPDATE lotes SET estado = 'Inactivo' WHERE lote_id = ?");
							$updateEstadoLote->bind_param("i", $lote_id);
							$updateEstadoLote->execute();
						}
					}	

					return ["status" => "success", "message" => "Movimiento registrado con éxito", "movimientos_id" => $movimientos_id];
				} else {
					return ["status" => "error", "message" => "Error al registrar el movimiento: " . $stmtMovimiento->error];
				}
			} else {
				return ["status" => "error", "message" => "Saldo insuficiente para la salida"];
			}
		}			
	}