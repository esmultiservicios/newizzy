<?php
// core/menus/getDependenciesMenu.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            "data" => [],
            "success" => false,
            "message" => $validacion['mensaje'] ?? "Sesión inválida"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function responderDependenciesMenu($data, $success = true, $message = "") {
    echo json_encode([
        "data" => $data,
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$tipo = isset($_POST['tipo']) ? trim((string)$_POST['tipo']) : "";
$menu_id = isset($_POST['menu_id']) ? (int)$_POST['menu_id'] : 0;

$tiposPermitidos = [
    "getMenus",
    "getAllSubmenus",
    "getSubmenusByMenu"
];

if (!in_array($tipo, $tiposPermitidos, true)) {
    responderDependenciesMenu([], false, "Tipo de dependencia no válido.");
}

$conexion = null;
$stmt = null;
$data = [];

try {
    $conexion = $insMainModel->connection();

    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    if ($tipo === "getMenus") {
        $sql = "
            SELECT 
                menu_id AS id,
                name AS nombre,
                descripcion
            FROM menu
            ORDER BY name ASC
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la consulta de menús: " . $conexion->error);
        }

    } elseif ($tipo === "getAllSubmenus") {
        $sql = "
            SELECT 
                submenu_id AS id,
                name AS nombre,
                descripcion
            FROM submenu
            ORDER BY name ASC
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la consulta de submenús: " . $conexion->error);
        }

    } else {
        if ($menu_id <= 0) {
            responderDependenciesMenu([], false, "No se recibió un menú válido.");
        }

        $sql = "
            SELECT 
                submenu_id AS id,
                name AS nombre,
                descripcion
            FROM submenu
            WHERE menu_id = ?
            ORDER BY name ASC
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la consulta de submenús por menú: " . $conexion->error);
        }

        $stmt->bind_param("i", $menu_id);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $data[] = [
                "id" => (int)$row["id"],
                "nombre" => $row["nombre"],
                "descripcion" => $row["descripcion"]
            ];
        }
    }

    responderDependenciesMenu($data, true, "");

} catch (Exception $e) {
    responderDependenciesMenu([], false, "Error en el servidor: " . $e->getMessage());

} finally {
    if ($stmt) {
        $stmt->close();
    }

    if ($conexion) {
        $conexion->close();
    }
}