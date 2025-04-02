<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['puestos_id']) && isset($_POST['puesto'])){
		require_once "../controladores/puestosControlador.php";
		$insVarios = new puestosControlador();
		
		echo $insVarios->edit_puestos_controlador();
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