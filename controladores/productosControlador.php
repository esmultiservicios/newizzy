<?php
if ($peticionAjax) {
	require_once '../modelos/productosModelo.php';
} else {
	require_once './modelos/productosModelo.php';
}

class productosControlador extends productosModelo
{
	public function agregar_productos_controlador()
	{
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
	
		$empresa = $_SESSION['empresa_id_sd'];
		$almacen_id = 1;  // ALMACEN 1 POR DEFAULT
		$medida_id = mainModel::cleanStringConverterCase($_POST['medida'] ?? 0);
		$producto_superior = mainModel::cleanString($_POST['producto_superior']) == '' ? 0 : mainModel::cleanString($_POST['producto_superior']);
		$categoria_id = mainModel::cleanStringConverterCase($_POST['producto_categoria'] ?? 0);
		$tipo_producto = mainModel::cleanStringConverterCase($_POST['tipo_producto'] ?? 1);
		$nombre = mainModel::cleanString($_POST['producto']);
		$descripcion = mainModel::cleanString($_POST['descripcion']);
		$cantidad = 0;
		$precio_compra = mainModel::cleanString($_POST['precio_compra'] === '' ? 0 : $_POST['precio_compra']);
		$porcentaje_venta = mainModel::cleanString($_POST['porcentaje_venta'] === '' ? 0 : $_POST['porcentaje_venta']);
		$precio_venta = mainModel::cleanString($_POST['precio_venta'] === '' ? 0 : $_POST['precio_venta']);
		$cantidad_mayoreo = mainModel::cleanString($_POST['cantidad_mayoreo'] === '' ? 3 : $_POST['cantidad_mayoreo']);
		$precio_mayoreo = mainModel::cleanString($_POST['precio_mayoreo'] === '' ? 0 : $_POST['precio_mayoreo']);
		$cantidad_minima = mainModel::cleanString($_POST['cantidad_minima'] === '' ? 0 : $_POST['cantidad_minima']);
		$cantidad_maxima = mainModel::cleanString($_POST['cantidad_maxima'] === '' ? 0 : $_POST['cantidad_maxima']);
	
		if ($_POST['bar_code_product'] == '') {
			$flag_barcode = true;
			while ($flag_barcode) {
				$result_barcode = productosModelo::valid_bar_code_productos_modelo(mainModel::generarCodigoBarra(), $empresa);
				if ($result_barcode->num_rows == 0) {
					$bar_code_product = mainModel::generarCodigoBarra();
					$flag_barcode = false;
				} else {
					$flag_barcode = true;
				}
			}
		} else {
			$bar_code_product = mainModel::cleanString($_POST['bar_code_product']);
		}
	
		// Validaciones de campos vacíos
		$cantidad = ($cantidad == '' || $cantidad == null) ? 0 : $cantidad;
		$precio_compra = ($precio_compra == '') ? 0 : $precio_compra;
		$porcentaje_venta = ($porcentaje_venta == '') ? 0 : $porcentaje_venta;
		$cantidad_minima = ($cantidad_minima == '') ? 0 : $cantidad_minima;
		$cantidad_maxima = ($cantidad_maxima == '') ? 0 : $cantidad_maxima;
		$precio_mayoreo = ($precio_mayoreo == '') ? 0 : $precio_mayoreo;
	
		$colaborador_id = $_SESSION['colaborador_id_sd'];
		$fecha_registro = date('Y-m-d H:i:s');
		$file = 'image_preview.png';
		$file_exist = 0;
	
		// FILE IMAGE
		if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name'])) {
			$file = $_FILES['file']['name'];
			$path = $_SERVER['DOCUMENT_ROOT'] . PRODUCT_PATH . $file;
			if (file_exists($path)) {
				$file_exist = 1;
			} else {
				move_uploaded_file($_FILES['file']['tmp_name'], $path);
			}
		}
	
		$estado = 1;
		$isv_venta = isset($_POST['producto_isv_factura']) ? $_POST['producto_isv_factura'] : 2;
		$isv_compra = isset($_POST['producto_isv_compra']) ? $_POST['producto_isv_compra'] : 2;
	
		$datos = [
			'bar_code_product' => $bar_code_product,
			'almacen_id' => $almacen_id,
			'medida_id' => $medida_id,
			'id_producto_superior' => $producto_superior,
			'categoria_id' => $categoria_id,
			'tipo_producto' => $tipo_producto,
			'nombre' => $nombre,
			'descripcion' => $descripcion,
			'precio_compra' => $precio_compra,
			'porcentaje_venta' => $porcentaje_venta,
			'precio_venta' => $precio_venta,
			'cantidad_mayoreo' => $cantidad_mayoreo,
			'precio_mayoreo' => $precio_mayoreo,
			'cantidad_minima' => $cantidad_minima,
			'cantidad_maxima' => $cantidad_maxima,
			'colaborador_id' => $colaborador_id,
			'fecha_registro' => $fecha_registro,
			'estado' => $estado,
			'isv_venta' => $isv_venta,
			'isv_compra' => $isv_compra,
			'file' => $file,
			'empresa' => $empresa,
		];
	
		// Validación de imagen existente
		if ($file_exist == 1) {
			return mainModel::showNotification([
				"title" => "Error",
				"text" => "El nombre de la imagen ya existe, por favor corregir",
				"type" => "error"
			]);
		}
	
		// Validación de código de barras existente
		$result = productosModelo::valid_bar_code_productos_modelo($bar_code_product, $empresa);
		if ($result->num_rows > 0) {
			return mainModel::showNotification([
				"title" => "Error",
				"text" => "El código de barra ya existe",
				"type" => "error"
			]);
		}
	
		// Validación de nombre de producto existente
		$result_nombre = productosModelo::valid_nombre_producto_modelo($nombre, $empresa);
		if ($result_nombre->num_rows > 0) {
			return mainModel::showNotification([
				"title" => "Error",
				"text" => "El nombre de producto ya existe",
				"type" => "error"
			]);
		}
	
		$mainModel = new mainModel();
		$planConfig = $mainModel->getPlanConfiguracionMainModel();
		
		// Solo evaluar si existe configuración de plan
		if (!empty($planConfig)) {
			$limiteProductos = (int)($planConfig['productos'] ?? 0);
			
			// Caso 1: Límite es 0 (bloquear)
			if ($limiteProductos === 0) {
				return $mainModel->showNotification([
					"type" => "error",
					"title" => "Acceso restringido",
					"text" => "Su plan actual no permite registrar productos."
				]);
			}
			
			// Caso 2: Si tiene límite > 0, validar disponibilidad
			$totalRegistrados = (int)productosModelo::getTotalProductosRegistrados();
			
			if ($totalRegistrados >= $limiteProductos) {
				return $mainModel->showNotification([
					"type" => "error",
					"title" => "Límite alcanzado",
					"text" => "Límite de productos alcanzado (Máximo: $limiteProductos). Actualiza tu plan."
				]);
			}
		}	

		// Registrar el producto
		$query = productosModelo::agregar_productos_modelo($datos);
		if (!$query) {
			return mainModel::showNotification([
				"title" => "Error",
				"text" => "No se pudo registrar el producto",
				"type" => "error"
			]);
		}
	
		// Proceso exitoso - registrar movimientos si es necesario
		$consulta_factura = productosModelo::consultar_codigo_producto($nombre)->fetch_assoc();
		$productos_id = $consulta_factura['productos_id'];
	
		$tipo_productos = '';
		$result_tipo_producto = productosModelo::tipo_producto_modelo($productos_id);
		if ($result_tipo_producto->num_rows > 0) {
			$valores2 = $result_tipo_producto->fetch_assoc();
			$tipo_productos = $valores2['tipo_producto'];
		}
	
		$datos_movimientos_productos = [
			'productos_id' => $productos_id,
			'documento' => 'Creacion de Producto',
			'cantidad_entrada' => $cantidad,
			'cantidad_salida' => 0,
			'saldo' => 0,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa,
			'clientes_id' => 0,
			'comentario' => '',
			'almacen_id' => $almacen_id
		];
	
		if ($cantidad > 0 && ($tipo_productos == 'Producto' || $tipo_productos == 'Insumos')) {
			productosModelo::agregar_movimientos_productos_modelo($datos_movimientos_productos);
		}
	
		// Registrar en historial
		mainModel::guardarHistorial([
			"modulo" => 'Productos',
			"colaboradores_id" => $_SESSION['colaborador_id_sd'],
			"status" => "Registro",
			"observacion" => "Se registró el producto {$datos['nombre']} con código {$datos['bar_code_product']}",
			"fecha_registro" => date("Y-m-d H:i:s")
		]);
	
		return mainModel::showNotification([
			"type" => "success",
			"title" => "Registro exitoso",
			"text" => "Producto registrado correctamente",
			"form" => "formProductos",
			"funcion" => "listar_productos();getProductos();getCategoriaProductos();getTipoProducto();getAlmacen();getMedida(0);getEmpresaProductos();"
		]);
	}

	public function edit_productos_controlador()
	{
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
	
		// Validar campos requeridos
		$requiredFields = [
			'productos_id' => "ID del producto",
			'producto' => "Nombre del producto",
			'precio_compra' => "Precio de compra",
			'precio_venta' => "Precio de venta"
		];
		
		$missingFields = [];
		foreach ($requiredFields as $field => $name) {
			if (!isset($_POST[$field]) || empty($_POST[$field])) {
				$missingFields[] = $name;
			}
		}
		
		if (!empty($missingFields)) {
			return mainModel::showNotification([
				"title" => "Campos requeridos",
				"text" => "Faltan los siguientes campos: ".implode(", ", $missingFields),
				"type" => "error"
			]);
		}
	
		// Limpiar y validar datos
		$productos_id = mainModel::cleanString($_POST['productos_id']);
		$nombre = mainModel::cleanString($_POST['producto']);
		$descripcion = mainModel::cleanString($_POST['descripcion'] ?? '');
		$precio_compra = (float)mainModel::cleanString($_POST['precio_compra']);
		$porcentaje_venta = (float)mainModel::cleanString($_POST['porcentaje_venta'] ?? 0);
		$precio_venta = (float)mainModel::cleanString($_POST['precio_venta']);
		$precio_mayoreo = (float)mainModel::cleanString($_POST['precio_mayoreo'] ?? 0);
		$cantidad_minima = (int)mainModel::cleanString($_POST['cantidad_minima'] ?? 0);
		$cantidad_maxima = (int)mainModel::cleanString($_POST['cantidad_maxima'] ?? 0);
		
		// Validar precios
		if ($precio_compra < 0 || $precio_venta < 0) {
			return mainModel::showNotification([
				"title" => "Error en precios",
				"text" => "Los precios no pueden ser negativos",
				"type" => "error"
			]);
		}
			
		// Manejo de imagen
		$cargarLogo = false;
		$file = 'image_preview.png';
		$file_exist = false;
		
		if (!empty($_FILES['file']['name'])) {
			$cargarLogo = true;
			$file = mainModel::cleanString($_FILES['file']['name']);
			$path = $_SERVER['DOCUMENT_ROOT'] . PRODUCT_PATH . $file;
	
			if (file_exists($path)) {
				$file_exist = true;
			} else {
				if (!move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
					return mainModel::showNotification([
						"title" => "Error en imagen",
						"text" => "No se pudo subir la imagen del producto",
						"type" => "error"
					]);
				}
			}
		}
	
		// Validar si el nombre ya existe (excluyendo el producto actual)
		$nombreExistente = productosModelo::valid_nombre_producto_modelo($nombre, $_SESSION['empresa_id_sd']);
		if ($nombreExistente->num_rows > 0) {
			$productoExistente = $nombreExistente->fetch_assoc();
			if ($productoExistente['productos_id'] != $productos_id) {
				return mainModel::showNotification([
					"title" => "Nombre duplicado",
					"text" => "Ya existe un producto con este nombre",
					"type" => "error"
				]);
			}
		}
	
		// Configurar estados
		$estado = isset($_POST['producto_activo']) && $_POST['producto_activo'] == 'on' ? 1 : 0;
		$isv_venta = isset($_POST['producto_isv_factura']) ? (int)$_POST['producto_isv_factura'] : 2;
		$isv_compra = isset($_POST['producto_isv_compra']) ? (int)$_POST['producto_isv_compra'] : 2;
	
		$datos = [
			'productos_id' => $productos_id,
			'nombre' => $nombre,
			'descripcion' => $descripcion,
			'precio_compra' => $precio_compra,
			'porcentaje_venta' => $porcentaje_venta,
			'precio_venta' => $precio_venta,
			'precio_mayoreo' => $precio_mayoreo,
			'cantidad_minima' => $cantidad_minima,
			'cantidad_maxima' => $cantidad_maxima,
			'estado' => $estado,
			'isv_venta' => $isv_venta,
			'isv_compra' => $isv_compra,
			'file' => $file,
			'cargarLogo' => $cargarLogo,
		];
	
		// Actualizar producto
		$query = productosModelo::edit_productos_modelo($datos);
	
		if ($query) {
			// Registrar en historial
			mainModel::guardarHistorial([
				"modulo" => 'Productos',
				"colaboradores_id" => $_SESSION['colaborador_id_sd'],
				"status" => "Actualización",
				"observacion" => "Se actualizó el producto {$nombre} (ID: {$productos_id})",
				"fecha_registro" => date("Y-m-d H:i:s")
			]);
	
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Producto actualizado",
				"text" => "El producto se ha actualizado correctamente",
				"funcion" => "listar_productos();getEmpresaProductos();getCategoriaProductos();getAlmacen();getTipoProducto();getMedida(1);"
			]);
		}
	
		return mainModel::showNotification([
			"title" => "Error al actualizar",
			"text" => "No se pudo actualizar el producto",
			"type" => "error"
		]);
	}

	public function edit_bodega_productos_controlador()
	{
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
	
		$productos_id = mainModel::cleanString($_POST['productos_id']);
		$bodega_actual = mainModel::cleanString($_POST['id_bodega_actual']);
		$bodega = mainModel::cleanString($_POST['id_bodega']);
		$cantidad = mainModel::cleanString($_POST['cantidad_movimiento']);
		$lote_id = mainModel::cleanString($_POST['lote_id_productos']);
		$empresa_id = mainModel::cleanString($_POST['empresa_id_productos']);
		$saldoProducto = 0;
	
		$comentario = isset($_POST['movimiento_comentario']) ? 
			mainModel::cleanString($_POST['movimiento_comentario']) : '';
		$clientes_id = 0;
	
		$datos = [
			'productos_id' => $productos_id,
			'bodega' => $bodega,
			'cantidad' => $cantidad
		];
	
		$fecha_registro = date('Y-m-d H:i:s');
		$saldo = 0;
	
		// Verificamos producto hijo
		$result_productos = mainModel::getProductoHijo($productos_id);
		$procesosHijosExitosos = true;
	
		if ($result_productos->num_rows > 0) {
			while ($consulta = $result_productos->fetch_assoc()) {
				$id_producto_hijo = intval($consulta['productos_id']);
				if ($id_producto_hijo != 0 && $id_producto_hijo != 'null') {
					// OBTENER LA MEDIDA DEL PRODUCTO PADRE
					$medidaName = strtolower(mainModel::getMedidaProductoPadre($productos_id)->fetch_assoc());
	
					if ($medidaName == 'ton') {  // Medida en Toneladas DEL HIJO
						$quantity = $cantidad * 2204.623;
	
						// OBTENEMOS EL SALDO DEL PRODUCTO HIJO
						$consultaSaldoProductoHijo = mainModel::getSaldoProductosMovimientosBodega($productos_id, $bodega_actual)->fetch_assoc();
						$saldoProductoHijo = doubleval($consultaSaldoProductoHijo['saldo']);
	
						$saldoNuevoProductoHijo = $saldoProductoHijo + doubleval($quantity);
	
						$datosHijo = [
							'productos_id' => $id_producto_hijo,
							'cantidad_entrada' => $quantity,
							'cantidad_salida' => 0,
							'saldo' => $saldoNuevoProductoHijo,
							'fecha_registro' => $fecha_registro,
							'empresa' => $empresa_id,
							'comentario' => $comentario,
							'clientes_id' => $clientes_id,
							'almacen_id' => $bodega,
							'lote_id' => $lote_id
						];
	
						$queryIngreso = mainModel::agregar_movimiento_productos_modelo($datosHijo);
						if (!$queryIngreso) {
							$procesosHijosExitosos = false;
						}
					}
				}
			}
		}
	
		if (!$procesosHijosExitosos) {
			return mainModel::showNotification([
				"title" => "Error",
				"text" => "Error al procesar productos hijos",
				"type" => "error"
			]);
		}
	
		// OBTENEMOS EL SALDO DEL PRODUCTO
		$consultaSaldoBodegaActual = mainModel::getSaldoProductosMovimientosBodega($productos_id, $bodega_actual)->fetch_assoc();
		$saldoProductoBodegaActual = doubleval($consultaSaldoBodegaActual['saldo']);
	
		$consultaSaldoBodegaNueva = mainModel::getSaldoProductosMovimientosBodega($productos_id, $bodega)->fetch_assoc();
		$saldoProductoBodegaNueva = doubleval($consultaSaldoBodegaNueva['saldo']);
	
		$saldoBodegaNueva = $saldoProductoBodegaNueva + doubleval($cantidad);
	
		// INGRESAMOS EL NUEVO REGISTRO EN LA ENTIDAD MOVIMIENTOS
		$datos = [
			'productos_id' => $productos_id,
			'cantidad_entrada' => $cantidad,
			'cantidad_salida' => 0,
			'saldo' => $saldoBodegaNueva,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa_id,
			'comentario' => $comentario,
			'clientes_id' => $clientes_id,
			'almacen_id' => $bodega,
			'lote_id' => $lote_id
		];
	
		$queryIngreso = mainModel::agregar_movimiento_productos_modelo($datos);
	
		$saldoNuevo = $saldoProducto + doubleval($cantidad);
		$saldoBodegaActual = $saldoProductoBodegaActual - doubleval($cantidad);
	
		// EGRESO DEL PRODUCTO DE LA BODEGA ACTUAL
		$datosEgreso = [
			'productos_id' => $productos_id,
			'cantidad_entrada' => 0,
			'cantidad_salida' => $cantidad,
			'saldo' => $saldoBodegaActual,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa_id,
			'comentario' => $comentario,
			'clientes_id' => $clientes_id,
			'almacen_id' => $bodega_actual,
			'lote_id' => $lote_id
		];
	
		$queryEgreso = mainModel::agregar_movimiento_productos_modelo($datosEgreso);
	
		if (!$queryEgreso || !$queryIngreso) {
			return mainModel::showNotification([
				"title" => "Error",
				"text" => "No se pudo completar la transferencia entre bodegas",
				"type" => "error"
			]);
		}
	
		// Registrar en historial
		mainModel::guardarHistorial([
			"modulo" => 'Productos',
			"colaboradores_id" => $_SESSION['colaborador_id_sd'],
			"status" => "Transferencia",
			"observacion" => "Se transfirió producto ID: {$productos_id} de bodega {$bodega_actual} a {$bodega}",
			"fecha_registro" => date("Y-m-d H:i:s")
		]);
	
		return mainModel::showNotification([
			"type" => "success",
			"title" => "Transferencia exitosa",
			"text" => "El movimiento entre bodegas se realizó correctamente",
			"form" => "formMovimientos",
			"funcion" => "inventario_transferencia();setValoresProduco();",
			"closeAllModals" => true
		]);
	}

	public function delete_productos_controlador()
	{
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

		$productos_id = $_POST['productos_id'];

        $producto = mainModel::consultar_tabla('productos', ['nombre', 'barCode'], "productos_id = {$productos_id}");
        
        if (empty($producto)) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Producto no encontrado"
            ]);
            exit();
        }
        
        $nombre = $producto[0]['nombre'] ?? '';

		// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
		if(productosModelo::valid_productos_movimientos($productos_id)->num_rows > 0){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "No se puede eliminar",
                "message" => "El producto {$nombre} tiene movimientos asociadas"
            ]);
            exit();                
        }

		if(!productosModelo::delete_productos_modelo($productos_id)){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el producto {$nombre}"
            ]);
            exit();
        }

		header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "title" => "Eliminado",
            "message" => "Producto {$nombre} eliminado correctamente"
        ]);
        exit();
	}
}
