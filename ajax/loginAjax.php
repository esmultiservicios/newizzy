<?php
$peticionAjax = true;
require_once '../core/configGenerales.php';

if (isset($_GET['token'])) {
	require_once '../controladores/loginControlador.php';

	$logout = new loginControlador();

	echo $logout->cerrar_sesion_controlador();
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
