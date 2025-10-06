<?php
// ajax/addNominaAjax.php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');

try {
    // Cargar configuración
    require_once __DIR__ . '/../core/configGenerales.php';

    // Asegurar método POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'status'  => 'error',
            'title'   => 'Método no permitido',
            'message' => 'Debes usar el método POST.'
        ]);
        exit;
    }

    // Validaciones mínimas (respeta exactamente los names del form)
    $required = ['nomina_detale', 'nomina_pago_planificado_id'];
    $missing  = [];
    foreach ($required as $r) {
        if (!isset($_POST[$r]) || $_POST[$r] === '') $missing[] = $r;
    }
    if (!empty($missing)) {
        echo json_encode([
            'status'  => 'error',
            'title'   => 'Error 🚨',
            'message' => 'Faltan parámetros obligatorios.',
            'missing' => $missing
        ]);
        exit;
    }

    // Cargar el archivo del controlador
    $ctrlPath  = __DIR__ . '/../controladores/nominaControlador.php';
    if (!file_exists($ctrlPath)) {
        echo json_encode([
            'status'  => 'error',
            'title'   => 'Controlador no encontrado',
            'message' => "No existe el archivo: {$ctrlPath}"
        ]);
        exit;
    }
    require_once $ctrlPath;

    // Instanciar la clase EXACTA si está definida
    $ctrlClass = 'nominaControlador';
    if (!class_exists($ctrlClass)) {
        echo json_encode([
            'status'  => 'error',
            'title'   => 'Clase no definida',
            'message' => "El archivo nominaControlador.php no define la clase PHP '{$ctrlClass}'."
        ]);
        exit;
    }

    $method = 'agregar_nomina_controlador';
    if (!method_exists($ctrlClass, $method)) {
        echo json_encode([
            'status'  => 'error',
            'title'   => 'Método no encontrado',
            'message' => "El método '{$method}' no existe en la clase '{$ctrlClass}'."
        ]);
        exit;
    }

    // ¡Ejecutar!
    $insNomina = new $ctrlClass(); // <- instanciación dinámica (no rompe Intelephense)
    $res = $insNomina->$method();

    // Si el método devuelve array, lo convertimos a JSON; si ya es JSON, lo imprimimos.
    if (is_array($res)) {
        echo json_encode($res);
    } else {
        echo (string)$res;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'title'   => 'Excepción',
        'message' => $e->getMessage()
    ]);
}