<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['correo']) && isset($_POST['nombre'])){
		require_once "../controladores/correoControlador.php";
		$insVarios = new correoControlador();
		
		echo $insVarios->registrar_destinatarios_correo_controlador();
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