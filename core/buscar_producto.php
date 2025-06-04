<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

try {
    $insMainModel = new mainModel();
    
    if (!isset($_POST["barcode"])) {
        throw new Exception("Parámetro 'barcode' no recibido");
    }

    $searchText = $_POST["barcode"];
    $result = $insMainModel->getProductosLike($searchText);

    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        echo json_encode([
            "success" => true,
            "productos_id" => $producto["productos_id"],
            "tipo_producto_id" => $producto["tipo_producto_id"],
            "nombre" => $producto["nombre"]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    }
} catch (mysqli_sql_exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error de base de datos: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}