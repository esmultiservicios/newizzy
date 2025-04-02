<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nomina_detale']) && isset($_POST['nomina_pago_planificado_id'])){
		require_once "../controladores/nominaControlador.php";
		$insVarios = new nominaControlador();
		
		echo $insVarios->agregar_nomina_controlador();
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