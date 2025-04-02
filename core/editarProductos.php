<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$productos_id  = $_POST['productos_id'];
	$result = $insMainModel->getTipoProductosEdit($productos_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['almacen_id'],
		1 => $valores2['medida_id'],
		2 => $valores2['nombre'],
		3 => $valores2['descripcion'],
		4 => $valores2['precio_compra'],
		5 => $valores2['precio_venta'],
		6 => $valores2['tipo_producto_id'],
		7 => $valores2['isv_venta'],	
		8 => $valores2['isv_compra'],	
		9 => $valores2['estado'],	
		10 => $valores2['file_name'],	
		11 => $valores2['empresa_id'],	
		12 => $valores2['tipo_producto'],
		13 => $valores2['porcentaje_venta'],	
		14 => $valores2['cantidad_minima'],	
		15 => $valores2['cantidad_maxima'],	
		16 => $valores2['categoria_id'],
		17 => $valores2['precio_mayoreo'],	
		18 => $valores2['cantidad_mayoreo'],
		19 => $valores2['barCode'],	
		20 => $valores2['id_producto_superior'],
		21 => SERVERURL."vistas/plantilla/img/products/".$valores2['file_name']
	);
	echo json_encode($datos);
?>	