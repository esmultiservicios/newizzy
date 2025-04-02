<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	$errores = [];

	// Verificar si los campos están vacíos o si contienen valores no válidos
	if ($_POST['fecha_caducidad'] == '') {
		$errores[] = 'La fecha de caducidad es obligatoria';
	}
	if ($_POST['productos_id'] == '' || $_POST['productos_id'] == '0') {
		$errores[] = 'El ID de producto no puede estar vacío ni ser cero';
	}
	if ($_POST['id_bodega_actual'] == '' || $_POST['id_bodega_actual'] == '0') {
		$errores[] = 'El ID de bodega actual no puede estar vacío ni ser cero';
	}
	if ($_POST['cantidad_productos'] == '' || $_POST['cantidad_productos'] == '0') {
		$errores[] = 'La cantidad de productos debe ser un valor válido y mayor que cero';
	}
	if ($_POST['empresa_id_productos'] == '') {
		$errores[] = 'El ID de la empresa de productos es obligatorio';
	}
	if ($_POST['lote_id_productos'] == '') {
		$errores[] = 'El ID del lote de productos es obligatorio';
	}
	
	// Si hay errores, mostrarlos
	if (count($errores) > 0) {
		$erroresString = implode('<br>', $errores); // Agrega saltos de línea HTML

		echo "
			<script>
				swal({
					icon: 'error',
					title: '¡Oops!', // Título simple sin HTML
					content: {
						element: 'span',
						attributes: {
							innerHTML: '¡Ups! Los siguientes campos tienen problemas: <br> <b style=\"color: #007bff;\">$erroresString</b><br><span style=\"font-size: 20px;\">❌</span> Por favor, corrígelos. <span style=\"font-size: 20px;\">❗</span>'
						}
					},
					type: 'error', 
					dangerMode: true,
					button: {
						text: 'Entendido',
						value: true,
						visible: true,
						className: 'btn btn-danger',
						closeModal: true
					}
				});            
			</script>
		";
	} else {
		require_once "../controladores/movimientoProductosControlador.php";
		$insVarios = new movimientoProductosControlador();
		echo $insVarios->modificar_fecha_vencimiento_movimiento_productos_controlador();
	}	