<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['almacen_almacen']) && isset($_POST['ubicacion_almacen']) && isset($_POST['facturar_cero']) ){
		require_once "../controladores/almacenControlador.php";
		$insVarios = new almacenControlador();
		
		echo $insVarios->agregar_almacen_controlador();
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