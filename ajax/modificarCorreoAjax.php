<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['serverConfEmail']) && isset($_POST['correoConfEmail']) && isset($_POST['puertoConfEmail']) && isset($_POST['smtpSecureConfEmail']) && isset($_POST['passConfEmail'])){
		require_once "../controladores/correoControlador.php";
		$insVarios = new correoControlador();
		
		echo $insVarios->edit_correo_controlador();
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