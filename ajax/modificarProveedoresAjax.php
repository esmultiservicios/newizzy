<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['proveedores_id']) && isset($_POST['nombre_proveedores']) && isset($_POST['rtn_proveedores']) && isset($_POST['dirección_proveedores']) && isset($_POST['telefono_proveedores']) && isset($_POST['correo_proveedores'])){
		require_once "../controladores/proveedoresControlador.php";
		$insVarios = new proveedoresControlador();
		
		echo $insVarios->edit_proveedores_controlador();
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