<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";

	$errores = [];

	// Verificar si los parámetros requeridos están en la solicitud POST
	if (empty($_POST['productos_id']) || $_POST['productos_id'] == '0') {
		$errores[] = 'El ID del producto es obligatorio y no puede ser cero.';
	}

	if (empty($_POST['id_bodega']) || $_POST['id_bodega'] == '0') {
		$errores[] = 'El ID de bodega es obligatorio y no puede ser cero.';
	}

	// Si hay errores, mostrarlos
	if (count($errores) > 0) {
		$erroresString = implode('<br>', $errores); // Generar una cadena con todos los errores

		echo "
			<script>
				swal({
					icon: 'error',
					title: '¡Oops!',
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
		exit();  // Detener la ejecución si hay errores
	} else {
		// Si no hay errores, proceder con la lógica del controlador
		require_once "../controladores/productosControlador.php";
		$insVarios = new productosControlador();
		
		// Llamar al método para editar bodega de productos
		echo $insVarios->edit_bodega_productos_controlador();
	}