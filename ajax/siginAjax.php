<?php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_GET['inputEmail']) && isset($_GET['inputPassword'])){
		require_once "../controladores/loginControlador.php";
		$login = new loginControlador();
		
		echo $login->iniciar_sesion_controlador();
	}else{
		echo "
			<script>
				swal({
					title: 'Error', 
					text: 'Los datos son incorrectos por favor corregir',
					type: 'error', 
					confirmButtonClass: 'btn-danger'
				});			
			</script>";
	}
?>