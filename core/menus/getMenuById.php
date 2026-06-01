<?php
// core/menus/getMenuById.php

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
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function responderGetMenuById($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizarTipoGetMenuById($tipo) {
    $tipo = strtolower(trim($tipo));

    if (!in_array($tipo, ["menu", "submenu", "submenu1"], true)) {
        return "";
    }

    return $tipo;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$tipo = isset($_POST['tipo']) ? normalizarTipoGetMenuById($_POST['tipo']) : "";

if ($id <= 0) {
    responderGetMenuById([
        "success" => false,
        "message" => "No se recibió un ID válido."
    ]);
}

if ($tipo === "") {
    responderGetMenuById([
        "success" => false,
        "message" => "No se recibió un tipo de menú válido."
    ]);
}

$conexion = null;
$stmt = null;

try {
    $conexion = $insMainModel->connection();

    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    if ($tipo === "menu") {
        $sql = "
            SELECT 
                menu_id AS id,
                name AS nombre,
                'menu' AS type,
                NULL AS dependency,
                icon,
                orden,
                descripcion,
                visible
            FROM menu
            WHERE menu_id = ?
            LIMIT 1
        ";
    } elseif ($tipo === "submenu") {
        $sql = "
            SELECT 
                submenu_id AS id,
                name AS nombre,
                'submenu' AS type,
                menu_id AS dependency,
                icon,
                orden,
                descripcion,
                visible
            FROM submenu
            WHERE submenu_id = ?
            LIMIT 1
        ";
    } else {
        $sql = "
            SELECT 
                submenu1_id AS id,
                name AS nombre,
                'submenu1' AS type,
                submenu_id AS dependency,
                icon,
                orden,
                descripcion,
                visible
            FROM submenu1
            WHERE submenu1_id = ?
            LIMIT 1
        ";
    }

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta: " . $conexion->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if (!$resultado || $resultado->num_rows <= 0) {
        responderGetMenuById([
            "success" => false,
            "message" => "No se encontró el elemento solicitado."
        ]);
    }

    $data = $resultado->fetch_assoc();

    $data["success"] = true;
    $data["id"] = (int)$data["id"];
    $data["dependency"] = $data["dependency"] !== null ? (int)$data["dependency"] : null;
    $data["orden"] = isset($data["orden"]) ? (int)$data["orden"] : 0;
    $data["visible"] = isset($data["visible"]) ? (int)$data["visible"] : 1;
    $data["icon"] = $data["icon"] ?? "";

    responderGetMenuById($data);

} catch (Exception $e) {
    responderGetMenuById([
        "success" => false,
        "message" => "Error en el servidor: " . $e->getMessage()
    ]);

} finally {
    if ($stmt) {
        $stmt->close();
    }

    if ($conexion) {
        $conexion->close();
    }
}