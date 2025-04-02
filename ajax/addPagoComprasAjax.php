<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	if(isset($_POST['proveedorPurchase']) && isset($_POST['fechaPurchase']) && isset($_POST['tipo_pago_idPurchase'])){
		require_once "../controladores/pagoCompraControlador.php";
		$insVarios = new pagoCompraControlador();
		
		echo $insVarios->agregar_pago_compra_controlador();
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