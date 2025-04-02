<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$servidor = $_POST['server'];
	$correo = $_POST['correo'];
	$contraseña = $_POST['password'];
	$puerto = $_POST['port'];
	$SMTPSecure = $_POST['smtpSecure'];
	$CharSet = "UTF-8";

	$result = $insMainModel->testingMail($servidor, $correo, $contraseña, $puerto, $SMTPSecure, $CharSet);
	
?>	
