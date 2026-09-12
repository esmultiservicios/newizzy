<?php
// core/cheques/_bootstrap.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

function chequesJson($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chequesMainModel() {
    static $model = null;
    if ($model === null) {
        $model = new mainModel();
    }
    return $model;
}

function chequesDb() {
    static $db = null;
    if ($db === null) {
        $db = chequesMainModel()->connection();
        if (!$db) {
            chequesJson([
                'success' => false,
                'title' => 'Base de datos no disponible',
                'message' => 'No se pudo establecer conexión con la base de datos.'
            ], 500);
        }
        $db->set_charset('utf8mb4');
    }
    return $db;
}

function chequesSesion() {
    $empresa = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
    $colaborador = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;
    $usuario = isset($_SESSION['users_id_sd']) ? (int)$_SESSION['users_id_sd'] : 0;

    if ($empresa <= 0 || $colaborador <= 0 || $usuario <= 0) {
        chequesJson([
            'success' => false,
            'title' => 'Sesión inválida',
            'message' => 'No se pudo identificar la empresa o el usuario de la sesión.'
        ], 401);
    }

    return [
        'empresa_id' => $empresa,
        'colaboradores_id' => $colaborador,
        'users_id' => $usuario
    ];
}

function chequesSoloPost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        chequesJson([
            'success' => false,
            'title' => 'Método no permitido',
            'message' => 'La operación solicitada requiere POST.'
        ], 405);
    }
}

function chequesTexto($valor, $max = 255) {
    $valor = trim((string)$valor);
    $valor = strip_tags($valor);
    $valor = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor);
    if (function_exists('mb_substr')) {
        return mb_substr($valor, 0, $max, 'UTF-8');
    }
    return substr($valor, 0, $max);
}

function chequesFechaValida($fecha) {
    $obj = DateTime::createFromFormat('Y-m-d', (string)$fecha);
    return $obj && $obj->format('Y-m-d') === $fecha;
}

function chequesTablaTieneColumna($db, $tabla, $columna) {
    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $db->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('ss', $tabla, $columna);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function chequesValidarInstalacion($db) {
    $requeridas = [
        'numero_cheque',
        'egresos_id',
        'categoria_gastos_id',
        'tipo_pago_id',
        'movimientos_cuentas_id',
        'movimiento_reintegro_id',
        'estado',
        'fecha_anulacion',
        'colaboradores_anula_id',
        'motivo_anulacion'
    ];

    $faltantes = [];
    foreach ($requeridas as $col) {
        if (!chequesTablaTieneColumna($db, 'cheque', $col)) {
            $faltantes[] = $col;
        }
    }

    if ($faltantes) {
        chequesJson([
            'success' => false,
            'title' => 'Módulo pendiente de instalar',
            'message' => 'Ejecute database/INSTALAR_CHEQUES.sql antes de usar el módulo.',
            'faltantes' => $faltantes
        ], 500);
    }
}

function chequesValidarTokenAdmin($token) {
    $token = trim((string)$token);

    if ($token === '') {
        chequesJson([
            'success' => false,
            'title' => 'Validación requerida',
            'message' => 'Debe validar un administrador para realizar esta acción.'
        ], 403);
    }

    if (
        empty($_SESSION['admin_config_token']) ||
        empty($_SESSION['admin_config_token_expira']) ||
        !hash_equals((string)$_SESSION['admin_config_token'], $token) ||
        time() > (int)$_SESSION['admin_config_token_expira']
    ) {
        chequesJson([
            'success' => false,
            'title' => 'Validación vencida',
            'message' => 'La validación administrativa no es válida o ha vencido. Vuelva a validar.'
        ], 403);
    }
}

function chequesSiguienteId($db, $tabla, $pk) {
    $permitidos = [
        'cheque' => 'cheque_id',
        'egresos' => 'egresos_id',
        'movimientos_cuentas' => 'movimientos_cuentas_id',
        'categoria_gastos' => 'categoria_gastos_id'
    ];

    if (!isset($permitidos[$tabla]) || $permitidos[$tabla] !== $pk) {
        throw new Exception('Tabla de correlativo no permitida.');
    }

    $sql = "SELECT COALESCE(MAX(`$pk`),0)+1 AS siguiente FROM `$tabla`";
    $res = $db->query($sql);
    if (!$res) throw new Exception($db->error);
    $row = $res->fetch_assoc();
    return (int)$row['siguiente'];
}

function chequesObtenerSaldoActual($db, $cuentaId, $empresaId) {
    $stmt = $db->prepare("
        SELECT saldo
        FROM movimientos_cuentas
        WHERE cuentas_id = ?
          AND empresa_id = ?
        ORDER BY movimientos_cuentas_id DESC
        LIMIT 1
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param('ii', $cuentaId, $empresaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? round((float)$row['saldo'], 2) : 0.0;
}

function chequesCuentaTipoPago($db, $tipoPagoId, $empresaId = 0) {
    $stmt = $db->prepare("
        SELECT tp.tipo_pago_id, tp.nombre AS tipo_pago, tp.cuentas_id,
               c.codigo, c.nombre AS cuenta, c.estado AS cuenta_estado
        FROM tipo_pago tp
        LEFT JOIN cuentas c ON c.cuentas_id = tp.cuentas_id
        WHERE tp.tipo_pago_id = ?
          AND tp.estado = 1
        LIMIT 1
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param('i', $tipoPagoId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || (int)$row['cuentas_id'] <= 0) {
        return null;
    }

    $row['saldo'] = chequesObtenerSaldoActual($db, (int)$row['cuentas_id'], $empresaId);
    return $row;
}

function chequesDesbloquear($db) {
    try { $db->query("UNLOCK TABLES"); } catch (Throwable $e) {}
}
