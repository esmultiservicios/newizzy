<?php
// core/llenarDataTableEmpresa.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

function empresaJsonResponse($payload, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$validacion = $insMainModel->validarSesion();

if (!empty($validacion['error'])) {
    empresaJsonResponse([
        'ok' => false,
        'sessionExpired' => true,
        'message' => $validacion['mensaje'] ?? 'La sesión ha expirado.',
        'redirect' => $validacion['redireccion'] ?? '',
        'data' => []
    ], 401);
}

$conexion = null;
$stmt = null;

try {
    $conexion = $insMainModel->connection();

    if (!$conexion) {
        throw new Exception('No se pudo establecer conexión con la base de datos.');
    }

    $stmt = $conexion->prepare(
        "SELECT nombre FROM privilegio WHERE privilegio_id = ?"
    );

    if (!$stmt) {
        throw new Exception('No se pudo preparar la consulta de privilegio: ' . $conexion->error);
    }

    $privilegioId = isset($_SESSION['privilegio_sd'])
        ? (int) $_SESSION['privilegio_sd']
        : 0;

    $stmt->bind_param('i', $privilegioId);

    if (!$stmt->execute()) {
        throw new Exception('No se pudo consultar el privilegio del usuario: ' . $stmt->error);
    }

    $resultadoPrivilegio = $stmt->get_result();
    $privilegioColaborador = '';

    if ($resultadoPrivilegio && $resultadoPrivilegio->num_rows > 0) {
        $rowPrivilegio = $resultadoPrivilegio->fetch_assoc();
        $privilegioColaborador = (string)($rowPrivilegio['nombre'] ?? '');
    }

    $stmt->close();
    $stmt = null;

    /*
     * Se conserva el contrato de getEmpresa().
     * Si no llega un estado explícito, se usa 1 como lo hacía el módulo
     * original para mantener el comportamiento existente.
     */
    $estado = (
        isset($_POST['estado']) &&
        $_POST['estado'] !== '' &&
        ($_POST['estado'] === '0' || $_POST['estado'] === '1' || $_POST['estado'] === 0 || $_POST['estado'] === 1)
    ) ? (int)$_POST['estado'] : 1;

    $datos = [
        'privilegio_id' => $privilegioId,
        'colaborador_id' => isset($_SESSION['colaborador_id_sd'])
            ? (int)$_SESSION['colaborador_id_sd']
            : 0,
        'privilegio_colaborador' => $privilegioColaborador,
        'empresa_id' => isset($_SESSION['empresa_id_sd'])
            ? (int)$_SESSION['empresa_id_sd']
            : 0,
        'estado' => $estado
    ];

    $result = $insMainModel->getEmpresa($datos);

    if (!$result) {
        throw new Exception('No se pudo obtener el listado de empresas.');
    }

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'empresa_id' => $row['empresa_id'] ?? '',
            'razon_social' => $row['razon_social'] ?? '',
            'nombre' => $row['nombre'] ?? '',
            'otra_informacion' => $row['otra_informacion'] ?? '',
            'eslogan' => $row['eslogan'] ?? '',
            'celular' => $row['celular'] ?? '',
            'telefono' => $row['telefono'] ?? '',
            'correo' => $row['correo'] ?? '',
            'image' => $row['logotipo'] ?? '',
            'logotipo' => $row['logotipo'] ?? '',
            'rtn' => $row['rtn'] ?? '',
            'ubicacion' => $row['ubicacion'] ?? '',
            'facebook' => $row['facebook'] ?? '',
            'sitioweb' => $row['sitioweb'] ?? '',
            'horario' => $row['horario'] ?? '',
            'estado' => $row['estado'] ?? 0,
            'fecha_registro' => $row['fecha_registro'] ?? '',
            'firma_documento' => $row['firma_documento'] ?? '',
            'MostrarFirma' => $row['MostrarFirma'] ?? 0
        ];
    }

    empresaJsonResponse([
        'ok' => true,
        'echo' => 1,
        'totalrecords' => count($data),
        'totaldisplayrecords' => count($data),
        'data' => $data
    ]);

} catch (Throwable $e) {
    empresaJsonResponse([
        'ok' => false,
        'echo' => 0,
        'message' => $e->getMessage(),
        'data' => []
    ], 500);

} finally {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}
