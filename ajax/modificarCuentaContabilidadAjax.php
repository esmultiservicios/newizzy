<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['cuentas_id']) && isset($_POST['cuenta_nombre'])){
		require_once "../controladores/cuentaContabilidadControlador.php";
		$insVarios = new cuentaContabilidadControlador();
		
		echo $insVarios->edit_productos_controlador();
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