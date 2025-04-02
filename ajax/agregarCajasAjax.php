<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nombre_caja']) && isset($_POST['descripcion_caja'])){
		require_once "../controladores/cajaControlador.php";
		$insVarios = new cajaControlador();
		
		echo $insVarios->agregar_caja_controlador();
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