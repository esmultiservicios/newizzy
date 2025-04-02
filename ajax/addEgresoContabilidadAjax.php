<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['proveedor_egresos']) && isset($_POST['cuenta_egresos']) && isset($_POST['empresa_egresos']) && isset($_POST['fecha_egresos']) && isset($_POST['factura_egresos']) && isset($_POST['subtotal_egresos']) && isset($_POST['isv_egresos']) && isset($_POST['descuento_egresos']) && isset($_POST['nc_egresos']) && isset($_POST['total_egresos'])){
		require_once "../controladores/egresosContabilidadControlador.php";
		$insVarios = new egresosContabilidadControlador();
		
		echo $insVarios->agregar_egresos_contabilidad_controlador();
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