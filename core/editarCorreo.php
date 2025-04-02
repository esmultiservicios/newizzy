<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$correo_id = $_POST['correo_id'];
	$result = $insMainModel->getCorreoEdit($correo_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['correo_tipo_id'],
		1 => $valores2['server'],
		2 => $valores2['correo'],
		3 => $valores2['port'],		
		4 => $valores2['smtp_secure'],		
		5 => $valores2['estado'],
		6 => $insMainModel->decryption($valores2['password']),											
	);
	echo json_encode($datos);
?>	