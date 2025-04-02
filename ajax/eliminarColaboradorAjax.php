<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaborador_id'])){
		require_once "../controladores/colaboradorControlador.php";
		$insVarios = new colaboradorControlador();
		
		echo $insVarios->delete_colaborador_controlador();
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