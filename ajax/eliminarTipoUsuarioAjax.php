<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['tipo_user_id'])){
		require_once "../controladores/tipoUsuarioControlador.php";
		$insVarios = new tipoUsuarioControlador();
		
		echo $insVarios->delete_tipo_usuario_controlador();
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