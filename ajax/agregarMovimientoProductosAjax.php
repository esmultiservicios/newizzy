<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['movimientos_id']) && isset($_POST['movimiento_producto']) && isset($_POST['movimientos_tipo_producto_id']) && isset($_POST['movimiento_cantidad'])){
		require_once "../controladores/movimientoProductosControlador.php";
		$insVarios = new movimientoProductosControlador();
		
		echo $insVarios->agregar_movimiento_productos_controlador();
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