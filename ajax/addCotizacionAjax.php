<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	if(isset($_POST['cliente_id']) && isset($_POST['cliente']) && isset($_POST['fecha']) && isset($_POST['colaborador_id']) && isset($_POST['colaborador'])){
		require_once "../controladores/cotizacionControlador.php";
		$insVarios = new cotizacionControlador();
		
		echo $insVarios->agregar_cotizacion_controlador();
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