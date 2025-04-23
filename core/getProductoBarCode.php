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

$data = [
    "barcode" => $_POST['barcode'],
    "empresa_id_sd" => $_SESSION['empresa_id_sd'],
    "bodega" => ''  // Suponiendo que 'bodega' es parte de los datos de la solicitud
];

$datos = array(); // Inicializamos $datos como un array vacío

// Obtener los datos del producto por el código de barras
$resultCantidad = $insMainModel->getProductosCantidad($data);

if($resultCantidad->num_rows > 0) {
    $row = $resultCantidad->fetch_assoc();
    
    // Verificar si el producto tiene un lote asociado (usando lote_id)
    $lote_id = isset($row['lote_id']) ? $row['lote_id'] : null;  // Asegúrate de que 'lote_id' esté presente en los resultados

    // Llamar a la función correspondiente según si el producto tiene un lote o no
    if ($lote_id) {
        // Si tiene lote, obtener el saldo por lote
        $saldoLote = $insMainModel->getSaldoPorLote($row['productos_id'], $lote_id);
        $saldo = $saldoLote ? $saldoLote['saldo'] : 0;
    } else {
        // Si no tiene lote, obtener el saldo normal
        $saldo = $insMainModel->getSaldoProductosMovimientos($row['productos_id']);
    }

    // Rellenar los datos con la información del producto y el saldo
    $datos = array(
        0 => $row['nombre'],
        1 => $row['precio_venta'],
        2 => $row['productos_id'],
        3 => $row['impuesto_venta'],
        4 => $row['cantidad_mayoreo'],    
        5 => $row['precio_mayoreo'],
        6 => $saldo,  // Usar el saldo calculado
        7 => $row['almacen_id'],
        8 => $row['medida'],
        9 => $row['tipo_producto_id'],
        10 => $row['precio_compra']
    );
}

echo json_encode($datos);



/*
while($row = $resultCantidad->fetch_assoc()){	
    echo "El Producto ID es: ".$row['productos_id'];
    $result_productos = $insMainModel->getCantidadProductos($row['productos_id']);	

    //ES UN PRODUCTO PADRE
    if($result_productos->num_rows>0){
        while($consulta = $result_productos->fetch_assoc()){
            $id_producto_superior = intval($consulta['id_producto_superior']);
            if($id_producto_superior != 0 || $id_producto_superior != 'null'){
                $datosH = [
                    "tipo_producto_id" => "",
                    "productos_id" => $id_producto_superior,
                    "empresa_id_sd" => $_SESSION['empresa_id_sd'],
                    "bodega" => $row['almacen_id'],	
                ];

                //agregos el producto hijo y las cantidades del padre
                $resultPadre = $insMainModel->getTranferenciaProductos($datosH);
                if($resultPadre->num_rows>0){
                    $rowP = $resultPadre->fetch_assoc();

                    $entradaH = 0;
                    $salidaH = 0;
                    $medidaName = strtolower($row['medida']);
                    if($medidaName == "ton"){ // Medida en Toneladas
                        $entradaH = $rowP['entrada'] / 2205;
                        $salidaH = $rowP['salida'] / 2205;
                    }

                    $datos = array(
                        0 => $row['nombre'],
                        1 => $row['precio_venta'],
                        2 => $row['productos_id'],
                        3 => $row['impuesto_venta'],
                        4 => $row['cantidad_mayoreo'],	
                        5 => $row['precio_mayoreo'],
                        6 => number_format($entradaH - $salidaH, 2),
                        7 => $row['almacen_id'],
                        8 => $row['medida'],
                        9 => $row['tipo_producto_id'],
                        10 => $row['precio_compra']
                    );	
                }
            } else {
                $datos = array(
                    0 => $row['nombre'],
                    1 => $row['precio_venta'],
                    2 => $row['productos_id'],
                    3 => $row['impuesto_venta'],
                    4 => $row['cantidad_mayoreo'],	
                    5 => $row['precio_mayoreo'],
                    6 => $row['cantidad'],
                    7 => $row['almacen_id'],
                    8 => $row['medida'],
                    9 => $row['tipo_producto_id'],
                    10 => $row['precio_compra']
                );
            }
        }
    }			
}*/