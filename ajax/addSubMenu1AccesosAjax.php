<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['privilegio_id_accesos'])){
		require_once "../controladores/menuAccesosControlador.php";
		$insVarios = new menuAccesosControlador();
		
		echo $insVarios->agregar_SubMenu1Accesos_controlador();
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