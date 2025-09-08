<?php
// core/llenarDataTableProductos.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Asegura la sesión SIEMPRE con el mismo nombre
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SD');
    session_start();
}

try {
    $insMainModel = new mainModel();

    // Entrada segura
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
    $empresaId = $_SESSION['empresa_id_sd'] ?? null;

    // --- OJO: tu getProductos original NO usa empresa. Si necesitas filtrar por empresa,
    // agrega la condición en la consulta. Aquí dejamos el mismo comportamiento que tenías.
    $datos = [
        "estado" => $estado,
        "empresa_id_sd" => $empresaId
    ];

    $result = $insMainModel->getProductos($datos);

    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Si no usas saldo, puedes quitar este bloque para ganar performance
            $saldo_productos = 0;
            $result_mov = $insMainModel->getSaldoProductosMovimientos($row['productos_id']);
            if ($result_mov && $result_mov->num_rows > 0) {
                $consulta = $result_mov->fetch_assoc();
                $saldo_productos = (float)$consulta['saldo'];
            }

            $data[] = [
                "productos_id"     => $row['productos_id'],
                "image"            => $row['image'],
                "barCode"          => $row['barCode'],
                "nombre"           => $row['nombre'],
                "medida"           => $row['medida'],
                "categoria"        => $row['categoria'],
                "precio_compra"    => (float)$row['precio_compra'],
                "precio_venta"     => (float)$row['precio_venta'],
                "isv_venta"        => $row['isv_venta'],
                "isv_compra"       => $row['isv_compra'],
                "porcentaje_venta" => (float)$row['porcentaje_venta'],
                "estado"           => (int)$row['estado'],
                // "saldo"         => $saldo_productos,  // si lo quieres mostrar
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
    // Nunca envíes HTML; responde JSON controlado
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Error cargando productos",
        "detail" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
