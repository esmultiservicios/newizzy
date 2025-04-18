<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaboradores_id_apertura'])){
		require_once "../controladores/aperturaCajaControlador.php";
		$insVarios = new aperturaCajaControlador();
		
		echo $insVarios->cerrar_caja_controlador();
	}else{
		echo "
			<script>
				showNotify('error', 'Error', 'Los datos son incorrectos por favor corregir');	
			</script>";
	}