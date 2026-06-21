<?php
// core/auth/validarAdministradorConfig.php

header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

function responderAuthAdminSistema($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function limpiarTextoAuthAdminSistema($valor, $limite = 255) {
    $valor = trim((string)$valor);

    if ($valor === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($valor, 0, $limite, 'UTF-8');
    }

    return substr($valor, 0, $limite);
}

function obtenerIpAuthAdminSistema() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return limpiarTextoAuthAdminSistema($_SERVER['HTTP_CLIENT_IP'], 45);
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $partes = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return limpiarTextoAuthAdminSistema($partes[0], 45);
    }

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return limpiarTextoAuthAdminSistema($_SERVER['REMOTE_ADDR'], 45);
    }

    return '';
}

function registrarAuditoriaAdminSistema($db, $datos) {
    if (!$db) {
        return 0;
    }

    $sql = "
        INSERT INTO auditoria_admin_autorizaciones (
            usuario_sesion_id,
            usuario_sesion_tipo_id,
            admin_users_id,
            admin_tipo_user_id,
            empresa_id,
            modulo,
            accion,
            referencia_id,
            referencia_texto,
            motivo,
            usuario_ingresado,
            permitido,
            resultado,
            mensaje,
            token_hash,
            ip,
            user_agent,
            fecha_registro
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $usuarioSesionId = isset($datos['usuario_sesion_id']) ? (int)$datos['usuario_sesion_id'] : 0;
    $usuarioSesionTipoId = isset($datos['usuario_sesion_tipo_id']) ? (int)$datos['usuario_sesion_tipo_id'] : 0;
    $adminUsersId = isset($datos['admin_users_id']) ? (int)$datos['admin_users_id'] : 0;
    $adminTipoUserId = isset($datos['admin_tipo_user_id']) ? (int)$datos['admin_tipo_user_id'] : 0;
    $empresaId = isset($datos['empresa_id']) ? (int)$datos['empresa_id'] : 0;

    if ($usuarioSesionId <= 0) {
        $usuarioSesionId = null;
    }

    if ($usuarioSesionTipoId <= 0) {
        $usuarioSesionTipoId = null;
    }

    if ($adminUsersId <= 0) {
        $adminUsersId = null;
    }

    if ($adminTipoUserId <= 0) {
        $adminTipoUserId = null;
    }

    $modulo = limpiarTextoAuthAdminSistema($datos['modulo'] ?? 'Sistema', 80);
    $accion = limpiarTextoAuthAdminSistema($datos['accion'] ?? 'Validación administrativa', 120);
    $referenciaId = limpiarTextoAuthAdminSistema($datos['referencia_id'] ?? '', 80);
    $referenciaTexto = limpiarTextoAuthAdminSistema($datos['referencia_texto'] ?? '', 180);
    $motivo = limpiarTextoAuthAdminSistema($datos['motivo'] ?? '', 255);
    $usuarioIngresado = limpiarTextoAuthAdminSistema($datos['usuario_ingresado'] ?? '', 80);
    $permitido = isset($datos['permitido']) ? (int)$datos['permitido'] : 0;
    $resultado = limpiarTextoAuthAdminSistema($datos['resultado'] ?? 'RECHAZADO', 30);
    $mensaje = limpiarTextoAuthAdminSistema($datos['mensaje'] ?? '', 255);
    $tokenHash = limpiarTextoAuthAdminSistema($datos['token_hash'] ?? '', 64);
    $ip = limpiarTextoAuthAdminSistema($datos['ip'] ?? '', 45);
    $userAgent = limpiarTextoAuthAdminSistema($datos['user_agent'] ?? '', 255);

    if ($referenciaId === '') {
        $referenciaId = null;
    }

    if ($referenciaTexto === '') {
        $referenciaTexto = null;
    }

    if ($motivo === '') {
        $motivo = null;
    }

    if ($tokenHash === '') {
        $tokenHash = null;
    }

    $stmt->bind_param(
        'iiiiissssssisssss',
        $usuarioSesionId,
        $usuarioSesionTipoId,
        $adminUsersId,
        $adminTipoUserId,
        $empresaId,
        $modulo,
        $accion,
        $referenciaId,
        $referenciaTexto,
        $motivo,
        $usuarioIngresado,
        $permitido,
        $resultado,
        $mensaje,
        $tokenHash,
        $ip,
        $userAgent
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }

    $auditoriaId = (int)$stmt->insert_id;
    $stmt->close();

    return $auditoriaId;
}

try {
    $mainModel = new mainModel();

    if (empty($_SESSION['users_id_sd'])) {
        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'Sesión no válida.'
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'Método no permitido.'
        ]);
    }

    $usuario = isset($_POST['usuario']) ? trim((string)$_POST['usuario']) : '';
    $passwordPlano = isset($_POST['password']) ? (string)$_POST['password'] : '';

    $modulo = limpiarTextoAuthAdminSistema($_POST['modulo'] ?? 'Sistema', 80);
    $accion = limpiarTextoAuthAdminSistema($_POST['accion'] ?? 'Validación administrativa', 120);
    $referenciaId = limpiarTextoAuthAdminSistema($_POST['referencia_id'] ?? '', 80);
    $referenciaTexto = limpiarTextoAuthAdminSistema($_POST['referencia_texto'] ?? '', 180);
    $motivo = limpiarTextoAuthAdminSistema($_POST['motivo'] ?? '', 255);

    $empresaId = (int)(
        $_SESSION['empresa_id_sd']
        ?? $_SESSION['empresa_id']
        ?? 0
    );

    $usuarioSesionId = (int)(
        $_SESSION['users_id_sd']
        ?? $_SESSION['user_sd']
        ?? $_SESSION['usuarios_id']
        ?? $_SESSION['user_id']
        ?? 0
    );

    $usuarioSesionTipoId = (int)(
        $_SESSION['tipo_user_id_sd']
        ?? $_SESSION['tipo_user_id']
        ?? 0
    );

    $ip = obtenerIpAuthAdminSistema();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if ($usuario === '' || $passwordPlano === '') {
        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'Ingrese usuario y contraseña.'
        ]);
    }

    if ($empresaId <= 0) {
        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'No se encontró la empresa de la sesión.'
        ]);
    }

    $db = $mainModel->connection();

    if (!$db) {
        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'No se pudo conectar a la base de datos.'
        ]);
    }

    $password = $mainModel->encryption($passwordPlano);

    $usuarioSinArroba = $usuario;

    if (strpos($usuario, '@') !== false) {
        $partesCorreo = explode('@', $usuario);
        $usuarioSinArroba = trim($partesCorreo[0]);
    }

    $sql = "
        SELECT 
            u.users_id,
            u.username,
            u.email,
            u.tipo_user_id,
            u.empresa_id,
            tu.nombre AS tipo_usuario
        FROM users AS u
        INNER JOIN tipo_user AS tu
            ON tu.tipo_user_id = u.tipo_user_id
        WHERE 
            u.estado = 1
            AND tu.estado = 1
            AND u.empresa_id = ?
            AND u.password = ?
            AND (
                BINARY u.username = ?
                OR BINARY u.email = ?
                OR BINARY SUBSTRING_INDEX(u.email, '@', 1) = ?
            )
            AND (
                u.tipo_user_id IN (1, 2)
                OR LOWER(TRIM(tu.nombre)) IN ('super administrador', 'administrador')
            )
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'No se pudo preparar la validación.',
            'error' => $db->error
        ]);
    }

    $stmt->bind_param(
        'issss',
        $empresaId,
        $password,
        $usuario,
        $usuario,
        $usuarioSinArroba
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'No se pudo ejecutar la validación.',
            'error' => $error
        ]);
    }

    $result = $stmt->get_result();

    if (!$result || $result->num_rows <= 0) {
        $stmt->close();

        registrarAuditoriaAdminSistema($db, [
            'usuario_sesion_id' => $usuarioSesionId,
            'usuario_sesion_tipo_id' => $usuarioSesionTipoId,
            'admin_users_id' => 0,
            'admin_tipo_user_id' => 0,
            'empresa_id' => $empresaId,
            'modulo' => $modulo,
            'accion' => $accion,
            'referencia_id' => $referenciaId,
            'referencia_texto' => $referenciaTexto,
            'motivo' => $motivo,
            'usuario_ingresado' => $usuario,
            'permitido' => 0,
            'resultado' => 'RECHAZADO',
            'mensaje' => 'Usuario, contraseña o permisos no válidos.',
            'token_hash' => '',
            'ip' => $ip,
            'user_agent' => $userAgent
        ]);

        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'Usuario, contraseña o permisos no válidos.'
        ]);
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $auditoriaId = registrarAuditoriaAdminSistema($db, [
        'usuario_sesion_id' => $usuarioSesionId,
        'usuario_sesion_tipo_id' => $usuarioSesionTipoId,
        'admin_users_id' => (int)$row['users_id'],
        'admin_tipo_user_id' => (int)$row['tipo_user_id'],
        'empresa_id' => $empresaId,
        'modulo' => $modulo,
        'accion' => $accion,
        'referencia_id' => $referenciaId,
        'referencia_texto' => $referenciaTexto,
        'motivo' => $motivo,
        'usuario_ingresado' => $usuario,
        'permitido' => 1,
        'resultado' => 'AUTORIZADO',
        'mensaje' => 'Administrador validado correctamente.',
        'token_hash' => $tokenHash,
        'ip' => $ip,
        'user_agent' => $userAgent
    ]);

    $_SESSION['admin_config_token'] = $token;
    $_SESSION['admin_config_token_expira'] = time() + 900;
    $_SESSION['admin_config_users_id'] = (int)$row['users_id'];
    $_SESSION['admin_config_tipo_user_id'] = (int)$row['tipo_user_id'];
    $_SESSION['admin_config_auditoria_id'] = $auditoriaId;

    try {
        $mainModel->guardar_historial_accesos(
            'Validación administrativa correcta - Usuario ID: ' . (int)$row['users_id']
        );
    } catch (Throwable $e) {
    }

    responderAuthAdminSistema([
        'success' => true,
        'permitido' => true,
        'message' => 'Administrador validado correctamente.',
        'token' => $token,
        'auditoria_admin_id' => $auditoriaId,
        'users_id' => (int)$row['users_id'],
        'tipo_user_id' => (int)$row['tipo_user_id'],
        'tipo_usuario' => $row['tipo_usuario'],
        'usuario' => $row['username'],
        'email' => $row['email']
    ]);

} catch (Throwable $e) {
    responderAuthAdminSistema([
        'success' => false,
        'permitido' => false,
        'message' => 'Error al validar administrador.',
        'error' => $e->getMessage()
    ]);
}