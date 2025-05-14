<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

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
    "tipo_compra_reporte" => $_POST['tipo_compra_reporte'],
    "fechai" => $_POST['fechai'],
    "fechaf" => $_POST['fechaf'],    
    "empresa_id_sd" => $_SESSION['empresa_id_sd'],        
];    

$result = $insMainModel->consultaComprasCompleta($datos);

$arreglo = array();
$data = [];
    
while($row = $result->fetch_assoc()){
    // Determinar el color basado en el estado de pago (todo en una sola consulta)
    $color = ($row['tipo_documento'] == 'Contado') ? 'bg-c-green' : 
             ($row['tiene_pagos'] > 0 ? 'bg-c-green' : 'bg-c-yellow');

    $data[] = array( 
        "compras_id"=>$row['compras_id'],
        "fecha"=>$row['fecha'],
        "tipo_documento"=>$row['tipo_documento'],
        "proveedor"=>$row['proveedor'],
        "numero"=>$row['numero'],
        "numero_ordenamiento"=>$row['numero_ordenamiento'],
        "subtotal"=>$row['subtotal'],    
        "isv"=>$row['isv'],    
        "descuento"=>$row['descuento'],
        "total"=>$row['total'],
        "color"=> $color, 
        "cuenta"=>$row['cuenta']          
    );        
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);