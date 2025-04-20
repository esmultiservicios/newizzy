<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }

	class facturasModelo extends mainModel{		
		protected function guardar_facturas_modelo($datos) {
			// Verificar si ya existe un registro con el mismo facturas_id
			$check = "SELECT COUNT(*) as count FROM facturas 
					  WHERE facturas_id = '".$datos['facturas_id']."'";
			$result_check = mainModel::connection()->query($check) or die(mainModel::connection()->error);
			$row = $result_check->fetch_assoc();
		
			if ($row['count'] > 0) {
				// Si existe, realizar un UPDATE
				$query = "UPDATE facturas SET
							`clientes_id` = '".$datos['clientes_id']."',
							`secuencia_facturacion_id` = '".$datos['secuencia_facturacion_id']."',
							`apertura_id` = '".$datos['apertura_id']."',
							`number` = '".$datos['numero']."',
							`tipo_factura` = '".$datos['tipo_factura']."',
							`colaboradores_id` = '".$datos['colaboradores_id']."',
							`importe` = '".$datos['importe']."',
							`notas` = '".$datos['notas']."',
							`fecha` = '".$datos['fecha']."',
							`estado` = '".$datos['estado']."',
							`usuario` = '".$datos['usuario']."',
							`empresa_id` = '".$datos['empresa']."',
							`fecha_registro` = '".$datos['fecha_registro']."',
							`fecha_dolar` = '".$datos['fecha_dolar']."'
						WHERE `facturas_id` = '".$datos['facturas_id']."'";
			} else {
				// Si no existe, realizar un INSERT
				$query = "INSERT INTO facturas (
							`facturas_id`, 
							`clientes_id`, 
							`secuencia_facturacion_id`, 
							`apertura_id`, 
							`number`, 
							`tipo_factura`, 
							`colaboradores_id`, 
							`importe`, 
							`notas`, 
							`fecha`, 
							`estado`, 
							`usuario`, 
							`empresa_id`, 
							`fecha_registro`, 
							`fecha_dolar`
						)
						VALUES (
							'".$datos['facturas_id']."',
							'".$datos['clientes_id']."',
							'".$datos['secuencia_facturacion_id']."',
							'".$datos['apertura_id']."',
							'".$datos['numero']."',
							'".$datos['tipo_factura']."',
							'".$datos['colaboradores_id']."',
							'".$datos['importe']."',
							'".$datos['notas']."',
							'".$datos['fecha']."',
							'".$datos['estado']."',
							'".$datos['usuario']."',
							'".$datos['empresa']."',
							'".$datos['fecha_registro']."',
							'".$datos['fecha_dolar']."'
						)";
			}
		
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
		
			// Devolver true si la consulta fue exitosa, false en caso contrario
			return $result ? true : false;
		}
		
		protected function agregar_detalle_facturas_modelo($datos) {
			// Verificar si ya existe un registro con el mismo facturas_id y productos_id
			$check = "SELECT COUNT(*) as count FROM facturas_detalles 
					  WHERE facturas_id = '".$datos['facturas_id']."' 
					  AND productos_id = '".$datos['productos_id']."'";
			$result_check = mainModel::connection()->query($check) or die(mainModel::connection()->error);
			$row = $result_check->fetch_assoc();
		
			if ($row['count'] > 0) {
				// Si existe, realizar un UPDATE
				$update = "UPDATE facturas_detalles SET
							`cantidad` = '".$datos['cantidad']."',
							`precio` = '".$datos['precio']."',
							`isv_valor` = '".$datos['isv_valor']."',
							`descuento` = '".$datos['descuento']."',
							`medida` = '".$datos['medida']."'
						WHERE `facturas_id` = '".$datos['facturas_id']."' 
						AND `productos_id` = '".$datos['productos_id']."'";
				$result = mainModel::connection()->query($update);
			} else {
				// Si no existe, realizar un INSERT
				$facturas_detalle_id = mainModel::correlativo("facturas_detalle_id", "facturas_detalles");
				$insert = "INSERT INTO facturas_detalles (
								`facturas_detalle_id`, 
								`facturas_id`, 
								`productos_id`, 
								`cantidad`, 
								`precio`, 
								`isv_valor`, 
								`descuento`, 
								`medida`
							)
							VALUES (
								'$facturas_detalle_id',
								'".$datos['facturas_id']."',
								'".$datos['productos_id']."',
								'".$datos['cantidad']."',
								'".$datos['precio']."',
								'".$datos['isv_valor']."',
								'".$datos['descuento']."',
								'".$datos['medida']."'
							)";
				$result = mainModel::connection()->query($insert);
			}
		
			// Devolver true si la consulta fue exitosa, false en caso contrario
			return $result ? true : false;
		}	
		
		protected function agregar_cambio_dolar_modelo($datos){
			$insert = "INSERT INTO cambio_dolar 
				VALUES('".$datos['cambio_dolar_id']."','".$datos['compra']."','".$datos['venta']."','".$datos['tipo']."','".$datos['fecha_registro']."')";

			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);		

			return $result;			
		}

		protected function agregar_movimientos_productos_modelo($datos){
			$movimientos_id = mainModel::correlativo("movimientos_id", "movimientos");
			$insert = "INSERT INTO movimientos (
							`movimientos_id`, 
							`productos_id`, 
							`documento`, 
							`cantidad_entrada`, 
							`cantidad_salida`, 
							`saldo`, 
							`empresa_id`, 
							`fecha_registro`, 
							`clientes_id`, 
							`comentario`, 
							`almacen_id`,
							`lote_id`
						)
						VALUES (
							'$movimientos_id',
							'".$datos['productos_id']."',
							'".$datos['documento']."',
							'".$datos['cantidad_entrada']."',
							'".$datos['cantidad_salida']."',
							'".$datos['saldo']."',
							'".$datos['empresa']."',
							'".$datos['fecha_registro']."',
							'".$datos['clientes_id']."',
							'',
							'".$datos['almacen_id']."',
							'".$datos['lote_id']."'  
						)";
		
			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);    
			return $result;                
		}		
		
		protected function agregar_cuenta_por_cobrar_clientes($datos){
			$cobrar_clientes_id = mainModel::correlativo("cobrar_clientes_id", "cobrar_clientes");
			$insert = "INSERT INTO cobrar_clientes (
							`cobrar_clientes_id`, 
							`clientes_id`, 
							`facturas_id`, 
							`fecha`, 
							`saldo`, 
							`estado`, 
							`usuario`, 
							`empresa_id`, 
							`fecha_registro`
						)
						VALUES (
							'$cobrar_clientes_id',
							'".$datos['clientes_id']."',
							'".$datos['facturas_id']."',
							'".$datos['fecha']."',
							'".$datos['saldo']."',
							'".$datos['estado']."',
							'".$datos['usuario']."',
							'".$datos['empresa']."',
							'".$datos['fecha_registro']."'
						)";
		
			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			return $result;                
		}		

		protected function agregar_precio_factura_clientes($datos){
			$precio_factura_id = mainModel::correlativo("precio_factura_id", "precio_factura");
			$insert = "INSERT INTO precio_factura (
							`precio_factura_id`, 
							`facturas_id`, 
							`productos_id`, 
							`clientes_id`, 
							`fecha`, 
							`referencia`, 
							`precio_anterior`, 
							`precio_nuevo`, 
							`fecha_registro`
						)
						VALUES (
							'$precio_factura_id',
							'".$datos['facturas_id']."',
							'".$datos['productos_id']."',
							'".$datos['clientes_id']."',
							'".$datos['fecha']."',
							'".$datos['referencia']."',
							'".$datos['precio_anterior']."',
							'".$datos['precio_nuevo']."',
							'".$datos['fecha_registro']."'
						)";
			
			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			return $result;                
		} 
		
		protected function agregar_facturas_proforma_modelo($datos){
			$facturas_proforma_id = mainModel::correlativo("facturas_proforma_id", "facturas_proforma");

			$conexion = mainModel::connection();
		
			// Preparar la consulta
			$insert = "INSERT INTO facturas_proforma (
							facturas_proforma_id,
							facturas_id,
							clientes_id,
							secuencia_facturacion_id,
							numero,
							importe,
							usuario,
							empresa_id,
							estado,
							fecha_creacion
						) VALUES (
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?
						)";
			
			$stmt = $conexion->prepare($insert);
		
			if (!$stmt) {
				die("Error al preparar la consulta: " . $conexion->error);
			}
		
			// Enlazar parámetros
			$stmt->bind_param("iiisisisss", 
				$facturas_proforma_id,
				$datos['facturas_id'],
				$datos['clientes_id'],
				$datos['secuencia_facturacion_id'],
				$datos['numero'],
				$datos['importe'],
				$datos['usuario'],
				$datos['empresa_id'],
				$datos['estado'],
				$datos['fecha_creacion']
			);
		
			// Ejecutar la consulta
			$result = $stmt->execute();
		
			if (!$result) {
				die("Error al ejecutar la consulta: " . $stmt->error);
			}
		
			$stmt->close();
		
			return $result;            
		}

		protected function actualizar_detalle_facturas($datos){
			$update = "UPDATE facturas_detalles
						SET 
							cantidad = '".$datos['cantidad']."',
							precio = '".$datos['precio']."',
							isv_valor = '".$datos['isv_valor']."',
							descuento = '".$datos['descuento']."'
						WHERE facturas_id = '".$datos['facturas_id']."' AND productos_id = '".$datos['productos_id']."'";        
		
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);        
			return $result;                    
		}
		
		protected function actualizar_factura_importe($datos){
			$update = "UPDATE facturas
						SET
							importe = '".$datos['importe']."'
						WHERE facturas_id = '".$datos['facturas_id']."'";
		
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			return $result;                
		}

		protected function actualizar_estado_factura_modelo($facturas_id){
			$update = "UPDATE facturas
				SET
					estado = '2'
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;				
		}			
					
		public static function bloquear_y_obtener_secuencia_modelo($empresa_id, $documento_id) {
			// Asegurarse que $empresa_id tenga valor
			if(empty($empresa_id)) {
				error_log("Error: empresa_id no definido");
				return false;
			}

			$conexion = mainModel::staticConnection();
			
			// Iniciar transacción
			$conexion->begin_transaction();
			
			try {
				// Bloquear la fila para lectura (FOR UPDATE)
				$sql = "SELECT * FROM secuencia_facturacion 
						WHERE empresa_id = ? 
						AND documento_id = ? 
						AND activo = 1
						LIMIT 1
						FOR UPDATE";
				
				$stmt = $conexion->prepare($sql);
				$stmt->bind_param("ii", $empresa_id, $documento_id);
				$stmt->execute();
				$result = $stmt->get_result();
				
				if($result->num_rows == 0) {
					$conexion->rollback();
					$stmt->close();
					return false;
				}
				
				$secuencia = $result->fetch_assoc();
				$stmt->close();
				
				// Confirmar transacción solo al final cuando todo esté listo
				// La transacción se cierra en el método que llama a este
				
				return $secuencia;
			} catch (Exception $e) {
				$conexion->rollback();
				error_log("Error en secuencia facturación: " . $e->getMessage());
				return false;
			}
		}
		
		public static function actualizar_secuencia_modelo($secuencia_id, $nuevo_numero) {
			$conexion = mainModel::staticConnection();
			
			try {
				// Verificar que el nuevo número no exceda el rango final
				$check_sql = "SELECT rango_final FROM secuencia_facturacion 
							  WHERE secuencia_facturacion_id = ? FOR UPDATE";
				$check_stmt = $conexion->prepare($check_sql);
				$check_stmt->bind_param("i", $secuencia_id);
				$check_stmt->execute();
				$check_result = $check_stmt->get_result();
				
				if($check_result->num_rows == 0) {
					$check_stmt->close();
					return false;
				}
				
				$row = $check_result->fetch_assoc();
				$rango_final = (int)$row['rango_final'];
				$check_stmt->close();
				
				if($nuevo_numero > $rango_final) {
					return false;
				}
				
				$sql = "UPDATE secuencia_facturacion 
						SET siguiente = ? 
						WHERE secuencia_facturacion_id = ?";
				
				$stmt = $conexion->prepare($sql);
				$stmt->bind_param("ii", $nuevo_numero, $secuencia_id);
				$result = $stmt->execute();
				$stmt->close();
				
				return $result;
			} catch (Exception $e) {
				error_log("Error al actualizar secuencia: " . $e->getMessage());
				return false;
			}
		}
		
		protected function cancelar_facturas_modelo($facturas_id){
			$estado = 4; //FACTURA CANCELADA
			$update = "UPDATE facturas
						SET
							estado = '$estado'
						WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
		
			return $result;            
		}
	
		protected function secuencia_facturacion_modelo($empresa_id, $documento_id) {
			// Consulta SQL para obtener la secuencia de facturación
			$query = "
				SELECT 
					secuencia_facturacion_id, 
					prefijo, 
					siguiente AS 'numero', 
					rango_final, 
					fecha_limite, 
					incremento, 
					relleno
				FROM 
					secuencia_facturacion
				WHERE 
					activo = '1' 
					AND empresa_id = '$empresa_id' 
					AND documento_id = '$documento_id'
			";

			// Ejecuta la consulta y maneja errores
			$result = mainModel::connection()->query($query) 
				or die(mainModel::connection()->error);

			return $result;
		}
		
		protected function validDetalleFactura($facturas_id, $productos_id){
			$query = "SELECT facturas_id
					FROM facturas_detalles
					WHERE facturas_id = '$facturas_id' AND productos_id  = '$productos_id'";
			
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;            
		}

		protected function validar_cobrarClientes_modelo($facturas_id){
			$query = "SELECT cobrar_clientes_id
					FROM cobrar_clientes
					WHERE facturas_id = '$facturas_id'";
			
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;            
		}		
	
		protected function valid_cambio_dolar_modelo($fecha){
			$query = "SELECT cambio_dolar_id
					FROM cambio_dolar
					WHERE CAST(fecha_registro AS DATE) = '$fecha'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;                
		}  

		protected function valid_cambio_dolar_tipo2_modelo($fecha){
			$query = "SELECT cambio_dolar_id
						FROM cambio_dolar
						WHERE CAST(fecha_registro AS DATE) = '$fecha' AND tipo = 2";
			
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);        
			
			return $result;                
		}  				

		protected function valid_precio_factura_modelo($datos){
			$query = "SELECT precio_factura_id
						FROM precio_factura
						WHERE facturas_id = '".$datos['facturas_id']."'";
			
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;                
		}	

		protected function saldo_productos_movimientos_modelo($productos_id){
			$result = mainModel::getSaldoProductosMovimientos($productos_id);
			
			return $result;            
		}
		
		protected function getISV_modelo(){
			$result = mainModel::getISV('Facturas');
			
			return $result;
		}
		
		protected function getISVEstadoProducto_modelo($productos_id){
			$result = mainModel::getISVEstadoProducto($productos_id);        
		
			return $result;            
		}
		
		protected function tipo_producto_modelo($productos_id){
			$result = mainModel::getTipoProducto($productos_id);        
		
			return $result;            
		}  	

		protected function getMedidaProducto($productos_id){
			$query = "SELECT
						productos.productos_id,
						medida.nombre AS medida,
						medida.medida_id,
						medida.estado
					FROM
						medida
						INNER JOIN productos ON medida.medida_id = productos.medida_id    
					WHERE productos.productos_id = '".$productos_id."'
					AND medida.estado = 1";
			
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;                
		}

		protected function cantidad_producto_modelo($productos_id){
			$result = mainModel::getCantidadProductos($productos_id);
			
			return $result;			
		}	

		protected function getAperturaIDModelo($datos){
			$query = "SELECT apertura_id
						FROM apertura
						WHERE colaboradores_id = '".$datos['colaboradores_id']."' AND fecha = '".$datos['fecha']."' AND estado = '".$datos['estado']."'";            
					
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;            
		}

		protected function total_hijos_segun_padre_modelo($productos_id){
			$result = mainModel::getTotalHijosporPadre($productos_id);
			
			return $result;			
		}
		
		protected function obtener_lote_para_salida($producto_id, $cantidad_salida) {
			// Seleccionar los lotes disponibles para el producto (por ejemplo, con estado 'Activo')
			$query = mainModel::connection()->query("SELECT lote_id, cantidad, fecha_vencimiento 
													 FROM lotes 
													 WHERE productos_id = '$producto_id' AND cantidad > 0 AND estado = 'Activo' 
													 ORDER BY fecha_vencimiento ASC"); // FIFO
		
			$lote_id = 0;
			$cantidad_restante = $cantidad_salida;
			
			while ($row = $query->fetch_assoc()) {
				if ($row['cantidad'] >= $cantidad_restante) {
					// Si el lote tiene suficiente cantidad, asignamos el lote
					$lote_id = $row['lote_id'];
					// Actualizamos la cantidad del lote
					$nueva_cantidad = $row['cantidad'] - $cantidad_restante;
					mainModel::connection()->query("UPDATE lotes SET cantidad = $nueva_cantidad WHERE lote_id = '$lote_id'");
					break;
				} else {
					// Si el lote no tiene suficiente cantidad, reducimos la cantidad restante
					$cantidad_restante -= $row['cantidad'];
					$lote_id = $row['lote_id'];
					// Ponemos la cantidad del lote a 0 ya que se consumió todo
					mainModel::connection()->query("UPDATE lotes SET cantidad = 0 WHERE lote_id = '$lote_id'");
				}
			}
			
			return $lote_id; // Retornamos el ID del lote seleccionado
		}	
		
		public function saldo_productos_por_lote_modelo($producto_id, $lote_id) {
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
			$stmt->bind_param("ii", $producto_id, $lote_id);
			
			// Ejecutamos la consulta
			$stmt->execute();
			
			// Obtenemos el resultado
			$result = $stmt->get_result();
			
			// Verificamos si existe un saldo para este producto en el lote especificado
			if ($result->num_rows > 0) {
				return $result->fetch_assoc(); // Devolvemos el saldo si existe
			} else {
				return null; // Si no se encuentra el saldo, devolvemos null
			}
		}

		protected function registrar_salida_lote_modelo($datos) {
			$mysqli = mainModel::connection();
			
			// Verificar si existe un lote activo para el producto
			$checkLoteQuery = $mysqli->prepare("SELECT lote_id, cantidad FROM lotes 
												WHERE productos_id = ? AND estado = 'Activo' 
												ORDER BY fecha_vencimiento ASC LIMIT 1");
			$checkLoteQuery->bind_param("i", $datos['productos_id']);
			$checkLoteQuery->execute();
			$resultLote = $checkLoteQuery->get_result();
		
			if ($resultLote->num_rows > 0) {
				// Si hay lote, tomamos su saldo
				$lote = $resultLote->fetch_assoc();
				$lote_id = $lote['lote_id'];
				$saldo = $lote['cantidad'];
			} else {
				// Si no hay fecha de vencimiento, el lote no se maneja, obtener saldo desde movimientos
				$resultSaldo = $this->getSaldoProductosMovimientos($datos['productos_id']);

				if ($resultSaldo->num_rows > 0) {
					$consulta = $resultSaldo->fetch_assoc();  // Accede a los resultados correctamente
					$saldo = $consulta['saldo'];  // Obtén el saldo desde la consulta
				} else {
					$saldo = 0;  // Si no hay resultados, asigna 0 al saldo
				}

				$nuevoSaldo = $saldo + $datos['cantidad'];
				$lote_id = 0;  // No hay lote asociado
			}
		
			// Verificamos si hay saldo suficiente para la salida
			if ($saldo >= $datos['cantidad']) {
				$cantidad_salida = $datos['cantidad'];
				$nuevo_saldo = $saldo - $datos['cantidad'];
		
				// Insertar el movimiento de salida
				$insertMovimiento = "INSERT INTO movimientos (productos_id, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro, almacen_id, lote_id, clientes_id, documento, comentario) 
									 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
		
				$cantidadEntrada = 0;

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
					$datos['documento'],
					$datos['comentario']
				);
		
				if ($stmtMovimiento->execute()) {
					$movimientos_id = $mysqli->insert_id; // Obtener ID del movimiento insertado
		
					// Actualizar el saldo del lote si se utilizó un lote
					if ($lote_id > 0) {
						// Actualizar el lote con el nuevo saldo
						$updateLote = $mysqli->prepare("UPDATE lotes SET cantidad = ? WHERE lote_id = ?");
						$updateLote->bind_param("ii", $nuevo_saldo, $lote_id);
						$updateLote->execute();
		
						// Si el saldo del lote es 0, marcar el lote como inactivo
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
		
		
		protected function getSaldoProductosMovimientosModelo($productos_id)
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
	}