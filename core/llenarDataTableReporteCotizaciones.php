<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

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
    "tipo_cotizacion_reporte" => $_POST['tipo_cotizacion_reporte'],
    "fechai" => $_POST['fechai'],
    "fechaf" => $_POST['fechaf'],        
    "empresa_id_sd" => $_SESSION['empresa_id_sd'],
];    

$result = $insMainModel->consultaCotizacionesReporte($datos);

$arreglo = array();
$data = [];
    
while($row = $result->fetch_assoc()){
    $data[] = array( 
        "cotizacion_id"=>$row['cotizacion_id'],
        "fecha"=>$row['fecha'],
        "tipo_documento"=>$row['tipo_documento'],
        "cliente"=>$row['cliente'],
        "numero"=>$row['numero'],
        "numero_ordenamiento"=>$row['numero_ordenamiento'], // Campo para ordenamiento
        "subtotal"=>$row['subtotal'],    
        "isv"=>$row['isv'],    
        "descuento"=>$row['descuento'],
        "total"=>$row['total']        
    );        
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);