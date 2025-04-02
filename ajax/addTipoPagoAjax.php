<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['confTipoPago']) && isset($_POST['confCuentaTipoPago'])){
		require_once "../controladores/tipoPagoControlador.php";
		$insVarios = new tipoPagoControlador();
		
		echo $insVarios->agregar_tipo_pago_controlador();
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