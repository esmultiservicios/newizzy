<?php
// core/correo/editarCorreo.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            "success" => false,
            "message" => $validacion['mensaje'] ?? "Sesión inválida"
        ]);
        exit;
    }
}

function usuarioPuedeEditarCorreoConfiguracion() {
    $privilegio = isset($_SESSION['privilegio_sd']) ? (int)$_SESSION['privilegio_sd'] : 0;

    /*
        1 = Super Administrador
        2 = Administrador
        3 = Reseller
        4 = Clientes
        5 = Contabilidad

        Solo Super Administrador y Administrador pueden modificar configuración de correo.
    */
    return in_array($privilegio, [1, 2], true);
}

function enmascararValorCorreo($valor) {
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

$correo_id = isset($_POST['correo_id']) ? (int)$_POST['correo_id'] : 0;

if ($correo_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió el ID de correo."
    ]);
    exit;
}

$sql = "
    SELECT
        correo_id,
        correo_tipo_id,
        metodo_envio,
        server,
        correo,
        port,
        smtp_secure,
        tenant_id,
        client_id,
        graph_user,
        save_to_sent_items,
        estado
    FROM correo
    WHERE correo_id = '$correo_id'
    LIMIT 1
";

$resultado = $insMainModel->ejecutar_consulta_simple($sql);

if (!$resultado || $resultado->num_rows <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró la configuración de correo."
    ]);
    exit;
}

$row = $resultado->fetch_assoc();

$puede_editar = usuarioPuedeEditarCorreoConfiguracion();

echo json_encode([
    "success" => true,
    "puede_editar" => $puede_editar,

    "correo_id" => $row['correo_id'],
    "correo_tipo_id" => $row['correo_tipo_id'],
    "metodo_envio" => $row['metodo_envio'],
    "server" => $row['server'],
    "correo" => $row['correo'],
    "port" => $row['port'],
    "smtp_secure" => $row['smtp_secure'],

    /*
        Por seguridad:
        Tenant ID y Client ID se devuelven enmascarados para todos.
        Si un admin quiere cambiarlos, debe escribir el valor completo nuevo.
        Si los deja enmascarados, el backend conserva el valor real.
    */
    "tenant_id" => enmascararValorCorreo($row['tenant_id']),
    "client_id" => enmascararValorCorreo($row['client_id']),

    /*
        El Client Secret nunca se devuelve.
    */
    "client_secret" => "",
    "client_secret_texto" => "Guardado de forma segura",

    "graph_user" => $row['graph_user'],
    "save_to_sent_items" => $row['save_to_sent_items'],
    "estado" => $row['estado']
], JSON_UNESCAPED_UNICODE);