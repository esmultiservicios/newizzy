<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['secuencia_facturacion_id'])){
		require_once "../controladores/secuenciaFacturacionControlador.php";
		$insVarios = new secuenciaFacturacionControlador();
		
		echo $insVarios->delete_secuencia_facturacion_controlador();
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