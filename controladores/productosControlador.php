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
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
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

		if ($cantidad == '' || $cantidad == null) {
			$cantidad = 0;
		}

		if ($precio_compra == '') {
			$precio_compra = 0;
		}

		if ($porcentaje_venta == '') {
			$porcentaje_venta = 0;
		}

		if ($porcentaje_venta == '') {
			$porcentaje_venta = 0;
		}

		if ($cantidad_minima == '') {
			$cantidad_minima = 0;
		}

		if ($cantidad_maxima == '') {
			$cantidad_maxima = 0;
		}

		if ($precio_mayoreo == '') {
			$precio_mayoreo = 0;
		}

		$colaborador_id = $_SESSION['colaborador_id_sd'];
		$fecha_registro = date('Y-m-d H:i:s');
		$file = 'image_preview.png';
		$file_exist = 0;

		// FILE IMAGE
		if (isset($_FILES['file']['name'])) {
			if (!empty($_FILES['file']['name'])) {
				// MOVEMOS LA IMAGEN EN LA CARPETA DE IMAGENES
				$file = $_FILES['file']['name'];
				$path = $_SERVER['DOCUMENT_ROOT'] . PRODUCT_PATH . $file;
				if (file_exists($path)) {
					$file_exist = 1;
				} else {
					move_uploaded_file($_FILES['file']['tmp_name'], $path);
				}
			}
		}

		$estado = 1;

		if (isset($_POST['producto_isv_factura'])) {
			$isv_venta = $_POST['producto_isv_factura'];
		} else {
			$isv_venta = 2;
		}

		if (isset($_POST['producto_isv_compra'])) {
			$isv_compra = $_POST['producto_isv_compra'];
		} else {
			$isv_compra = 2;
		}

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

		// EVALUAMOS QUE LA VARIABLE DEL ARCHIVO ESTE EN FALSE PARA ALMACENAR EL REGISTRO
		if ($file_exist == 0) {
			// VALIDAMOS QUE NO EXISTA EL CODIGO DE BARRA
			$result = productosModelo::valid_bar_code_productos_modelo($bar_code_product, $empresa);

			if ($result->num_rows == 0) {
				// VALIDAMOS QUE NO EXISTA EL NOMBRE DEL PRODUCTO
				$result_nombre = productosModelo::valid_nombre_producto_modelo($nombre, $empresa);

				if ($result_nombre->num_rows == 0) {
					$query = productosModelo::agregar_productos_modelo($datos);

					if ($query) {
						$consulta_factura = productosModelo::consultar_codigo_producto($nombre)->fetch_assoc();
						$productos_id = $consulta_factura['productos_id'];

						// CONSULTAMOS LA CATEGORIA DEL PRODUCTOS
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

						if ($cantidad > 0) {
							if ($tipo_productos == 'Producto' || $tipo_productos == 'Insumos') {
								productosModelo::agregar_movimientos_productos_modelo($datos_movimientos_productos);
							}
						}

						$alert = [
							'alert' => 'save_simple',
							'title' => 'Registro almacenado',
							'text' => 'El registro se ha almacenado correctamente',
							'type' => 'success',
							'btn-class' => 'btn-primary',
							'btn-text' => '¡Bien Hecho!',
							'form' => 'formProductos',
							'id' => 'proceso_productos',
							'valor' => 'Registro',
							'funcion' => 'listar_productos();getProductos();getCategoriaProductos();getTipoProducto();getAlmacen();getMedida(0);getEmpresaProductos();',
							'modal' => '',
						];
					} else {
						$alert = [
							'alert' => 'simple',
							'title' => 'Ocurrio un error inesperado',
							'text' => 'No hemos podido procesar su solicitud',
							'type' => 'error',
							'btn-class' => 'btn-danger',
						];
					}
				} else {
					$alert = [
						'alert' => 'simple',
						'title' => 'Resgistro ya existe',
						'text' => 'Lo sentimos este nombre de producto ya existe',
						'type' => 'error',
						'btn-class' => 'btn-danger',
					];
				}
			} else {
				$alert = [
					'alert' => 'simple',
					'title' => 'Resgistro ya existe',
					'text' => 'Lo sentimos este código de barra ya existe',
					'type' => 'error',
					'btn-class' => 'btn-danger',
				];
			}
		} else {
			$alert = [
				'alert' => 'simple',
				'title' => 'Resgistro ya existe',
				'text' => 'Lo sentimos el nombre de la imagen ya existe, por favor corregir',
				'type' => 'error',
				'btn-class' => 'btn-danger',
			];
		}

		return mainModel::sweetAlert($alert);
	}

	public function edit_productos_controlador()
	{
		;
		$productos_id = mainModel::cleanString($_POST['productos_id']);
		$nombre = mainModel::cleanString($_POST['producto']);
		$descripcion = mainModel::cleanString($_POST['descripcion']);
		$precio_compra = mainModel::cleanString($_POST['precio_compra']);
		$porcentaje_venta = mainModel::cleanString($_POST['porcentaje_venta']);
		$precio_venta = mainModel::cleanString($_POST['precio_venta']);
		$precio_mayoreo = mainModel::cleanString($_POST['precio_mayoreo']);
		$cantidad_minima = mainModel::cleanString($_POST['cantidad_minima']);
		$cantidad_maxima = mainModel::cleanString($_POST['cantidad_maxima']);
		$file_exist = false;

		if ($precio_mayoreo == '') {
			$precio_mayoreo = 0;
		}

		// FILE IMAGE
		$cargarLogo = false;
		$file = 'image_preview.png';
		if (isset($_FILES['file']['name'])) {
			if (!empty($_FILES['file']['name'])) {
				$cargarLogo = true;
				// MOVEMOS LA IMAGEN EN LA CARPETA DE IMAGENES
				$file = $_FILES['file']['name'];
				$path = $_SERVER['DOCUMENT_ROOT'] . PRODUCT_PATH . $file;

				if (file_exists($path)) {
					$file_exist = true;
				} else {
					move_uploaded_file($_FILES['file']['tmp_name'], $path);
				}
			}
		}

		if (isset($_POST['producto_activo'])) {
			$estado = $_POST['producto_activo'];
		} else {
			$estado = 2;
		}

		if (isset($_POST['producto_isv_factura'])) {
			$isv_venta = $_POST['producto_isv_factura'];
		} else {
			$isv_venta = 2;
		}

		if (isset($_POST['producto_isv_compra'])) {
			$isv_compra = $_POST['producto_isv_compra'];
		} else {
			$isv_compra = 2;
		}

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

		$query = productosModelo::edit_productos_modelo($datos);

		if ($query) {
			$alert = [
				'alert' => 'edit',
				'title' => 'Registro modificado',
				'text' => 'El registro se ha modificado correctamente',
				'type' => 'success',
				'btn-class' => 'btn-primary',
				'btn-text' => '¡Bien Hecho!',
				'form' => 'formProductos',
				'id' => 'proceso_productos',
				'valor' => 'Editar',
				'funcion' => 'listar_productos();getEmpresaProductos();getCategoriaProductos();getAlmacen();getTipoProducto();getMedida(1);',
				'modal' => '',
			];
		} else {
			$alert = [
				'alert' => 'simple',
				'title' => 'Ocurrio un error inesperado',
				'text' => 'No hemos podido procesar su solicitud',
				'type' => 'error',
				'btn-class' => 'btn-danger',
			];
		}

		return mainModel::sweetAlert($alert);
	}

	public function edit_bodega_productos_controlador()
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$productos_id = mainModel::cleanString($_POST['productos_id']);
		$bodega_actual = mainModel::cleanString($_POST['id_bodega_actual']);
		$bodega = mainModel::cleanString($_POST['id_bodega']);
		$cantidad = mainModel::cleanString($_POST['cantidad_movimiento']);
		$lote_id = mainModel::cleanString($_POST['lote_id_productos']);
		$empresa_id = mainModel::cleanString($_POST['empresa_id_productos']);

		$saldoProducto = 0;

		if (isset($_POST['movimiento_comentario'])) {
			$comentario = mainModel::cleanString($_POST['movimiento_comentario']);
		} else {
			$comentario = '';
		}

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

		if ($result_productos->num_rows > 0) {
			while ($consulta = $result_productos->fetch_assoc()) {
				$id_producto_hijo = intval($consulta['productos_id']);
				if ($id_producto_hijo != 0 || $id_producto_hijo != 'null') {
					// agregos el producto hijo a la bodega de transferencia

					// OBTENER LA MEDIDA DEL PRODUCTO PADRE
					$medidaName = strtolower(mainModel::getMedidaProductoPadre($productos_id)->fetch_assoc());

					if ($medidaName == 'ton') {  // Medida en Toneladas DEL HIJO
						$quantity = $cantidad * 2204.623;

						// OTENEMOS EL SALDO DEL PRODCUTO HIJO
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
					}
				}
			}
		}

		// OTENEMOS EL SALDO DEL PRODCUTO
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

		if ($queryEgreso && $queryIngreso) {
			$alert = [
				'alert' => 'edit',
				'title' => 'Agregar Movimiento Almacen',
				'text' => 'El registro se ha almacenado correctamente',
				'type' => 'success',
				'btn-class' => 'btn-primary',
				'btn-text' => '¡Bien Hecho!',
				'form' => 'formMovimientos',
				'id' => 'proceso_movimientos',
				'valor' => 'Editar',
				'funcion' => 'inventario_transferencia();setValoresProduco();',
				'modal' => '',
			];
		} else {
			$alert = [
				'alert' => 'simple',
				'title' => 'Ocurrio un error inesperado en almacen',
				'text' => 'No hemos podido procesar su solicitud',
				'type' => 'error',
				'btn-class' => 'btn-danger',
			];
		}

		return mainModel::sweetAlert($alert);
	}

	public function delete_productos_controlador()
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$productos_id = $_POST['productos_id'];

		// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
		$result_valid_productos_movimientos = productosModelo::valid_productos_movimientos($productos_id);

		if ($result_valid_productos_movimientos->num_rows == 0) {
			$query = productosModelo::delete_productos_modelo($productos_id);

			if ($query) {
				$alert = [
					'alert' => 'clear',
					'title' => 'Registro eliminado',
					'text' => 'El registro se ha eliminado correctamente',
					'type' => 'success',
					'btn-class' => 'btn-primary',
					'btn-text' => '¡Bien Hecho!',
					'form' => 'formProductos',
					'id' => 'proceso_productos',
					'valor' => 'Eliminar',
					'funcion' => 'listar_productos();getProductos();getCategoriaProductos();getTipoProducto();getAlmacen();getMedida(0);getEmpresaProductos();',
					'modal' => 'modal_registrar_productos',
				];
			} else {
				$alert = [
					'alert' => 'simple',
					'title' => 'Ocurrio un error inesperado',
					'text' => 'No hemos podido procesar su solicitud',
					'type' => 'error',
					'btn-class' => 'btn-danger',
				];
			}
		} else {
			$alert = [
				'alert' => 'simple',
				'title' => 'Este registro cuenta con información almacenada',
				'text' => 'No se puede eliminar este registro',
				'type' => 'error',
				'btn-class' => 'btn-danger',
			];
		}

		return mainModel::sweetAlert($alert);
	}
}
