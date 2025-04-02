<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['cuentas_id'])){
		require_once "../controladores/cuentaContabilidadControlador.php";
		$insVarios = new cuentaContabilidadControlador();
		
		echo $insVarios->delete_cuneta_contabilidad_controlador();
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