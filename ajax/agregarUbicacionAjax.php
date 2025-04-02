<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['ubicacion_ubicacion']) && isset($_POST['empresa_ubicacion'])){
		require_once "../controladores/ubicacionControlador.php";
		$insVarios = new ubicacionControlador();
		
		echo $insVarios->agregar_ubicacion_controlador();
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