<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();
if (isset($_POST["barcode"])) {
    $searchText = $_POST["barcode"];

    // Obtener resultados
    $result = $insMainModel->getProductosLike($searchText);

    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();

        // Respuesta en formato JSON
        echo json_encode([
            "success" => true,
            "productos_id" => $producto["productos_id"],
            "tipo_producto_id" => $producto["tipo_producto_id"],
            "nombre" => $producto["nombre"] // Opcional, si lo necesitas
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Parámetro 'barcode' no recibido"]);
}