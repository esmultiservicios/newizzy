<?php
//llenarDataTablePrivilegio.php
$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Validar sesión
$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
    echo json_encode([
        "error" => true,
        "mensaje" => $validacion['mensaje'],
        "redireccion" => $validacion['redireccion'],
        "data" => []
    ]);
    exit();
}

/*
    =========================================================
    VALIDAR BASE PRINCIPAL
    =========================================================

    DB_MAIN ya está definido en configAPP.php:
    define('DB_MAIN', 'esmultiservicios_izzy');

    Regla:
    - Si la base actual es DB_MAIN, muestra todos los privilegios.
    - Si la base actual NO es DB_MAIN, oculta privilegio_id = 1.
*/
function obtenerBaseActualPrivilegios($insMainModel) {
    $dbActual = "";

    $sql = "SELECT DATABASE() AS db_actual";
    $result = $insMainModel->ejecutar_consulta_simple($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $dbActual = isset($row['db_actual']) ? trim($row['db_actual']) : "";
    }

    return $dbActual;
}

function esBasePrincipalPrivilegios($insMainModel) {
    if (!defined('DB_MAIN')) {
        return false;
    }

    $dbActual = obtenerBaseActualPrivilegios($insMainModel);
    $dbMain = trim(DB_MAIN);

    if ($dbActual === "" || $dbMain === "") {
        return false;
    }

    return strtolower($dbActual) === strtolower($dbMain);
}

$es_base_principal = esBasePrincipalPrivilegios($insMainModel);

// Obtener el estado enviado desde JS
// 1: activo, 0: inactivo
$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? (int)$_POST['estado'] : 1;

// WHERE principal
$where = "p.estado = '$estado'";

// Si NO es base principal, ocultar Super Administrador
if (!$es_base_principal) {
    $where .= " AND p.privilegio_id <> 1";
}

// Consulta principal con filtro por estado y seguridad de Super Administrador
$query = "
    SELECT 
        p.privilegio_id, 
        p.nombre, 
        p.estado 
    FROM privilegio p
    WHERE $where
    ORDER BY p.privilegio_id ASC
";

$result = $insMainModel->ejecutar_consulta_simple($query);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $privilegioActual = (int)$row['privilegio_id'];

        $queryCounts = "
            SELECT 
                (
                    SELECT COUNT(DISTINCT m.menu_id) 
                    FROM acceso_menu am
                    INNER JOIN menu m 
                        ON am.menu_id = m.menu_id
                    WHERE am.privilegio_id = '$privilegioActual' 
                      AND am.estado = 1
                ) AS menus,
                
                (
                    SELECT COUNT(DISTINCT sm.submenu_id) 
                    FROM acceso_submenu asm
                    INNER JOIN submenu sm 
                        ON asm.submenu_id = sm.submenu_id
                    WHERE asm.privilegio_id = '$privilegioActual' 
                      AND asm.estado = 1
                ) AS submenus,
                
                (
                    SELECT COUNT(DISTINCT sm1.submenu1_id) 
                    FROM acceso_submenu1 assm1
                    INNER JOIN submenu1 sm1 
                        ON assm1.submenu1_id = sm1.submenu1_id
                    WHERE assm1.privilegio_id = '$privilegioActual' 
                      AND assm1.estado = 1
                ) AS submenus1
        ";

        $countResult = $insMainModel->ejecutar_consulta_simple($queryCounts);

        $menus = 0;
        $submenus = 0;
        $submenus1 = 0;

        if ($countResult && $countResult->num_rows > 0) {
            $countRow = $countResult->fetch_assoc();

            $menus = isset($countRow['menus']) ? (int)$countRow['menus'] : 0;
            $submenus = isset($countRow['submenus']) ? (int)$countRow['submenus'] : 0;
            $submenus1 = isset($countRow['submenus1']) ? (int)$countRow['submenus1'] : 0;
        }

        $data[] = [
            "privilegio_id" => $privilegioActual,
            "planes_id" => $privilegioActual,
            "nombre" => $row['nombre'],
            "estado" => (int)$row['estado'],
            "menus_asignados" => $menus,
            "submenus_asignados" => $submenus,
            "submenus1_asignados" => $submenus1
        ];
    }
}

$arreglo = [
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
];

echo json_encode($arreglo);