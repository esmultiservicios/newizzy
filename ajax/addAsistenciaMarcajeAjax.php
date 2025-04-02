<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['asistencia_empleado'])){
		require_once "../controladores/asistenciaControlador.php";
		$insVarios = new asistenciaControlador();
		
		echo $insVarios->agregar_asistencia_controlador();
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