<?php	
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";
require_once "sendEmail.php"; // Asegúrate de que la ruta sea correcta

$servidor = $_POST['server'];
$correo = $_POST['correo'];
$contraseña = $_POST['password'];
$puerto = $_POST['port'];
$SMTPSecure = $_POST['smtpSecure'];
$CharSet = "UTF-8";

$sendEmail = new sendEmail();

$result = $sendEmail->testingMail($servidor, $correo, $contraseña, $puerto, $SMTPSecure, $CharSet);

echo $result;