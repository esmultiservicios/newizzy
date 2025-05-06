<?php    
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Validar y limpiar los datos de entrada
$estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
$fechai = $insMainModel->cleanString($_POST['fechai']);
$fechaf = $insMainModel->cleanString($_POST['fechaf']);        

$datos = [
    "estado" => $estado,
    "fechai" => $fechai,
    "fechaf" => $fechaf,        
];        

$result = $insMainModel->getIngresosContables($datos);

$arreglo = array();
$data = array();

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){                
        $data[] = array( 
            "ingresos_id"=>$row['ingresos_id'],
            "fecha_registro"=>$row['fecha_registro'],
            "fecha"=>$row['fecha'],
            "nombre"=>$row['nombre'],
            "cliente"=>$row['cliente'],
            "factura"=>$row['factura'],
            "subtotal"=>$row['subtotal'], // Quitamos el 'L.' para que DataTables pueda ordenar correctamente
            "impuesto"=>$row['impuesto'],
            "descuento"=>$row['descuento'],
            "nc"=>$row['nc'],
            "total"=>$row['total'],
            "recibide"=>$row['recibide'],
            "tipo_ingreso"=>$row['tipo_ingreso'],
            "observacion"=>$row['observacion'],
            "estado"=>$row['estado']
        );    
    }
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);