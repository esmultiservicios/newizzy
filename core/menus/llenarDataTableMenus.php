<?php
// core/menus/llenarDataTableMenus.php

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

function responderLlenarDataTableMenus($data, $success = true, $message = "") {
    echo json_encode([
        "data" => $data,
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conexion = null;
$stmt = null;
$data = [];

try {
    $conexion = $insMainModel->connection();

    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    /*
        Este query no recibe datos del usuario.
        Por eso no hay riesgo de inyección SQL aquí.
        Aun así usamos prepare() para mantener el estándar.
    */
    $sql = "
        SELECT 
            m.menu_id AS id,
            m.name AS name,
            m.icon,
            m.orden,
            m.descripcion,
            m.visible,
            'Menú Principal' AS type,
            'Sin dependencia' AS dependency
        FROM menu m

        UNION ALL

        SELECT 
            s.submenu_id AS id,
            s.name AS name,
            s.icon,
            s.orden,
            s.descripcion,
            s.visible,
            'Submenú Nivel 1' AS type,
            m.descripcion AS dependency
        FROM submenu s
        INNER JOIN menu m ON s.menu_id = m.menu_id

        UNION ALL

        SELECT 
            s1.submenu1_id AS id,
            s1.name AS name,
            s1.icon,
            s1.orden,
            s1.descripcion,
            s1.visible,
            'Submenú Nivel 2' AS type,
            s.descripcion AS dependency
        FROM submenu1 s1
        INNER JOIN submenu s ON s1.submenu_id = s.submenu_id

        ORDER BY type ASC, name ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta: " . $conexion->error);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $data[] = [
                "id" => (int)$row["id"],
                "name" => $row["name"],
                "icon" => $row["icon"],
                "orden" => (int)$row["orden"],
                "descripcion" => $row["descripcion"],
                "visible" => (int)$row["visible"],
                "type" => $row["type"],
                "dependency" => $row["dependency"]
            ];
        }
    }

    responderLlenarDataTableMenus($data, true, "");

} catch (Exception $e) {
    responderLlenarDataTableMenus([], false, "Error en el servidor: " . $e->getMessage());

} finally {
    if ($stmt) {
        $stmt->close();
    }

    if ($conexion) {
        $conexion->close();
    }
}