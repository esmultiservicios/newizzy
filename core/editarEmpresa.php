<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$empresa_id  = $_POST['empresa_id'];
	$result = $insMainModel->getEmpresasEdit($empresa_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'],
		1 => $valores2['telefono'],
		2 => $valores2['correo'],
		3 => $valores2['rtn'],
		4 => $valores2['ubicacion'],
		5 => $valores2['estado'],	
		6 => $valores2['razon_social'],	
		7 => $valores2['otra_informacion'],	
		8 => $valores2['eslogan'],
		9 => $valores2['celular'],
		10 => $valores2['facebook'],
		11 => $valores2['sitioweb'],
		12 => $valores2['horario'],						
		13 => $valores2['logotipo'],	
	);
	echo json_encode($datos);
?>	