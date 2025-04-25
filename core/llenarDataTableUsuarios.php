<?php
//llenarDataTableUsuarios.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";
require_once "Database.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$datos = [
    "privilegio_id" => $_SESSION['privilegio_sd'],
    "colaborador_id" => $_SESSION['colaborador_id_sd'],	
    "empresa_id" => $_SESSION['empresa_id_sd'],
    "db_cliente" => $_SESSION['db_cliente']
];	

$result = $insMainModel->getUsuarios($datos);

$arreglo = array();
$data = array();

foreach ($result as $row) {				
    $data[] = array( 
        "users_id" => $row['users_id'],
        "colaborador" => $row['colaborador'],
        "correo" => $row['correo'],
        "tipo_usuario" => $row['tipo_usuario'],
        "privilegio" => $row['privilegio'],
        "estado" => $row['estado'],
        "empresa" => $row['empresa'],		
        "server_customers_id" => $row['server_customers_id']
    );			
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);