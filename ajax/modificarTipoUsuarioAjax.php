<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['tipo_user_id']) && isset($_POST['tipo_usuario_nombre'])){
		require_once "../controladores/tipoUsuarioControlador.php";
		$insVarios = new tipoUsuarioControlador();
		
		echo $insVarios->edit_tipo_usuario_controlador();
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