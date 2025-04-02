<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['diarios_id']) && isset($_POST['confCuenta'])){
		require_once "../controladores/diariosControlador.php";
		$insVarios = new diariosControlador();
		
		echo $insVarios->edit_diarios_controlador();
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