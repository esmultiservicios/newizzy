<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['privilegios_nombre'])){
		require_once "../controladores/privilegioControlador.php";
		$insVarios = new privilegioControlador();
		
		echo $insVarios->agregar_privilegio_controlador();
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