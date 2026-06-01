<?php
// core/correo/llenarDataTableConfCorreos.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            "data" => []
        ]);
        exit;
    }
}

function enmascararValorCorreoTabla($valor) {
    $valor = trim((string)$valor);

    if ($valor === '') {
        return '';
    }

    $longitud = strlen($valor);

    if ($longitud <= 8) {
        return substr($valor, 0, 2) . '****' . substr($valor, -2);
    }

    if (strpos($valor, '-') !== false) {
        $partes = explode('-', $valor);

        if (count($partes) >= 5) {
            return substr($partes[0], 0, 4) . '****-****-****-****-****' . substr($partes[count($partes) - 1], -4);
        }
    }

    return substr($valor, 0, 4) . '****' . substr($valor, -4);
}

$sql = "
    SELECT
        c.correo_id,
        c.correo_tipo_id,
        ct.nombre AS tipo_correo,
        c.metodo_envio,
        c.server,
        c.correo,
        c.port,
        c.smtp_secure,
        c.tenant_id,
        c.client_id,
        c.graph_user,
        c.save_to_sent_items,
        c.estado
    FROM correo c
    INNER JOIN correo_tipo ct ON c.correo_tipo_id = ct.correo_tipo_id
    ORDER BY c.correo_tipo_id ASC
";

$resultado = $insMainModel->ejecutar_consulta_simple($sql);

$data = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $data[] = [
            "correo_id" => $row["correo_id"],
            "correo_tipo_id" => $row["correo_tipo_id"],
            "tipo_correo" => $row["tipo_correo"],
            "metodo_envio" => $row["metodo_envio"],
            "server" => $row["server"],
            "correo" => $row["correo"],
            "port" => $row["port"],
            "smtp_secure" => $row["smtp_secure"],

            /*
                Se muestran parciales para todos.
            */
            "tenant_id" => enmascararValorCorreoTabla($row["tenant_id"]),
            "client_id" => enmascararValorCorreoTabla($row["client_id"]),

            "graph_user" => $row["graph_user"],
            "save_to_sent_items" => $row["save_to_sent_items"],
            "estado" => $row["estado"]
        ];
    }
}

echo json_encode([
    "data" => $data
], JSON_UNESCAPED_UNICODE);