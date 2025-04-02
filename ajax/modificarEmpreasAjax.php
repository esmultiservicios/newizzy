<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['empresa_id']) && isset($_POST['empresa_empresa']) && isset($_POST['telefono_empresa']) && isset($_POST['correo_empresa']) && isset($_POST['rtn_empresa']) && isset($_POST['direccion_empresa'])){
		require_once "../controladores/empresaControlador.php";
		$insVarios = new empresaControlador();
		
		echo $insVarios->edit_empresa_controlador();
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