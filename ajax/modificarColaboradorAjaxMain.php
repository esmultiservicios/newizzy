<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaborador_id']) && isset($_POST['nombre_colaborador']) && isset($_POST['apellido_colaborador']) && isset($_POST['telefono_colaborador'])){
		require_once "../controladores/colaboradorControlador.php";
		$insVarios = new colaboradorControlador();
		
		echo $insVarios->editar_colaborador_perfil_controlador();
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