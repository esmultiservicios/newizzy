<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['egresos_id'])){
		require_once "../controladores/egresosContabilidadControlador.php";
		$insVarios = new egresosContabilidadControlador();
		
		echo $insVarios->edit_egresos_contabilidad_controlador();
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