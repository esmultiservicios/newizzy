<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['bk_nm']) && isset($_POST['ben_nm'])){
		require_once "../controladores/pagoCompraControlador.php";
		$insVarios = new pagoCompraControlador();
		
		echo $insVarios->agregar_pago_compra_controlador_transferencia();
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