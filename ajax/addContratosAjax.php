<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['contrato_colaborador_id']) && isset($_POST['contrato_tipo_contrato_id']) && isset($_POST['contrato_pago_planificado_id']) && isset($_POST['contrato_tipo_empleado_id'])){
		require_once "../controladores/contratoControlador.php";
		$insVarios = new contratoControlador();
		
		echo $insVarios->agregar_contrato_controlador();
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