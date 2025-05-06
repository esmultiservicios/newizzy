<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Validar sesión
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    echo json_encode([
        "error" => true,
        "mensaje" => $validacion['mensaje'],
        "redireccion" => $validacion['redireccion']
    ]);
    exit();
}

// Obtener el estado enviado desde JS (1: activo, 0: inactivo)
$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

// Consulta principal con filtro por estado
$query = "SELECT privilegio_id, nombre, estado FROM privilegio WHERE estado = '$estado'";
$result = $insMainModel->ejecutar_consulta_simple($query);

$data = [];

while ($row = $result->fetch_assoc()) {
    $privilegioActual = $row['privilegio_id'];

    // Contar accesos (menús, submenús, etc.)
    $queryCounts = "
    SELECT 
        (SELECT COUNT(DISTINCT m.menu_id) FROM acceso_menu am
            INNER JOIN menu m ON am.menu_id = m.menu_id
            WHERE am.privilegio_id = '$privilegioActual' AND am.estado = 1) AS menus,
        
        (SELECT COUNT(DISTINCT sm.submenu_id) FROM acceso_submenu asm
            INNER JOIN submenu sm ON asm.submenu_id = sm.submenu_id
            WHERE asm.privilegio_id = '$privilegioActual' AND asm.estado = 1) AS submenus,
        
        (SELECT COUNT(DISTINCT sm1.submenu1_id) FROM acceso_submenu1 assm1
            INNER JOIN submenu1 sm1 ON assm1.submenu1_id = sm1.submenu1_id
            WHERE assm1.privilegio_id = '$privilegioActual' AND assm1.estado = 1) AS submenus1";

    $countResult = $insMainModel->ejecutar_consulta_simple($queryCounts);

    $menus = 0;
    $submenus = 0;
    $submenus1 = 0;

    if ($countResult && $countResult->num_rows > 0) {
        $countRow = $countResult->fetch_assoc();
        $menus = $countRow['menus'];
        $submenus = $countRow['submenus'];
        $submenus1 = $countRow['submenus1'];
    }

    $data[] = [
        "privilegio_id" => $row['privilegio_id'],
        "planes_id" => $row['privilegio_id'], // Campo usado en los botones
        "nombre" => $row['nombre'],
        "estado" => $row['estado'],
        "menus_asignados" => $menus,
        "submenus_asignados" => $submenus,
        "submenus1_asignados" => $submenus1
    ];
}

$arreglo = [
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
];

echo json_encode($arreglo);