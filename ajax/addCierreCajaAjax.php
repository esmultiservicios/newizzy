<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaboradores_id_apertura'])){
		require_once "../controladores/cierreCajaControlador.php";
		$insVarios = new cierreCajaControlador();
		
		echo $insVarios->cerrar_caja_controlador();
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