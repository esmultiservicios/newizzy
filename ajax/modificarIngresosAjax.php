<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['ingresos_id'])){
		require_once "../controladores/ingresosContabilidadControlador.php";
		$insVarios = new ingresosContabilidadControlador();
		
		echo $insVarios->edit_ingresos_contabilidad_controlador();
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