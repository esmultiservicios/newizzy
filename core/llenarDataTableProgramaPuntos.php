<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$estado = isset($_POST['estado']) ? $_POST['estado'] : '';
$query = "SELECT * FROM programa_puntos";

// Si hay filtro
if ($estado !== '') {
    $query .= " WHERE activo = " . intval($estado);
}

$query .= " ORDER BY fecha_creacion DESC";

$result = $insMainModel->ejecutar_consulta($query);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = array(
        "id" => $row['id'],
        "nombre" => $row['nombre'],
        "tipo_calculo" => $row['tipo_calculo'],
        "monto" => $row['monto'],
        "porcentaje" => $row['porcentaje'],
        "activo" => $row['activo'],
        "fecha_creacion" => $row['fecha_creacion']
    );
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);
