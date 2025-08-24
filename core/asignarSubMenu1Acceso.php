<?php
// asignarSubMenu1Acceso.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Siempre devolver JSON
header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

// Sanitizar y castear
$submenu1_id   = isset($_POST['submenu1_id']) ? (int)$_POST['submenu1_id'] : 0;
$privilegio_id = isset($_POST['privilegio_id']) ? (int)$_POST['privilegio_id'] : 0;
$estado        = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;
$fecha_registro = date("Y-m-d H:i:s");

// Validación básica
if ($submenu1_id <= 0 || $privilegio_id <= 0) {
    echo json_encode([
        'type'    => 'error',
        'title'   => 'Datos incompletos',
        'message' => 'Faltan parámetros requeridos.',
        'estado'  => false
    ]);
    exit;
}

// --- MODO SEGURO: si no tienes índice único (submenu1_id, privilegio_id),
// primero intenta actualizar; si no existe, inserta.

$existeSql = "
    SELECT acceso_submenu1_id
    FROM acceso_submenu1
    WHERE submenu1_id = {$submenu1_id}
      AND privilegio_id = {$privilegio_id}
    LIMIT 1
";
$existeRes = $mainModel->ejecutar_consulta_simple($existeSql);

if ($existeRes && $existeRes->num_rows > 0) {
    // UPDATE
    $updateSql = "
        UPDATE acceso_submenu1
        SET estado = {$estado},
            fecha_registro = '{$fecha_registro}'
        WHERE submenu1_id = {$submenu1_id}
          AND privilegio_id = {$privilegio_id}
    ";
    $ok = $mainModel->ejecutar_consulta_simple($updateSql);
    if ($ok === false) {
        echo json_encode([
            'type'    => 'error',
            'title'   => 'Error en base de datos',
            'message' => 'No se pudo actualizar el registro.',
            'estado'  => false
        ]);
        exit;
    }
} else {
    // INSERT
    $insertSql = "
        INSERT INTO acceso_submenu1 (submenu1_id, privilegio_id, estado, fecha_registro)
        VALUES ({$submenu1_id}, {$privilegio_id}, {$estado}, '{$fecha_registro}')
    ";
    $ok = $mainModel->ejecutar_consulta_simple($insertSql);
    if ($ok === false) {
        echo json_encode([
            'type'    => 'error',
            'title'   => 'Error en base de datos',
            'message' => 'No se pudo insertar el registro.',
            'estado'  => false
        ]);
        exit;
    }
}

echo json_encode([
    'type'    => 'success',
    'title'   => 'Operación exitosa',
    'message' => 'El registro se ha actualizado correctamente.',
    'estado'  => true
]);
exit;