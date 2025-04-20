<?php	
	//resetearContrasenaAjax.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['users_id'])){
		require_once "../controladores/cambiarContraseñaControlador.php";
		$insVarios = new cambiarContraseñaControlador();
		
		echo $insVarios->resetear_contraseña_controlador();
	}else{
		echo "
			<script>
				showNotify('error', 'Error', 'Los datos son incorrectos por favor corregir');
			</script>";
	}