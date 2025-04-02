<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['tipo_pago_id'])){
		require_once "../controladores/tipoPagoControlador.php";
		$insVarios = new tipoPagoControlador();
		
		echo $insVarios->edit_tipo_pago_controlador();
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