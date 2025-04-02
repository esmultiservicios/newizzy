<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['bk_nm_chk']) && isset($_POST['check_num'])){
		require_once "../controladores/pagoFacturaControlador.php";
		$insVarios = new pagoFacturaControlador();
		
		echo $insVarios->agregar_pago_factura_controlador_cheque();
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