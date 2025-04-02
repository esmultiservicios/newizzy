<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['isv_id']) && isset($_POST['valor'])){
		require_once "../controladores/impuestosControlador.php";
		$insVarios = new impuestosControlador();
		
		echo $insVarios->edit_impuestos_controlador();
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