<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['empresa_secuencia']) && isset($_POST['cai_secuencia']) && isset($_POST['prefijo_secuencia']) && isset($_POST['relleno_secuencia']) && isset($_POST['incremento_secuencia']) && isset($_POST['siguiente_secuencia']) && isset($_POST['rango_inicial_secuencia']) && isset($_POST['rango_final_secuencia']) && isset($_POST['fecha_activacion_secuencia']) && isset($_POST['fecha_limite_secuencia']) && isset($_POST['estado_secuencia']) && isset($_POST['documento_secuencia']) ){
		require_once "../controladores/secuenciaFacturacionControlador.php";
		$insVarios = new secuenciaFacturacionControlador();
		
		echo $insVarios->agregar_secuencia_facturacion_controlador();
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