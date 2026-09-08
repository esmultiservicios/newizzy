<?php
// core/AsignarPlanes/administrarCliente.php
// Administración centralizada de colaboradores, usuarios y empresas de una DB cliente.

$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/AsignarPlanesSyncHelper.php';

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

function apiResponder($success, $message = '', $data = [], $extra = []) {
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function apiClean($value) {
    return trim((string)$value);
}

function apiInt($key, $default = 0) {
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function apiStr($key, $default = '') {
    return isset($_POST[$key]) ? apiClean($_POST[$key]) : $default;
}

function apiNextId(mysqli $cn, $table, $column) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        throw new Exception('Nombre de tabla o columna inválido.');
    }
    $rs = $cn->query("SELECT COALESCE(MAX(`{$column}`),0)+1 AS siguiente FROM `{$table}`");
    if (!$rs) throw new Exception('No se pudo obtener el correlativo: ' . $cn->error);
    $row = $rs->fetch_assoc();
    return (int)$row['siguiente'];
}

function apiTableExists(mysqli $cn, $table) {
    $stmt = $cn->prepare("SELECT COUNT(*) total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    if (!$stmt) return false;
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row['total']);
}

function apiColumns(mysqli $cn, $table) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return [];
    $out = [];
    $rs = $cn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$rs) return [];
    while ($row = $rs->fetch_assoc()) $out[] = $row['Field'];
    return $out;
}

function apiCatalog(mysqli $cn, $table, $preferredId, $preferredName) {
    if (!apiTableExists($cn, $table)) return [];
    $cols = apiColumns($cn, $table);
    $id = in_array($preferredId, $cols, true) ? $preferredId : null;
    $nameCandidates = [$preferredName, 'nombre', 'descripcion', 'puesto', 'tipo_user', 'privilegio'];
    $name = null;
    foreach ($nameCandidates as $candidate) {
        if (in_array($candidate, $cols, true)) { $name = $candidate; break; }
    }
    if (!$id || !$name) return [];
    $estadoClause = in_array('estado', $cols, true) ? ' WHERE estado = 1' : '';
    $rs = $cn->query("SELECT `{$id}` id, `{$name}` nombre FROM `{$table}`{$estadoClause} ORDER BY `{$name}` ASC");
    if (!$rs) return [];
    $out = [];
    while ($row = $rs->fetch_assoc()) $out[] = ['id' => (int)$row['id'], 'nombre' => trim((string)$row['nombre'])];
    return $out;
}

function apiServerCustomer(mysqli $main, $serverCustomerId) {
    $stmt = $main->prepare("SELECT sc.server_customers_id, sc.clientes_id, sc.sistema_id, sc.db, sc.codigo_cliente, c.nombre cliente_nombre, c.rtn cliente_rtn, s.nombre sistema_nombre FROM server_customers sc INNER JOIN clientes c ON c.clientes_id=sc.clientes_id INNER JOIN sistema s ON s.sistema_id=sc.sistema_id WHERE sc.server_customers_id=? LIMIT 1");
    if (!$stmt) throw new Exception('No se pudo preparar la consulta del cliente.');
    $stmt->bind_param('i', $serverCustomerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) throw new Exception('No se encontró el cliente solicitado.');
    if (strtoupper(trim((string)$row['sistema_nombre'])) !== 'IZZY') {
        throw new Exception('Esta administración está disponible únicamente para clientes IZZY.');
    }
    if (!AsignarPlanesSyncHelper::validarDbName($row['db'])) throw new Exception('La base de datos del cliente no es válida.');
    return $row;
}

function apiUsername($email) {
    $base = strstr(strtolower(trim($email)), '@', true);
    if ($base === false || $base === '') $base = 'usuario';
    $base = preg_replace('/[^a-z0-9._-]/', '', $base);
    $base = trim($base, '._-');
    if ($base === '') $base = 'usuario';
    return substr($base, 0, 20);
}

function apiGenerateCollaboratorIdentity(mysqli $client) {
    // colaboradores.identidad es CHAR(13). Generamos exactamente 13 caracteres.
    for ($attempt = 0; $attempt < 25; $attempt++) {
        $identity = 'C' . str_pad((string)random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        $stmt = $client->prepare("SELECT colaboradores_id FROM colaboradores WHERE identidad=? LIMIT 1");
        if (!$stmt) throw new Exception('No se pudo validar la identificación interna.');
        $stmt->bind_param('s', $identity);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$exists) return $identity;
    }
    throw new Exception('No se pudo generar una identificación interna única.');
}

function apiGetMainMirrorUser(mysqli $main, $serverCustomerId, $email) {
    $stmt = $main->prepare("SELECT u.users_id, u.colaboradores_id FROM users u WHERE u.server_customers_id=? AND LOWER(u.email)=LOWER(?) LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('is', $serverCustomerId, $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function apiEnsureMainCollaborator(mysqli $main, array $c) {
    $identity = trim((string)$c['identidad']);
    if ($identity !== '') {
        $stmt = $main->prepare("SELECT colaboradores_id FROM colaboradores WHERE identidad=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $identity);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) return (int)$row['colaboradores_id'];
        }
    }
    $id = apiNextId($main, 'colaboradores', 'colaboradores_id');
    $puesto = (int)$c['puestos_id'];
    if ($puesto <= 0) $puesto = 5;
    $nombre = trim((string)$c['nombre']);
    $telefono = trim((string)$c['telefono']);
    $empresa = 1;
    $estado = (int)$c['estado'] === 2 ? 2 : 1;
    $fechaIngreso = !empty($c['fecha_ingreso']) ? $c['fecha_ingreso'] : date('Y-m-d');
    if ($identity === '') $identity = 'C-' . $id . '-' . date('ymd');
    $stmt = $main->prepare("INSERT INTO colaboradores (colaboradores_id, puestos_id, nombre, identidad, estado, telefono, empresa_id, fecha_registro, fecha_ingreso, fecha_egreso) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, '')");
    if (!$stmt) throw new Exception('No se pudo preparar el colaborador espejo: ' . $main->error);
    $stmt->bind_param('iissisis', $id, $puesto, $nombre, $identity, $estado, $telefono, $empresa, $fechaIngreso);
    if (!$stmt->execute()) throw new Exception('No se pudo crear el colaborador espejo: ' . $stmt->error);
    $stmt->close();
    return $id;
}

function apiSyncMainCollaborator(mysqli $main, array $c, $oldIdentity = null) {
    $identity = trim((string)$c['identidad']);
    $lookupIdentity = trim((string)($oldIdentity !== null ? $oldIdentity : $identity));
    if ($lookupIdentity === '') return;
    $stmt = $main->prepare("UPDATE colaboradores SET nombre=?, identidad=?, telefono=?, estado=? WHERE identidad=?");
    if (!$stmt) return;
    $nombre = trim((string)$c['nombre']);
    $telefono = trim((string)$c['telefono']);
    $estado = (int)$c['estado'] === 2 ? 2 : 1;
    $stmt->bind_param('sssis', $nombre, $identity, $telefono, $estado, $lookupIdentity);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    if ($affected === 0) {
        apiEnsureMainCollaborator($main, $c);
    }
}

function apiCreateMainMirrorUser(mysqli $main, $serverCustomerId, $collabId, array $u) {
    if (apiGetMainMirrorUser($main, $serverCustomerId, $u['email'])) return;
    $id = apiNextId($main, 'users', 'users_id');
    // En DB_MAIN el registro es solo espejo de autenticación del cliente.
    // Se conservan los valores por defecto que ya usa usuarioControlador para no
    // mezclar el catálogo de privilegios/permisos del cliente con el de MAIN.
    $priv = 4;
    $tipo = 4;
    $username = apiUsername($u['email']);
    $empresa = 1;
    $estado = (int)$u['estado'] === 2 ? 2 : 1;
    $stmt = $main->prepare("INSERT INTO users (users_id,colaboradores_id,privilegio_id,username,password,email,tipo_user_id,estado,fecha_registro,empresa_id,server_customers_id) VALUES (?,?,?,?,?,?,?,?,NOW(),?,?)");
    if (!$stmt) throw new Exception('No se pudo preparar el usuario espejo: ' . $main->error);
    $stmt->bind_param('iiisssiiii', $id, $collabId, $priv, $username, $u['password'], $u['email'], $tipo, $estado, $empresa, $serverCustomerId);
    if (!$stmt->execute()) throw new Exception('No se pudo crear el usuario espejo: ' . $stmt->error);
    $stmt->close();
}

function apiUpdateMainMirrorUser(mysqli $main, $serverCustomerId, $oldEmail, array $u) {
    $username = apiUsername($u['email']);
    $estado = (int)$u['estado'] === 2 ? 2 : 1;
    $stmt = $main->prepare("UPDATE users SET email=?, username=?, estado=? WHERE server_customers_id=? AND LOWER(email)=LOWER(?)");
    if (!$stmt) throw new Exception('No se pudo preparar la actualización del usuario espejo.');
    $stmt->bind_param('ssiis', $u['email'], $username, $estado, $serverCustomerId, $oldEmail);
    if (!$stmt->execute()) throw new Exception('No se pudo actualizar el usuario espejo: ' . $stmt->error);
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

function apiSendMailBase($email, $name, $subject, $bodyHtml) {
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

        $path = __DIR__ . '/../correo/sendEmail.php';
        if (!file_exists($path)) return false;

        require_once $path;
        if (!class_exists('sendEmail')) return false;

        $sender = new sendEmail();
        $destinatarios = [$email => $name ?: 'Cliente IZZY'];
        $bcc = [];
        $empresaId = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 1;

        $mensaje =
            '<div style="font-family:Arial,sans-serif;line-height:1.55;color:#24364b;padding:20px">' .
                '<div style="font-size:18px;font-weight:700;color:#15324a;margin-bottom:12px">IZZY · ES MULTISERVICIOS</div>' .
                $bodyHtml .
                '<div style="margin-top:20px;padding-top:14px;border-top:1px solid #e3e9ef;color:#69798a;font-size:12px">' .
                    'Este cambio fue realizado desde la administración central de <strong>ES MULTISERVICIOS</strong> y no directamente desde el sistema del cliente.' .
                '</div>' .
            '</div>';

        $sender->enviarCorreo($destinatarios, $bcc, $subject, $mensaje, 2, $empresaId, []);
        return true;
    } catch (Throwable $e) {
        error_log('IZZY admin clientes - correo: ' . $e->getMessage());
        return false;
    }
}

function apiSendPasswordMail($email, $name, $plainPassword) {
    $safeName = htmlspecialchars($name ?: 'Usuario', ENT_QUOTES, 'UTF-8');
    $safePass = htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');

    $body =
        '<h3 style="margin:0 0 10px;color:#15324a">Nueva contraseña temporal</h3>' .
        '<p>Hola '.$safeName.',</p>' .
        '<p>ES MULTISERVICIOS generó una nueva contraseña temporal para tu acceso a IZZY.</p>' .
        '<p style="padding:12px 14px;background:#f4f8fc;border-radius:8px"><strong>Nueva contraseña:</strong> '.$safePass.'</p>' .
        '<p>Por seguridad, cambia esta contraseña después de iniciar sesión.</p>';

    return apiSendMailBase($email, $name, 'IZZY - Nueva contraseña temporal | ES MULTISERVICIOS', $body);
}

function apiSendChangeMail($email, $name, $title, $detail, $clientName = '') {
    $safeName = htmlspecialchars($name ?: 'Cliente', ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeDetail = htmlspecialchars($detail, ENT_QUOTES, 'UTF-8');
    $safeClient = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');

    $body =
        '<h3 style="margin:0 0 10px;color:#15324a">'.$safeTitle.'</h3>' .
        '<p>Hola '.$safeName.',</p>' .
        ($safeClient !== '' ? '<p>Se realizó un cambio administrativo para <strong>'.$safeClient.'</strong>.</p>' : '') .
        '<p style="padding:12px 14px;background:#f4f8fc;border-radius:8px">'.$safeDetail.'</p>' .
        '<p>Si no solicitaste este cambio o necesitas asistencia, contacta a ES MULTISERVICIOS.</p>';

    return apiSendMailBase($email, $name, 'IZZY - '.$title.' | ES MULTISERVICIOS', $body);
}

function apiCompanyEmail(mysqli $client, $empresaId) {
    if ((int)$empresaId <= 0) return '';
    $stmt = $client->prepare("SELECT correo FROM empresa WHERE empresa_id=? LIMIT 1");
    if (!$stmt) return '';
    $stmt->bind_param('i', $empresaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $email = $row ? strtolower(trim((string)$row['correo'])) : '';
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function apiCollaboratorNotificationEmail(mysqli $client, $collabId, $empresaId) {
    $stmt = $client->prepare("SELECT email FROM users WHERE colaboradores_id=? AND estado=1 ORDER BY users_id ASC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $collabId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $email = $row ? strtolower(trim((string)$row['email'])) : '';
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
    }

    return apiCompanyEmail($client, $empresaId);
}

function apiPlanLimits(mysqli $client) {
    $result = [
        'plan_id' => 0,
        'plan_nombre' => '',
        'usuarios_configurado' => false,
        'usuarios_base' => null,
        'usuarios_extra' => 0,
        'usuarios_limite' => null,
        'usuarios_actuales' => 0,
        'usuarios_disponibles' => null,
        'puede_crear_usuario' => true,
        'empresas_configurado' => false,
        'empresas_limite' => null,
        'empresas_actuales' => 0,
        'empresas_disponibles' => null,
        'puede_crear_empresa' => true
    ];

    if (!apiTableExists($client, 'plan') || !apiTableExists($client, 'planes')) {
        return $result;
    }

    $sql = "SELECT p.planes_id, IFNULL(p.user_extra,0) user_extra, pl.nombre, pl.configuraciones
            FROM plan p
            LEFT JOIN planes pl ON pl.planes_id=p.planes_id
            WHERE p.plan_id=1 LIMIT 1";
    $rs = $client->query($sql);
    if (!$rs) return $result;
    $row = $rs->fetch_assoc();
    if (!$row) return $result;

    $result['plan_id'] = (int)$row['planes_id'];
    $result['plan_nombre'] = trim((string)($row['nombre'] ?? ''));
    $result['usuarios_extra'] = max(0, (int)$row['user_extra']);

    $configs = [];
    if (!empty($row['configuraciones'])) {
        $decoded = json_decode($row['configuraciones'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $configs = $decoded;
    }

    $urs = $client->query("SELECT COUNT(*) total FROM users WHERE estado=1 AND tipo_user_id NOT IN(1)");
    if ($urs) $result['usuarios_actuales'] = (int)($urs->fetch_assoc()['total'] ?? 0);

    $ers = $client->query("SELECT COUNT(*) total FROM empresa WHERE estado=1");
    if ($ers) $result['empresas_actuales'] = (int)($ers->fetch_assoc()['total'] ?? 0);

    if (array_key_exists('usuarios', $configs)) {
        $base = max(0, (int)$configs['usuarios']);
        $limit = $base + $result['usuarios_extra'];
        $result['usuarios_configurado'] = true;
        $result['usuarios_base'] = $base;
        $result['usuarios_limite'] = $limit;
        $result['usuarios_disponibles'] = max(0, $limit - $result['usuarios_actuales']);
        $result['puede_crear_usuario'] = $base > 0 && $result['usuarios_actuales'] < $limit;
    }

    if (array_key_exists('empresas', $configs)) {
        $limit = max(0, (int)$configs['empresas']);
        $result['empresas_configurado'] = true;
        $result['empresas_limite'] = $limit;
        $result['empresas_disponibles'] = max(0, $limit - $result['empresas_actuales']);
        $result['puede_crear_empresa'] = $limit > 0 && $result['empresas_actuales'] < $limit;
    }

    return $result;
}

function apiAssertCanCreateUser(mysqli $client) {
    $limits = apiPlanLimits($client);
    if (!$limits['usuarios_configurado']) return $limits;
    if ((int)$limits['usuarios_base'] === 0) {
        throw new Exception('El plan actual no incluye la creación de usuarios. Solo puede editar los usuarios existentes.');
    }
    if (!$limits['puede_crear_usuario']) {
        throw new Exception('El plan alcanzó el límite de usuarios permitido ('.$limits['usuarios_actuales'].' de '.$limits['usuarios_limite'].'). Puede editar usuarios existentes o aumentar el plan/usuarios extra.');
    }
    return $limits;
}

function apiAssertCanCreateCompany(mysqli $client) {
    $limits = apiPlanLimits($client);
    if (!$limits['empresas_configurado']) return $limits;
    if ((int)$limits['empresas_limite'] === 0) {
        throw new Exception('El plan actual no incluye empresas adicionales. Solo puede editar las empresas existentes.');
    }
    if (!$limits['puede_crear_empresa']) {
        throw new Exception('El plan alcanzó el límite de empresas permitido ('.$limits['empresas_actuales'].' de '.$limits['empresas_limite'].'). Solo puede editar las empresas existentes o cambiar de plan.');
    }
    return $limits;
}

function apiFriendlyError(Throwable $e, $action = '') {
    $raw = trim((string)$e->getMessage());
    $low = strtolower($raw);

    if (strpos($low, 'duplicate') !== false || strpos($low, 'duplic') !== false) {
        return 'Ya existe un registro con esos datos. Revise identidad, correo o RTN e inténtelo nuevamente.';
    }
    if (strpos($low, 'data too long') !== false || strpos($low, 'truncated') !== false) {
        return 'Uno de los datos supera el tamaño permitido. Revise los campos ingresados e inténtelo nuevamente.';
    }
    if (strpos($low, 'foreign key') !== false) {
        return 'No se pudo completar el cambio porque uno de los datos relacionados ya no existe. Actualice la vista e inténtelo nuevamente.';
    }
    if (strpos($low, 'unknown column') !== false) {
        return 'La estructura de la base del cliente no coincide con la esperada para esta función. No se realizó ningún cambio.';
    }
    if (strpos($low, 'server has gone away') !== false || strpos($low, 'lost connection') !== false) {
        return 'Se perdió la conexión con la base de datos. No se pudo completar la operación.';
    }

    return $raw !== '' ? $raw : 'No se pudo completar la operación solicitada.';
}

if (method_exists($mainModel, 'validarSesion')) {
    $v = $mainModel->validarSesion();
    if (!empty($v['error'])) apiResponder(false, $v['mensaje'] ?? 'Sesión inválida.');
}

$action = apiStr('action');
$serverCustomerId = apiInt('server_customers_id');
if ($serverCustomerId <= 0) apiResponder(false, 'No se recibió el cliente a administrar.');

$main = null;
$client = null;

try {
    $main = AsignarPlanesSyncHelper::conectarPrincipal($mainModel);
    if (!$main) throw new Exception('No se pudo conectar a la base principal.');
    $sc = apiServerCustomer($main, $serverCustomerId);
    $client = AsignarPlanesSyncHelper::conectarCliente($mainModel, $sc['db']);
    if (!$client) throw new Exception('No se pudo conectar a la base del cliente.');

    if ($action === 'list') {
        $sql = "SELECT c.colaboradores_id,c.puestos_id,c.nombre,c.identidad,c.estado,c.telefono,c.empresa_id,c.fecha_registro,c.fecha_ingreso,c.fecha_egreso,
                       u.users_id,u.privilegio_id,u.username,u.email,u.tipo_user_id,u.estado user_estado,u.fecha_registro user_fecha_registro,u.empresa_id user_empresa_id,u.server_customers_id,
                       e.nombre empresa_nombre,e.razon_social empresa_razon_social
                FROM colaboradores c
                LEFT JOIN users u ON u.colaboradores_id=c.colaboradores_id
                LEFT JOIN empresa e ON e.empresa_id=COALESCE(u.empresa_id,c.empresa_id)
                ORDER BY c.nombre ASC, u.users_id ASC";
        $rs = $client->query($sql);
        if (!$rs) throw new Exception('No se pudo consultar colaboradores: ' . $client->error);
        $map = [];
        while ($r = $rs->fetch_assoc()) {
            $cid = (int)$r['colaboradores_id'];
            if (!isset($map[$cid])) {
                $map[$cid] = [
                    'colaboradores_id' => $cid,
                    'puestos_id' => (int)$r['puestos_id'],
                    'nombre' => trim((string)$r['nombre']),
                    'identidad' => trim((string)$r['identidad']),
                    'estado' => (int)$r['estado'],
                    'telefono' => trim((string)$r['telefono']),
                    'empresa_id' => (int)$r['empresa_id'],
                    'fecha_registro' => $r['fecha_registro'],
                    'fecha_ingreso' => $r['fecha_ingreso'],
                    'fecha_egreso' => $r['fecha_egreso'],
                    'usuarios' => []
                ];
            }
            if (!empty($r['users_id'])) {
                $map[$cid]['usuarios'][] = [
                    'users_id' => (int)$r['users_id'],
                    'privilegio_id' => (int)$r['privilegio_id'],
                    'username' => trim((string)$r['username']),
                    'email' => trim((string)$r['email']),
                    'tipo_user_id' => (int)$r['tipo_user_id'],
                    'estado' => (int)$r['user_estado'],
                    'fecha_registro' => $r['user_fecha_registro'],
                    'empresa_id' => (int)$r['user_empresa_id'],
                    'empresa_nombre' => trim((string)$r['empresa_nombre']),
                    'server_customers_id' => (int)$r['server_customers_id']
                ];
            }
        }
        $empresas = [];
        if (apiTableExists($client, 'empresa')) {
            $ers = $client->query("SELECT empresa_id, razon_social, nombre, rtn, correo, telefono, celular, ubicacion, estado, fecha_registro FROM empresa ORDER BY nombre ASC");
            if ($ers) while ($e = $ers->fetch_assoc()) $empresas[] = $e;
        }
        $catalogos = [
            'puestos' => apiCatalog($client, 'puestos', 'puestos_id', 'nombre'),
            'privilegios' => apiCatalog($client, 'privilegio', 'privilegio_id', 'nombre'),
            'tipos_usuario' => apiCatalog($client, 'tipo_user', 'tipo_user_id', 'nombre')
        ];
        apiResponder(true, 'Información cargada.', [
            'cliente' => [
                'server_customers_id' => (int)$sc['server_customers_id'],
                'clientes_id' => (int)$sc['clientes_id'],
                'nombre' => $sc['cliente_nombre'],
                'rtn' => $sc['cliente_rtn'],
                'codigo_cliente' => $sc['codigo_cliente'],
                'db' => $sc['db'],
                'sistema' => $sc['sistema_nombre']
            ],
            'colaboradores' => array_values($map),
            'empresas' => $empresas,
            'catalogos' => $catalogos,
            'limites' => apiPlanLimits($client)
        ]);
    }

    if ($action === 'save_collaborator') {
        $id = apiInt('colaboradores_id');
        $nombre = apiStr('nombre');
        $identidad = apiStr('identidad');
        $telefono = apiStr('telefono');
        $puesto = apiInt('puestos_id');
        $empresa = apiInt('empresa_id');
        $estado = apiInt('estado', 1) === 2 ? 2 : 1;
        $fechaIngreso = apiStr('fecha_ingreso', date('Y-m-d'));
        if ($nombre === '') throw new Exception('El nombre del colaborador es obligatorio.');
        if (mb_strlen($nombre) > 100) throw new Exception('El nombre del colaborador no puede superar 100 caracteres.');
        if ($identidad !== '' && mb_strlen($identidad) > 13) throw new Exception('La identidad no puede superar 13 caracteres.');
        if (mb_strlen($telefono) > 8) throw new Exception('El teléfono del colaborador no puede superar 8 caracteres.');
        if ($puesto <= 0) throw new Exception('Debe seleccionar un puesto.');
        if ($empresa <= 0) throw new Exception('Debe seleccionar una empresa.');
        if ($identidad === '') $identidad = apiGenerateCollaboratorIdentity($client);

        if ($id > 0) {
            $dupStmt = $client->prepare("SELECT colaboradores_id FROM colaboradores WHERE identidad=? AND colaboradores_id<>? LIMIT 1");
            if ($dupStmt) {
                $dupStmt->bind_param('si', $identidad, $id);
                $dupStmt->execute();
                if ($dupStmt->get_result()->num_rows) throw new Exception('Ya existe otro colaborador con esa identidad.');
                $dupStmt->close();
            }
            $oldRow = null;
            $oldStmt = $client->prepare("SELECT puestos_id,nombre,identidad,estado,telefono,empresa_id,fecha_ingreso FROM colaboradores WHERE colaboradores_id=? LIMIT 1");
            if ($oldStmt) {
                $oldStmt->bind_param('i', $id);
                $oldStmt->execute();
                $oldRow = $oldStmt->get_result()->fetch_assoc();
                $oldStmt->close();
            }
            if (!$oldRow) throw new Exception('El colaborador que intenta editar ya no existe.');

            $oldIdentity = trim((string)$oldRow['identidad']);

            $stmt = $client->prepare("UPDATE colaboradores SET puestos_id=?,nombre=?,identidad=?,estado=?,telefono=?,empresa_id=?,fecha_ingreso=? WHERE colaboradores_id=?");
            if (!$stmt) throw new Exception('No se pudo preparar la edición del colaborador.');
            $stmt->bind_param('issisisi', $puesto, $nombre, $identidad, $estado, $telefono, $empresa, $fechaIngreso, $id);
            if (!$stmt->execute()) throw new Exception('No se pudo actualizar el colaborador: ' . $stmt->error);
            $stmt->close();

            try {
                apiSyncMainCollaborator(
                    $main,
                    compact('nombre','identidad','telefono','estado') + ['puestos_id'=>$puesto,'fecha_ingreso'=>$fechaIngreso],
                    $oldIdentity
                );
            } catch (Throwable $mirrorError) {
                $restore = $client->prepare("UPDATE colaboradores SET puestos_id=?,nombre=?,identidad=?,estado=?,telefono=?,empresa_id=?,fecha_ingreso=? WHERE colaboradores_id=?");
                if ($restore) {
                    $restorePuesto = (int)$oldRow['puestos_id'];
                    $restoreNombre = (string)$oldRow['nombre'];
                    $restoreIdentidad = (string)$oldRow['identidad'];
                    $restoreEstado = (int)$oldRow['estado'];
                    $restoreTelefono = (string)$oldRow['telefono'];
                    $restoreEmpresa = (int)$oldRow['empresa_id'];
                    $restoreFecha = (string)$oldRow['fecha_ingreso'];

                    $restore->bind_param(
                        'issisisi',
                        $restorePuesto,
                        $restoreNombre,
                        $restoreIdentidad,
                        $restoreEstado,
                        $restoreTelefono,
                        $restoreEmpresa,
                        $restoreFecha,
                        $id
                    );
                    $restore->execute();
                    $restore->close();
                }
                throw new Exception('No se actualizó el colaborador porque falló la sincronización con la base principal: '.$mirrorError->getMessage());
            }
            $notifyEmail = apiCollaboratorNotificationEmail($client, $id, $empresa);
            $mailSent = $notifyEmail !== '' ? apiSendChangeMail(
                $notifyEmail,
                $nombre,
                'Colaborador actualizado',
                'Se actualizaron los datos del colaborador '.$nombre.'.',
                $sc['cliente_nombre']
            ) : false;
            apiResponder(
                true,
                $mailSent
                    ? 'Colaborador actualizado y notificación enviada por correo.'
                    : 'Colaborador actualizado correctamente. No se encontró un correo válido para enviar la notificación.',
                ['colaboradores_id'=>$id, 'correo_enviado'=>$mailSent]
            );
        }

        $stmt = $client->prepare("SELECT colaboradores_id FROM colaboradores WHERE identidad=? LIMIT 1");
        $stmt->bind_param('s', $identidad); $stmt->execute();
        if ($stmt->get_result()->num_rows) throw new Exception('Ya existe un colaborador con esa identidad en la base del cliente.');
        $stmt->close();
        $id = apiNextId($client, 'colaboradores', 'colaboradores_id');
        $stmt = $client->prepare("INSERT INTO colaboradores (colaboradores_id,puestos_id,nombre,identidad,estado,telefono,empresa_id,fecha_registro,fecha_ingreso,fecha_egreso) VALUES (?,?,?,?,?,?,?,NOW(),?,'')");
        if (!$stmt) throw new Exception('No se pudo preparar el registro del colaborador.');
        $stmt->bind_param('iissisis', $id, $puesto, $nombre, $identidad, $estado, $telefono, $empresa, $fechaIngreso);
        if (!$stmt->execute()) throw new Exception('No se pudo crear el colaborador: ' . $stmt->error);
        $stmt->close();
        try {
            apiEnsureMainCollaborator($main, ['puestos_id'=>$puesto,'nombre'=>$nombre,'identidad'=>$identidad,'estado'=>$estado,'telefono'=>$telefono,'fecha_ingreso'=>$fechaIngreso]);
        } catch (Throwable $mirrorError) {
            $client->query("DELETE FROM colaboradores WHERE colaboradores_id=".(int)$id);
            throw new Exception('No se creó el colaborador porque falló la sincronización con la base principal: '.$mirrorError->getMessage());
        }

        $notifyEmail = apiCollaboratorNotificationEmail($client, $id, $empresa);
        $mailSent = $notifyEmail !== '' ? apiSendChangeMail(
            $notifyEmail,
            $nombre,
            'Colaborador creado',
            'Se registró el colaborador '.$nombre.' en IZZY.',
            $sc['cliente_nombre']
        ) : false;

        apiResponder(
            true,
            $mailSent
                ? 'Colaborador creado en ambas bases y notificación enviada por correo.'
                : 'Colaborador creado en la base del cliente y sincronizado con la base principal. No se encontró un correo válido para notificar.',
            ['colaboradores_id'=>$id, 'correo_enviado'=>$mailSent]
        );
    }

    if ($action === 'save_user') {
        $userId = apiInt('users_id');
        $collabId = apiInt('colaboradores_id');
        $email = strtolower(apiStr('email'));
        $collabNombre = apiStr('colaborador_nombre');
        $priv = apiInt('privilegio_id');
        $tipo = apiInt('tipo_user_id');
        $empresa = apiInt('empresa_id');
        $estado = apiInt('estado', 1) === 2 ? 2 : 1;
        if ($collabId <= 0) throw new Exception('Debe seleccionar un colaborador.');
        if ($collabNombre === '') throw new Exception('El nombre del colaborador es obligatorio.');
        if (mb_strlen($collabNombre) > 100) throw new Exception('El nombre del colaborador no puede superar 100 caracteres.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Ingrese un correo electrónico válido.');
        if (mb_strlen($email) > 45) throw new Exception('El correo no puede superar 45 caracteres.');
        if ($priv <= 0) throw new Exception('Debe seleccionar un nivel de privilegio.');
        if ($tipo <= 0) throw new Exception('Debe seleccionar un tipo de permisos.');
        if ($empresa <= 0) throw new Exception('Debe seleccionar una empresa.');

        $crs = $client->query("SELECT colaboradores_id,puestos_id,nombre,identidad,estado,telefono,empresa_id,fecha_ingreso FROM colaboradores WHERE colaboradores_id=".(int)$collabId." LIMIT 1");
        $collab = $crs ? $crs->fetch_assoc() : null;
        if (!$collab) throw new Exception('El colaborador seleccionado no existe.');

        // El nombre puede corregirse desde el modal de usuario. Se mantiene
        // sincronizado tanto en la base del cliente como en DB_MAIN.
        if (trim((string)$collab['nombre']) !== $collabNombre) {
            $nombreAnterior = (string)$collab['nombre'];
            $stmtNombre = $client->prepare("UPDATE colaboradores SET nombre=? WHERE colaboradores_id=?");
            if (!$stmtNombre) throw new Exception('No se pudo preparar la actualización del nombre del colaborador.');
            $stmtNombre->bind_param('si', $collabNombre, $collabId);
            if (!$stmtNombre->execute()) {
                $detalle = $stmtNombre->error;
                $stmtNombre->close();
                throw new Exception('No se pudo actualizar el nombre del colaborador: '.$detalle);
            }
            $stmtNombre->close();

            $collab['nombre'] = $collabNombre;
            try {
                apiSyncMainCollaborator($main, $collab);
            } catch (Throwable $renameMirrorError) {
                $restoreNombre = $client->prepare("UPDATE colaboradores SET nombre=? WHERE colaboradores_id=?");
                if ($restoreNombre) {
                    $restoreNombre->bind_param('si', $nombreAnterior, $collabId);
                    $restoreNombre->execute();
                    $restoreNombre->close();
                }
                $collab['nombre'] = $nombreAnterior;
                throw new Exception('No se cambió el nombre porque no se pudo sincronizar con la base principal: '.$renameMirrorError->getMessage());
            }
        }

        if ($userId > 0) {
            $stmt = $client->prepare("SELECT colaboradores_id,privilegio_id,username,password,email,tipo_user_id,estado,empresa_id,server_customers_id FROM users WHERE users_id=? LIMIT 1");
            if (!$stmt) throw new Exception('No se pudo consultar el usuario actual.');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$old) throw new Exception('El usuario no existe.');

            $username = apiUsername($email);
            $stmt = $client->prepare("UPDATE users SET colaboradores_id=?,privilegio_id=?,username=?,email=?,tipo_user_id=?,estado=?,empresa_id=?,server_customers_id=? WHERE users_id=?");
            if (!$stmt) throw new Exception('No se pudo preparar la actualización del usuario.');
            $stmt->bind_param('iissiiiii', $collabId, $priv, $username, $email, $tipo, $estado, $empresa, $serverCustomerId, $userId);

            if (!$stmt->execute()) {
                throw new Exception('No se pudo actualizar el usuario: ' . $stmt->error);
            }
            $stmt->close();

            try {
                $updatedMirror = apiUpdateMainMirrorUser(
                    $main,
                    $serverCustomerId,
                    $old['email'],
                    ['email'=>$email,'estado'=>$estado]
                );

                apiSyncMainCollaborator($main, $collab);

                if ($updatedMirror === 0 && !apiGetMainMirrorUser($main, $serverCustomerId, $email)) {
                    $mainCollabId = apiEnsureMainCollaborator($main, $collab);
                    apiCreateMainMirrorUser($main, $serverCustomerId, $mainCollabId, [
                        'email'=>$email,
                        'password'=>$old['password'],
                        'estado'=>$estado,
                        'privilegio_id'=>$priv,
                        'tipo_user_id'=>$tipo
                    ]);
                }
            } catch (Throwable $mirrorError) {
                $restore = $client->prepare("UPDATE users SET colaboradores_id=?,privilegio_id=?,username=?,password=?,email=?,tipo_user_id=?,estado=?,empresa_id=?,server_customers_id=? WHERE users_id=?");
                if ($restore) {
                    $restoreCollab = (int)$old['colaboradores_id'];
                    $restorePriv = (int)$old['privilegio_id'];
                    $restoreUsername = (string)$old['username'];
                    $restorePassword = (string)$old['password'];
                    $restoreEmail = (string)$old['email'];
                    $restoreTipo = (int)$old['tipo_user_id'];
                    $restoreEstado = (int)$old['estado'];
                    $restoreEmpresa = (int)$old['empresa_id'];
                    $restoreServerCustomer = (int)$old['server_customers_id'];

                    $restore->bind_param(
                        'iisssiiiii',
                        $restoreCollab,
                        $restorePriv,
                        $restoreUsername,
                        $restorePassword,
                        $restoreEmail,
                        $restoreTipo,
                        $restoreEstado,
                        $restoreEmpresa,
                        $restoreServerCustomer,
                        $userId
                    );
                    $restore->execute();
                    $restore->close();
                }

                throw new Exception('No se actualizó el usuario porque falló la sincronización con la base principal: '.$mirrorError->getMessage());
            }

            $mailSent = apiSendChangeMail(
                $email,
                $collab['nombre'],
                'Usuario actualizado',
                'Se actualizaron los datos de acceso, empresa asignada o estado de tu usuario IZZY.',
                $sc['cliente_nombre']
            );

            apiResponder(
                true,
                $mailSent
                    ? 'Usuario actualizado en ambas bases y notificación enviada al correo.'
                    : 'Usuario actualizado y sincronizado con la base principal. No se pudo confirmar el envío del correo.',
                ['users_id'=>$userId, 'correo_enviado'=>$mailSent]
            );
        }

        apiAssertCanCreateUser($client);

        $stmt = $client->prepare("SELECT users_id FROM users WHERE colaboradores_id=? OR LOWER(email)=LOWER(?) LIMIT 1");
        $stmt->bind_param('is',$collabId,$email); $stmt->execute();
        if ($stmt->get_result()->num_rows) throw new Exception('El colaborador ya tiene usuario o el correo ya está registrado.');
        $stmt->close();

        $plain = mainModel::generar_password_complejo();
        $encrypted = mainModel::encryption($plain);
        $username = apiUsername($email);
        $userId = apiNextId($client, 'users', 'users_id');
        $stmt = $client->prepare("INSERT INTO users (users_id,colaboradores_id,privilegio_id,username,password,email,tipo_user_id,estado,fecha_registro,empresa_id,server_customers_id) VALUES (?,?,?,?,?,?,?,?,NOW(),?,?)");
        if (!$stmt) throw new Exception('No se pudo preparar el registro del usuario.');
        $stmt->bind_param('iiisssiiii',$userId,$collabId,$priv,$username,$encrypted,$email,$tipo,$estado,$empresa,$serverCustomerId);
        if (!$stmt->execute()) throw new Exception('No se pudo crear el usuario: ' . $stmt->error);
        $stmt->close();

        try {
            $mainCollabId = apiEnsureMainCollaborator($main, $collab);
            apiCreateMainMirrorUser($main, $serverCustomerId, $mainCollabId, [
                'privilegio_id'=>$priv,'tipo_user_id'=>$tipo,'email'=>$email,'password'=>$encrypted,'estado'=>$estado
            ]);
        } catch (Throwable $mirrorError) {
            $client->query("DELETE FROM users WHERE users_id=".(int)$userId);
            throw new Exception('No se creó el usuario porque falló la sincronización con la base principal: '.$mirrorError->getMessage());
        }

        $mailSent = apiSendPasswordMail($email, $collab['nombre'], $plain);
        apiResponder(true, $mailSent ? 'Usuario creado y credenciales enviadas al correo.' : 'Usuario creado y sincronizado. No se pudo confirmar el envío del correo; puede restablecer la contraseña desde Acciones.', ['users_id'=>$userId,'correo_enviado'=>$mailSent]);
    }

    if ($action === 'reset_password') {
        $userId = apiInt('users_id');
        if ($userId <= 0) throw new Exception('No se recibió el usuario.');
        $stmt = $client->prepare("SELECT u.email,u.colaboradores_id,u.privilegio_id,u.tipo_user_id,u.estado,c.nombre,c.identidad,c.telefono,c.puestos_id,c.fecha_ingreso,c.empresa_id FROM users u INNER JOIN colaboradores c ON c.colaboradores_id=u.colaboradores_id WHERE u.users_id=? LIMIT 1");
        $stmt->bind_param('i',$userId); $stmt->execute(); $info=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$info || !filter_var($info['email'], FILTER_VALIDATE_EMAIL)) throw new Exception('El usuario no tiene un correo válido para enviar la nueva contraseña.');
        $plain = mainModel::generar_password_complejo();
        $encrypted = mainModel::encryption($plain);
        $stmt = $client->prepare("UPDATE users SET password=? WHERE users_id=?");
        $stmt->bind_param('si',$encrypted,$userId);
        if (!$stmt->execute()) throw new Exception('No se pudo actualizar la contraseña local.');
        $stmt->close();
        $stmt = $main->prepare("UPDATE users SET password=? WHERE server_customers_id=? AND LOWER(email)=LOWER(?)");
        if (!$stmt) throw new Exception('No se pudo preparar la actualización espejo de contraseña.');
        $stmt->bind_param('sis',$encrypted,$serverCustomerId,$info['email']);
        if (!$stmt->execute()) throw new Exception('No se pudo sincronizar la contraseña en la base principal.');
        $affectedMain = $stmt->affected_rows;
        $stmt->close();
        if ($affectedMain === 0 && !apiGetMainMirrorUser($main, $serverCustomerId, $info['email'])) {
            $mainCollabId = apiEnsureMainCollaborator($main, [
                'puestos_id'=>$info['puestos_id'],'nombre'=>$info['nombre'],'identidad'=>$info['identidad'],'estado'=>1,
                'telefono'=>$info['telefono'],'fecha_ingreso'=>$info['fecha_ingreso']
            ]);
            apiCreateMainMirrorUser($main, $serverCustomerId, $mainCollabId, [
                'email'=>$info['email'],'password'=>$encrypted,'estado'=>$info['estado'],'privilegio_id'=>$info['privilegio_id'],'tipo_user_id'=>$info['tipo_user_id']
            ]);
        }
        $mailSent = apiSendPasswordMail($info['email'], $info['nombre'], $plain);
        apiResponder(true, $mailSent ? 'Contraseña restablecida y enviada al correo del usuario.' : 'La contraseña se restableció, pero no se pudo confirmar el envío del correo.', ['correo_enviado'=>$mailSent]);
    }

    if ($action === 'save_company') {
        $id = apiInt('empresa_id');
        $razon = apiStr('razon_social');
        $nombre = apiStr('nombre');
        $rtn = apiStr('rtn');
        $correo = strtolower(apiStr('correo'));
        $telefono = apiStr('telefono');
        $celular = apiStr('celular');
        $ubicacion = apiStr('ubicacion');
        $estado = apiInt('estado',1) === 0 ? 0 : 1;
        if ($razon === '' || $nombre === '') throw new Exception('Razón social y nombre comercial son obligatorios.');
        if (mb_strlen($razon) > 254 || mb_strlen($nombre) > 254) throw new Exception('Razón social y nombre comercial no pueden superar 254 caracteres.');
        if (mb_strlen($rtn) > 14) throw new Exception('El RTN no puede superar 14 caracteres.');
        if (mb_strlen($telefono) > 8 || mb_strlen($celular) > 8) throw new Exception('Teléfono y celular no pueden superar 8 caracteres.');
        if (mb_strlen($ubicacion) > 150) throw new Exception('La ubicación no puede superar 150 caracteres.');
        if ($correo !== '' && !filter_var($correo,FILTER_VALIDATE_EMAIL)) throw new Exception('El correo de la empresa no es válido.');
        if (mb_strlen($correo) > 50) throw new Exception('El correo de la empresa no puede superar 50 caracteres.');

        if ($id > 0) {
            $stmt=$client->prepare("UPDATE empresa SET razon_social=?,nombre=?,rtn=?,correo=?,telefono=?,celular=?,ubicacion=?,estado=? WHERE empresa_id=?");
            if(!$stmt) throw new Exception('No se pudo preparar la edición de empresa.');
            $stmt->bind_param('sssssssii',$razon,$nombre,$rtn,$correo,$telefono,$celular,$ubicacion,$estado,$id);
            if(!$stmt->execute()) throw new Exception('No se pudo actualizar la empresa: '.$stmt->error);
            $stmt->close();
            $mailSent = filter_var($correo, FILTER_VALIDATE_EMAIL)
                ? apiSendChangeMail($correo, $nombre, 'Empresa actualizada', 'Se actualizaron los datos de la empresa '.$nombre.'.', $sc['cliente_nombre'])
                : false;
            apiResponder(
                true,
                $mailSent
                    ? 'Empresa actualizada y notificación enviada al correo.'
                    : 'Empresa actualizada correctamente en la base del cliente. No hay un correo válido para notificar.',
                ['empresa_id'=>$id, 'correo_enviado'=>$mailSent]
            );
        }

        apiAssertCanCreateCompany($client);

        $id=apiNextId($client,'empresa','empresa_id');
        $creador=isset($_SESSION['colaboradores_id_sd'])?(int)$_SESSION['colaboradores_id_sd']:1;
        $stmt=$client->prepare("INSERT INTO empresa (empresa_id,razon_social,nombre,otra_informacion,eslogan,celular,telefono,correo,logotipo,rtn,ubicacion,facebook,sitioweb,horario,estado,colaboradores_id,fecha_registro,firma_documento,MostrarFirma) VALUES (?,?,?,'','',?,?,?,'image_preview.png',?,?,'','','',?,?,NOW(),'',0)");
        if(!$stmt) throw new Exception('No se pudo preparar el registro de empresa: '.$client->error);
        $stmt->bind_param('isssssssii',$id,$razon,$nombre,$celular,$telefono,$correo,$rtn,$ubicacion,$estado,$creador);
        if(!$stmt->execute()) throw new Exception('No se pudo crear la empresa: '.$stmt->error);
        $stmt->close();
        $mailSent = filter_var($correo, FILTER_VALIDATE_EMAIL)
            ? apiSendChangeMail($correo, $nombre, 'Empresa creada', 'Se registró la empresa '.$nombre.' dentro de IZZY.', $sc['cliente_nombre'])
            : false;
        apiResponder(
            true,
            $mailSent
                ? 'Empresa creada y notificación enviada al correo.'
                : 'Empresa creada correctamente en la base del cliente. No hay un correo válido para notificar.',
            ['empresa_id'=>$id, 'correo_enviado'=>$mailSent]
        );
    }

    throw new Exception('Acción no reconocida.');

} catch (Throwable $e) {
    error_log('IZZY administrarCliente ['.$action.']: ' . $e->getMessage());
    apiResponder(false, apiFriendlyError($e, $action));
} finally {
    if ($client instanceof mysqli) $client->close();
    if ($main instanceof mysqli) $main->close();
}
