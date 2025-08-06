<?php
// getTotalFacturasDisponibles.php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

// Validar sesión
$validacion = $insMainModel->validarSesion();
if ($validacion['error']) {
    echo $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
    ]);
    exit;
}

$empresa_id = $_SESSION['empresa_id_sd'];

$siguienteNumero = 0;  // Cambiamos el nombre para mayor claridad
$rango_inicial = 0;
$rango_final = 0;
$contador = 0;
$fecha_limite = 'Sin definir';

// Obtener el siguiente número a usar
$resultNumero = $insMainModel->getTotalFacturasDisponiblesDB($empresa_id);
if ($resultNumero->num_rows > 0) {
    $row = $resultNumero->fetch_assoc();
    $siguienteNumero = (int)$row['numero'];
}

// Obtener rango inicial y final
$resultRango = $insMainModel->getNumeroMaximoPermitido($empresa_id);
if ($resultRango->num_rows > 0) {
    $row = $resultRango->fetch_assoc();
    $rango_final = (int)$row['rango_final'];
    $rango_inicial = (int)$row['rango_inicial'];
}

// Calcular total de facturas disponibles
if ($siguienteNumero === 0) {
    // Caso especial: no se ha usado ninguna factura
    $facturasPendientes = $rango_final - $rango_inicial + 1;
} else {
    // El último número usado es $siguienteNumero - 1
    $ultimoUsado = $siguienteNumero - 1;
    
    // Verificar si ya hemos alcanzado el límite
    if ($ultimoUsado >= $rango_final) {
        $facturasPendientes = 0; // Ya no quedan facturas disponibles
    } else {
        // Calcular las facturas restantes
        $facturasPendientes = $rango_final - $ultimoUsado;
    }
}

// Obtener fecha límite
$resultFecha = $insMainModel->getFechaLimiteFactura($empresa_id);
if ($resultFecha->num_rows > 0) {
    $row = $resultFecha->fetch_assoc();
    $contador = (int)$row['dias_transcurridos'];
    $fecha_limite = $row['fecha_limite'];
}

// Devolver datos en formato JSON
$datos = [
    'facturasPendientes' => $facturasPendientes,
    'contador' => $contador,
    'fechaLimite' => $fecha_limite
];

echo json_encode($datos);
exit;