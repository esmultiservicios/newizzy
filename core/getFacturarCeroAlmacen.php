<?php	
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

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

$almacen_id = $_POST['almacen_id'];	
$result = $insMainModel->getAlmacenId($almacen_id);
$estado = false;

if($result->num_rows>0){
    $res = $result->fetch_assoc();
    // Cambiar esta evaluación para que sea explícita
    $estado = ($res['facturar_cero'] == 1); // Comparar con 1 explícitamente
}		

// Devolver como JSON con estructura clara
echo json_encode([
    'success' => true,
    'facturar_cero' => $estado
]);