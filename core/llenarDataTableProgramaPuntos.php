<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciamos el modelo
$insMainModel = new mainModel();

// Obtenemos el valor del estado desde POST (si existe)
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';

// Comenzamos a construir la consulta SQL
$query = "SELECT * FROM programa_puntos";

// Si el estado no está vacío, aplicamos el filtro
if ($estado !== '') {
    $query .= " WHERE activo = ?";
}

// Ordenamos los resultados por fecha de creación
$query .= " ORDER BY fecha_creacion DESC";

// Preparamos la consulta
$stmt = $insMainModel->connection()->prepare($query);

// Si se necesita el parámetro de estado en la consulta
if ($estado !== '') {
    $stmt->bind_param('i', $estado); // 'i' es para un entero (activo 1 o 0)
}

// Ejecutamos la consulta
$stmt->execute();

// Obtenemos los resultados
$result = $stmt->get_result();

// Procesamos los resultados
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

// Construimos la respuesta en formato JSON
$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

// Enviamos la respuesta como JSON
echo json_encode($arreglo);