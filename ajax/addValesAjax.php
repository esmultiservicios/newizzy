<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['vale_empleado']) && isset($_POST['vale_monto'])){
		require_once "../controladores/nominaControlador.php";
		$insVarios = new nominaControlador();
		
		echo $insVarios->agregar_vale_controlador();
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