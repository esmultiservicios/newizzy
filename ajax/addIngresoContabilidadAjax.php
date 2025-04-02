<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['cliente_ingresos']) && isset($_POST['cuenta_ingresos']) && isset($_POST['empresa_ingresos']) && isset($_POST['fecha_ingresos']) && isset($_POST['factura_ingresos']) && isset($_POST['subtotal_ingresos']) && isset($_POST['isv_ingresos']) && isset($_POST['descuento_ingresos']) && isset($_POST['nc_ingresos']) && isset($_POST['total_ingresos'])){
		require_once "../controladores/ingresosContabilidadControlador.php";
		$insVarios = new ingresosContabilidadControlador();
		
		echo $insVarios->agregar_ingresos_contabilidad_controlador();
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