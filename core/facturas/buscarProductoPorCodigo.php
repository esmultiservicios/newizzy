<?php
// buscarProductoPorCodigo.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

if (isset($_POST['codigo'])) {
    $codigo = $mainModel->cleanString($_POST['codigo']);
    
    $query = "SELECT productos_id, nombre, precio_venta, isv_venta 
              FROM productos 
              WHERE (codigo_barras = ? OR productos_id = ?) AND estado = 1 
              LIMIT 1";
    
    $result = $mainModel->ejecutar_consulta_simple_preparada($query, "ss", [$codigo, $codigo]);
    
    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        echo json_encode(['success' => true, 'producto' => $producto]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Código no proporcionado']);
}