<?php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nombre_colaborador']) && isset($_POST['apellido_colaborador']) && isset($_POST['identidad_colaborador']) && isset($_POST['telefono_colaborador']) && isset($_POST['puesto_colaborador']) && isset($_POST['colaboradores_activo'])){
		require_once "../controladores/colaboradorControlador.php";
		$insVarios = new colaboradorControlador();

		echo $insVarios->agregar_colaborador_controlador();
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