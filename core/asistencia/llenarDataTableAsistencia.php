<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Obtener parámetros del filtro
$estado = isset($_POST['estado']) ? intval($_POST['estado']) : null;
$colaborador_id = isset($_POST['colaborador']) ? intval($_POST['colaborador']) : null;
$fecha_inicio = isset($_POST['fechai']) ? $_POST['fechai'] : null;
$fecha_fin = isset($_POST['fechaf']) ? $_POST['fechaf'] : null;

// Construir la consulta SQL
$query = "SELECT a.asistencia_id, c.nombre as colaborador, 
          a.fecha, a.horai, a.horaf, a.comentario, a.estado
          FROM asistencia a
          JOIN colaboradores c ON a.colaboradores_id = c.colaboradores_id
          WHERE 1=1";

$params = [];
$types = "";

// Aplicar filtros
if ($estado !== null) {
    $query .= " AND a.estado = ?";
    $params[] = $estado;
    $types .= "i";
}

if ($colaborador_id) {
    $query .= " AND a.colaboradores_id = ?";
    $params[] = $colaborador_id;
    $types .= "i";
}

if ($fecha_inicio && $fecha_fin) {
    $query .= " AND a.fecha BETWEEN ? AND ?";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
    $types .= "ss";
}

$query .= " ORDER BY a.fecha DESC, a.horai DESC";

// Ejecutar consulta
$result = $mainModel->ejecutar_consulta_simple_preparada($query, $types, $params);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Calcular horas trabajadas
        $horas_trabajadas = "N/A";
        $horas_decimal = 0;
        
        if (!empty($row['horai']) && !empty($row['horaf'])) {
            $entrada = new DateTime($row['horai']);
            $salida = new DateTime($row['horaf']);
            
            // Calcular diferencia
            $diferencia = $entrada->diff($salida);
            
            // Formatear como HH:MM
            $horas_trabajadas = $diferencia->format('%H:%I');
            
            // Calcular en decimal para posibles cálculos
            $horas_decimal = $diferencia->h + ($diferencia->i / 60);
        }

        $data[] = [
            "asistencia_id" => $row['asistencia_id'],
            "colaborador" => $row['colaborador'],
            "fecha" => date("d/m/Y", strtotime($row['fecha'])),
            "horai" => !empty($row['horai']) ? date("H:i", strtotime($row['horai'])) : '--:--',
            "horaf" => !empty($row['horaf']) ? date("H:i", strtotime($row['horaf'])) : '--:--',
            "horas_trabajadas" => $horas_trabajadas,
            "horas_decimal" => $horas_decimal, // Para posibles cálculos
            "comentario" => $row['comentario'],
            "estado" => $row['estado'] == 1 ? 
                '<span class="badge badge-success">Pagado</span>' : 
                '<span class="badge badge-warning">Pendiente</span>'
        ];
    }
}

echo json_encode(["data" => $data]);