<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";

	if(isset($_POST['proveedores_id']) && isset($_POST['proveedor']) && isset($_POST['facturaPurchase']) && isset($_POST['colaborador_id']) ){
		require_once "../controladores/comprasControlador.php";
		$insVarios = new comprasControlador();
		
		echo $insVarios->agregar_compras_controlador();
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