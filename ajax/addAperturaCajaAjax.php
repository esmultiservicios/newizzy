<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaboradores_id_apertura']) && isset($_POST['monto_apertura'])){
		require_once "../controladores/aperturaCajaControlador.php";
		$insVarios = new aperturaCajaControlador();
		
		echo $insVarios->agregar_apertura_caja_controlador();
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