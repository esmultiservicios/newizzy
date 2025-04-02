<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nomina_id']) && isset($_POST['nomina_detalles_id'])){
		require_once "../controladores/nominaControlador.php";
		$insVarios = new nominaControlador();
		
		echo $insVarios->edit_nomina_detalles_controlador();
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