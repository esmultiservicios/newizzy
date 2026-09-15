<?php
$peticionAjax = true;

header('Content-Type: application/json; charset=utf-8');

ob_start();

try {
    require_once "configGenerales.php";
    require_once "mainModel.php";

    $insMainModel = new mainModel();
    $conexion = $insMainModel->connection();

    $sql = "
        SELECT
            isv_id,
            isv_tipo_id,
            valor,
            activar,
            fecha_registro,
            CASE
                WHEN isv_tipo_id = 1 THEN 'Factura'
                WHEN isv_tipo_id = 2 THEN 'Compra'
                ELSE CONCAT('Tipo ', isv_tipo_id)
            END AS tipo_isv_nombre
        FROM isv
        ORDER BY isv_tipo_id ASC, valor ASC, isv_id ASC
    ";

    $result = $conexion->query($sql);

    if ($result === false) {
        throw new Exception('No se pudo consultar la tabla isv: ' . $conexion->error);
    }

    $data = array();

    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            "isv_id"          => (int)$row['isv_id'],
            "isv_tipo_id"     => (int)$row['isv_tipo_id'],
            "tipo_isv_nombre" => (string)$row['tipo_isv_nombre'],
            "valor"           => (float)$row['valor'],
            "activar"         => (int)$row['activar'],
            "fecha_registro"  => (string)$row['fecha_registro']
        );
    }

    $result->free();
    $conexion->close();

    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode(
        array(
            "success"             => true,
            "echo"                => 1,
            "totalrecords"        => count($data),
            "totaldisplayrecords" => count($data),
            "data"                => $data
        ),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code(500);

    error_log('llenarDataTableConfImpuestos.php: ' . $e->getMessage());

    echo json_encode(
        array(
            "success" => false,
            "message" => "No se pudieron cargar los impuestos configurados.",
            "data"    => array()
        ),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
}
