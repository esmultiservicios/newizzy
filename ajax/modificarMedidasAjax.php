<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['medida_id']) && isset($_POST['medidas_medidas']) && isset($_POST['descripcion_medidas'])){
		require_once "../controladores/medidasControlador.php";
		$insVarios = new medidasControlador();
		
		echo $insVarios->edit_medidas_controlador();
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