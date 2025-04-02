<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['permisos_tipo_user_id'])){
		require_once "../controladores/tipoUsuarioAccesosControlador.php";
		$insVarios = new tipoUsuarioAccesosControlador();
		
		echo $insVarios->agregar_tipoUsuarioAccesos_controlador();
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