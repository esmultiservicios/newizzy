<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

$datos = [
    "estado" => $estado,
    "proveedores_id" => $_POST['proveedores_id'],        
    "fechai" => $_POST['fechai'],
    "fechaf" => $_POST['fechaf'],        
];    

$result = $insMainModel->consultaCuentasPorPagarCompleta($datos);

$arreglo = array();
$data = array();
$totalCredito = 0;
$totalAbono = 0;
$totalPendiente = 0;

while($row = $result->fetch_assoc()){
    $credito = $row['importe'];
    $abono = $row['abono'] ?? 0.00;
    $saldo = $row['importe'] - $abono;

    $totalCredito += $credito;
    $totalAbono += $abono;
    $totalPendiente += $saldo;
                
    $estadoColor = ($row['estado'] == 2) ? 'bg-c-green' : 'bg-warning';

    $data[] = array( 
        "compras_id"=>$row['compras_id'],
        "fecha"=>$row['fecha'],
        "proveedores"=>$row['proveedores'],
        "factura"=>$row['factura'],
        "numero_ordenamiento"=>$row['numero_ordenamiento'],
        "tipo_compra"=>$row['tipo_compra'],
        "credito"=>$credito,
        "abono"=>$abono,            
        "saldo"=>$saldo,
        "color"=> $estadoColor,
        "estado"=>$row['estado'],
        "total_credito"=> number_format($totalCredito,2),
        "total_abono"=>number_format($totalAbono,2),
        "total_pendiente"=> number_format($totalPendiente,2)          
    );        
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);