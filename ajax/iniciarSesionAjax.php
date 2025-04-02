<?php
$peticionAjax = true;
require_once '../core/configGenerales.php';

if (isset($_POST['inputEmail']) && isset($_POST['inputPassword'])) {
	require_once '../controladores/loginControlador.php';
	require_once '../core/mainModel.php';

	$login = new loginControlador();

	if ($login->validar_pago_pendiente_main_server_controlador() == 1) {
		echo $login->iniciar_sesion_controlador();
	} else {
		echo $login->validar_pago_pendiente_main_server_controlador();
	}

	$insMainModel = new mainModel();

	// mainModel::guardar_historial_accesos("Inicio de Sesion");
} else {
	echo "
			<script>
				swal({
					title: 'Error', 
					text: 'Los datos son incorrectos por favor corregir',
					type: 'error', 
					confirmButtonClass: 'btn-danger'
				});\t\t\t
			</script>";
}
