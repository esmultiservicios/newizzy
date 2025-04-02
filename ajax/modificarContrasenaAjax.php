<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['contranaterior']) && isset($_POST['nuevacontra']) && isset($_POST['repcontra'])){
		require_once "../controladores/cambiarContraseñaControlador.php";
		$insVarios = new cambiarContraseñaControlador();
		
		echo $insVarios->edit_contraseña_controlador();
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