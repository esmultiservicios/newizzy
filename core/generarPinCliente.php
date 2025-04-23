<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$codigoCliente = isset($_POST['codigoCliente']) ? $insMainModel->cleanString($_POST['codigoCliente']) : '';
$generateNew = isset($_POST['generateNew']) ? $insMainModel->cleanString($_POST['generateNew']) : '0';

// Validación mejorada del código de cliente
if (empty($codigoCliente) || !is_numeric($codigoCliente)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Código de cliente inválido',
        'message' => 'El código de cliente debe ser un valor numérico válido'
    ]);
    exit;
}

// Validar longitud mínima (ejemplo: mínimo 4 dígitos)
if (strlen($codigoCliente) < 4) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Código de cliente demasiado corto',
        'message' => 'El código debe tener al menos 4 dígitos'
    ]);
    exit;
}

try {
    // Establecer conexión a la base de datos
    $mysqli = $insMainModel->connection();
    
    // Iniciar transacción
    $mysqli->autocommit(false);

    // Verificar si el cliente ya tiene un PIN válido
    $pinExistente = obtenerPinValido($mysqli, $codigoCliente);
    
    // Variable para almacenar el PIN que se devolverá
    $pin = null;

    // Lógica para determinar si se necesita generar un nuevo PIN
    if ($generateNew === "1" || $pinExistente === null) {
        // Generar un nuevo PIN único
        $pin = generarPinUnico($mysqli);
        
        // Invalidar cualquier PIN anterior que esté activo
        invalidarPinAnterior($mysqli, $codigoCliente);
        
        // Insertar el nuevo PIN en la base de datos local
        insertarNuevoPin($mysqli, $codigoCliente, $pin, $_SESSION['server_customers_id']);
        
        // Insertar el PIN en el servidor principal
        insertarPinEnServidorPrincipal($insMainModel, $codigoCliente, $pin, $_SESSION['server_customers_id']);
    } else {
        // Usar el PIN existente
        $pin = $pinExistente;
    }

    // Confirmar la transacción
    $mysqli->commit();
    
    // Devolver respuesta exitosa con el PIN
    header('Content-Type: application/json');
    echo json_encode([
        'pin' => $pin,
        'success' => true,
        'message' => 'PIN generado/recuperado exitosamente'
    ]);
    
} catch (Exception $e) {
    // En caso de error, revertir la transacción
    if (isset($mysqli)) {
        $mysqli->rollback();
    }
    
    // Devolver respuesta de error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Error al procesar la solicitud de PIN',
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    // Restaurar autocommit y cerrar conexión
    if (isset($mysqli)) {
        $mysqli->autocommit(true);
        $mysqli->close();
    }
}

/**
 * Verifica si el cliente tiene un PIN válido y lo devuelve si existe
 * @param mysqli $mysqli Conexión a la base de datos
 * @param string $codigoCliente Código del cliente
 * @return string|null El PIN válido o null si no existe
 */
function obtenerPinValido($mysqli, $codigoCliente) {
    $query = "SELECT pin FROM pin WHERE codigo_cliente = ? AND fecha_hora_fin > NOW() LIMIT 1";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $codigoCliente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pinData = $result->fetch_assoc();
        $stmt->close();
        return $pinData['pin'];
    }
    
    $stmt->close();
    return null;
}

/**
 * Genera un PIN numérico único de 6 dígitos
 * @param mysqli $mysqli Conexión a la base de datos
 * @return int PIN único generado
 * @throws Exception Si no se puede generar un PIN único después de varios intentos
 */
function generarPinUnico($mysqli) {
    $pin = null;
    $maxIntentos = 10;
    $intentos = 0;
    
    do {
        $pin = mt_rand(100000, 999999);
        $query = "SELECT pin FROM pin WHERE pin = ? LIMIT 1";
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $mysqli->error);
        }
        
        $stmt->bind_param("i", $pin);
        $stmt->execute();
        $result = $stmt->get_result();
        $existe = $result->num_rows > 0;
        $stmt->close();
        
        $intentos++;
        if ($intentos >= $maxIntentos) {
            throw new Exception("No se pudo generar un PIN único después de $maxIntentos intentos");
        }
    } while ($existe);
    
    return $pin;
}

/**
 * Invalida cualquier PIN activo que tenga el cliente
 * @param mysqli $mysqli Conexión a la base de datos
 * @param string $codigoCliente Código del cliente
 */
function invalidarPinAnterior($mysqli, $codigoCliente) {
    $fechaHoraActual = date("Y-m-d H:i:s");
    
    $query = "UPDATE pin SET fecha_hora_fin = ? 
              WHERE codigo_cliente = ? AND fecha_hora_fin > NOW()";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $mysqli->error);
    }
    
    $stmt->bind_param("si", $fechaHoraActual, $codigoCliente);
    $stmt->execute();
    $stmt->close();
}

/**
 * Inserta un nuevo PIN en la base de datos
 * @param mysqli $mysqli Conexión a la base de datos
 * @param string $codigoCliente Código del cliente
 * @param int $pin PIN generado
 * @param int $serverCustomersId ID del cliente en el servidor principal
 */
function insertarNuevoPin($mysqli, $codigoCliente, $pin, $serverCustomersId) {
    // Obtener el próximo ID disponible
    $query = "SELECT IFNULL(MAX(pin_id), 0) + 1 AS next_id FROM pin";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $mysqli->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pin_id = $row['next_id'];
    $stmt->close();
    
    // Calcular fechas de validez (5 minutos de vigencia)
    $fechaHoraInicio = date("Y-m-d H:i:s");
    $fechaHoraFin = date("Y-m-d H:i:s", strtotime($fechaHoraInicio) + (5 * 60));
    
    // Insertar el nuevo registro
    $query = "INSERT INTO pin (pin_id, server_customers_id, codigo_cliente, pin, fecha_hora_inicio, fecha_hora_fin) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $mysqli->error);
    }
    
    $stmt->bind_param("iiisss", $pin_id, $serverCustomersId, $codigoCliente, $pin, $fechaHoraInicio, $fechaHoraFin);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al insertar el PIN: " . $stmt->error);
    }
    
    $stmt->close();
}

/**
 * Inserta el PIN en el servidor principal
 * @param mainModel $mainModel Instancia del modelo principal
 * @param string $codigoCliente Código del cliente
 * @param int $pin PIN generado
 * @param int $serverCustomersId ID del cliente en el servidor principal
 */
function insertarPinEnServidorPrincipal($mainModel, $codigoCliente, $pin, $serverCustomersId) {
    $fechaHoraInicio = date("Y-m-d H:i:s");
    $fechaHoraFin = date("Y-m-d H:i:s", strtotime($fechaHoraInicio) + (5 * 60));
    
    $datos = [
        "server_customers_id" => $serverCustomersId,
        "codigo_cliente" => $codigoCliente,
        "pin" => $pin,
        "fecha_hora_inicio" => $fechaHoraInicio,
        "fecha_hora_fin" => $fechaHoraFin
    ];
    
    $resultado = $mainModel->insertarPinServerP($datos);
    
    if (!$resultado) {
        throw new Exception("Error al insertar el PIN en el servidor principal");
    }
}