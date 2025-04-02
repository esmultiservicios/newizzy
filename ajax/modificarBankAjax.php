<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['banco_id']) && isset($_POST['confbanco'])){
		require_once "../controladores/bancoControlador.php";
		$insVarios = new bancoControlador();
		
		echo $insVarios->edit_banco_controlador();
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