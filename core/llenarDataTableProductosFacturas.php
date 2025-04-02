<?php	
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

if (!isset($_SESSION['user_sd'])) {
    session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

$bodega = isset($_POST['bodega']) ? $_POST['bodega'] : '';

$datos = [
    "bodega" => $bodega,
    "barcode" => '',
    "planes_id" => $_SESSION['planes_id'],
    "empresa_id_sd" => $_SESSION['empresa_id_sd']
];

$result = $insMainModel->getProductosConInventarioYServicios($datos);

$arreglo = array();
$data = array();

while ($row = $result->fetch_assoc()) {
    $bodegaNombre = ($row['almacen_id'] == 0 || $row['almacen_id'] == null) ? "Sin bodega" : $row['almacen'];
    $cantidad = ($row['cantidad'] == null || $row['cantidad'] == "") ? 0 : $row['cantidad'];

    $data[] = array(
        "productos_id" => $row['productos_id'],
        "barCode" => $row['barCode'],
        "nombre" => $row['nombre'],
        "cantidad" => $cantidad,
        "medida" => $row['medida'],
        "tipo_producto_id" => $row['tipo_producto_id'],
        "precio_venta" => $row['precio_venta'],
        "almacen" => $bodegaNombre,
        "almacen_id" => $row['almacen_id'],
        "tipo_producto" => $row['tipo_producto'],
        "impuesto_venta" => $row['impuesto_venta'],
        "precio_mayoreo" => $row['precio_mayoreo'],
        "cantidad_mayoreo" => $row['cantidad_mayoreo'],
        "tipo_producto_nombre" => $row['tipo_producto_nombre'],
        "isv_venta" => $row['isv_venta'],
        "isv_compra" => $row['isv_compra'],
        "image" => $row['image'],
        "id_producto_superior" => $row['id_producto_superior']
    );
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);


/* 	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();

	$bodega = '';

	if(isset($_POST['bodega'])){
		$bodega = $_POST['bodega'];
	}

	$datos = [
		"bodega" => $bodega,
		"barcode" => '',
		"planes_id" => $_SESSION['planes_id'],	
		"empresa_id_sd" => $_SESSION['empresa_id_sd']		
	];
	
	$result = $insMainModel->getProductosCantidad($datos);
	
	$arreglo = array();
	$data = array();
	
	$entradaH = 0;
	$salidaH = 0;
	$cantidad = 0;
	
	while($row = $result->fetch_assoc()){	
		$result_productos = $insMainModel->getCantidadProductos($row['productos_id']);	
		if($result_productos->num_rows>0){
			while($consulta = $result_productos->fetch_assoc()){
				if($row['almacen_id'] == 0 || $row['almacen_id'] == null){
					$bodega = "Sin bodega";
				}else{
					$bodega = $row['almacen'];
				}

				if($row['cantidad'] == null || $row['cantidad'] == ""){
					$cantidad = 0;
				}else{
					$cantidad = $row['cantidad'];
				}

				$data[] = array( 
					"productos_id"=>$row['productos_id'],
					"barCode"=>$row['barCode'],
					"nombre"=>$row['nombre'],
					"cantidad"=>$cantidad,
					"medida"=>$row['medida'],
					"tipo_producto_id"=>$row['tipo_producto_id'],
					"precio_venta"=>$row['precio_venta'],
					"almacen"=>$bodega,
					"almacen_id"=>$row['almacen_id'],
					"tipo_producto"=>$row['tipo_producto'],
					"impuesto_venta"=>$row['impuesto_venta'],
					"precio_mayoreo"=>$row['precio_mayoreo'],
					"cantidad_mayoreo"=>$row['cantidad_mayoreo'],
					"tipo_producto_nombre"=>$row['tipo_producto_nombre'],
					"isv_venta"=>$row['isv_venta'],
					"isv_compra"=>$row['isv_compra'],
					"image"=>$row['image']
				);
			}
		}			
	}
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo); */