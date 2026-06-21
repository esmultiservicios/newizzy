<?php
// core/auth/validarAdministradorConfig.php

header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

$out = [
    'success' => false,
    'permitido' => false,
    'message' => 'Error desconocido.'
];

function responderAuthAdminSistema($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
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

    if ($usuario === '' || $passwordPlano === '') {
        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'Ingrese usuario y contraseña.'
        ]);
    }

    $empresaId = (int)(
        $_SESSION['empresa_id_sd']
        ?? $_SESSION['empresa_id']
        ?? 0
    );

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

        responderAuthAdminSistema([
            'success' => false,
            'permitido' => false,
            'message' => 'Usuario, contraseña o permisos no válidos.'
        ]);
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    $token = bin2hex(random_bytes(32));

    $_SESSION['admin_config_token'] = $token;
    $_SESSION['admin_config_token_expira'] = time() + 900;
    $_SESSION['admin_config_users_id'] = (int)$row['users_id'];
    $_SESSION['admin_config_tipo_user_id'] = (int)$row['tipo_user_id'];

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