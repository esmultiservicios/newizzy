<?php
// core/facturas/getISVValor.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$isv_id = isset($_POST['isv_id']) ? intval($_POST['isv_id']) : 0;
// Solo aceptamos 1 o 2
if ($isv_id !== 1 && $isv_id !== 2) {
    echo json_encode([
        "id"      => 0,
        "valor"   => 0,
        "activar" => 0
    ]);
    exit;
}

$query  = "SELECT isv_id AS id, valor, activar FROM isv WHERE isv_id = $isv_id LIMIT 1";
$result = $mainModel->ejecutar_consulta_simple($query);

$out = [
    "id"      => 0,
    "valor"   => 0,
    "activar" => 0
];

if ($result && $result->num_rows > 0) {
    $row        = $result->fetch_assoc();
    $out["id"]      = isset($row["id"])      ? (int)$row["id"]      : 0;
    $out["valor"]   = isset($row["valor"])   ? (float)$row["valor"] : 0;
    $out["activar"] = isset($row["activar"]) ? (int)$row["activar"] : 0;
}

echo json_encode($out);