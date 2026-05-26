<?php
// core/llenarDataTableProductos.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SD');
    session_start();
}

try {
    $insMainModel = new mainModel();

    $estado    = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
    $empresaId = $_SESSION['empresa_id_sd'] ?? null;

    $datos = [
        "estado"        => $estado,
        "empresa_id_sd" => $empresaId
    ];

    $result = $insMainModel->getProductos($datos);

    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "productos_id"     => $row['productos_id'],
                "image"            => $row['image'],
                "barCode"          => $row['barCode'],
                "nombre"           => $row['nombre'],
                "descripcion"      => $row['descripcion'],
                "medida"           => $row['medida'],
                "categoria"        => $row['categoria'],
                "precio_compra"    => (float)$row['precio_compra'],
                "precio_venta"     => (float)$row['precio_venta'],
                "isv_venta"        => $row['isv_venta'],   // "Si"/"No" (texto, se usa para mostrar)
                "isv_compra"       => $row['isv_compra'],
                "porcentaje_venta" => (float)$row['porcentaje_venta'],
                "estado"           => (int)$row['estado'],

                // ---- NUEVO: valores crudos para switches del modal (0/1)
                "restaurante"      => (int)$row['restaurante'],
                "isv1"             => (int)$row['isv1'],   // 15%
                "isv2"             => (int)$row['isv2']    // 18%
            ];
        }
    }

    echo json_encode([
        "echo"                 => 1,
        "totalrecords"         => count($data),
        "totaldisplayrecords"  => count($data),
        "data"                 => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error"   => true,
        "message" => "Error cargando productos",
        "detail"  => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}