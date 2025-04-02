<?php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['usuarios_colaborador_id']) && isset($_POST['colaborador_id_usuario']) && isset($_POST['privilegio_id']) && isset($_POST['correo_usuario']) && isset($_POST['empresa_usuario']) && isset($_POST['tipo_user']) && isset($_POST['usuarios_activo'])){
		require_once "../controladores/usuarioControlador.php";
		$insVarios = new usuarioControlador();

		echo $insVarios->agregar_usuario_controlador();
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