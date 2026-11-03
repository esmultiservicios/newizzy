<?php
/**
 * IZZY API v1 - Servicio de autenticación móvil.
 * Las conexiones se invocan como métodos de instancia para PHP 8.2.
 * No modifica los modelos ni el controlador de login del sitio web.
 */
$peticionAjax = true;
require_once dirname(__DIR__) . '/configGenerales.php';
require_once dirname(__DIR__) . '/mainModel.php';

/** Reutiliza las mismas consultas y el mismo cifrado del login web. */
final class IzzyApiLoginModel extends mainModel
{
    public function buscarCliente(string $email)
    {
        $cn = $this->connectionDBLocal(DB_MAIN);
        try {
            $sql = "SELECT COALESCE(s.server_customers_id, 0) AS server_customers_id,
                           COALESCE(s.db, ?) AS db,
                           COALESCE(s.codigo_cliente, 0) AS codigo_cliente,
                           COALESCE(s.clientes_id, 0) AS clientes_id,
                           COALESCE(s.planes_id, 0) AS planes_id,
                           COALESCE(s.validar, 1) AS validar,
                           COALESCE(s.estado, 0) AS estado
                    FROM users AS u
                    LEFT JOIN server_customers AS s
                      ON u.server_customers_id = s.server_customers_id
                    WHERE BINARY u.email = ? LIMIT 1";
            $stmt = $cn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('No se pudo consultar el correo.');
            }
            $default = DB_MAIN;
            $stmt->bind_param('ss', $default, $email);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result[0] ?? null;
        } finally {
            $cn->close();
        }
    }

    public function buscarClientePorUsuario(string $username)
    {
        $cn = $this->connectionDBLocal(DB_MAIN);
        try {
            $sql = "SELECT COALESCE(s.server_customers_id, 0) AS server_customers_id,
                           COALESCE(s.db, ?) AS db,
                           COALESCE(s.codigo_cliente, 0) AS codigo_cliente,
                           COALESCE(s.clientes_id, 0) AS clientes_id,
                           COALESCE(s.planes_id, 0) AS planes_id,
                           COALESCE(s.validar, 1) AS validar,
                           COALESCE(s.estado, 0) AS estado
                    FROM users AS u
                    LEFT JOIN server_customers AS s
                      ON u.server_customers_id = s.server_customers_id
                    WHERE BINARY u.username = ? LIMIT 1";
            $stmt = $cn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('No se pudo consultar el usuario.');
            }
            $default = DB_MAIN;
            $stmt->bind_param('ss', $default, $username);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result[0] ?? null;
        } finally {
            $cn->close();
        }
    }

    public function autenticar(string $identificador, string $cifrado, string $db, bool $esCorreo)
    {
        $cn = $this->connectionDBLocal($db);
        try {
            $columna = $esCorreo ? 'u.email' : 'u.username';
            $sql = "SELECT u.*, tu.nombre AS cuentaTipo, c.identidad
                    FROM users AS u
                    INNER JOIN tipo_user AS tu ON u.tipo_user_id = tu.tipo_user_id
                    INNER JOIN colaboradores AS c ON u.colaboradores_id = c.colaboradores_id
                    WHERE BINARY {$columna} = ? AND u.password = ? AND u.estado = 1
                    LIMIT 1";
            $stmt = $cn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('No se pudo validar el inicio de sesión.');
            }
            $stmt->bind_param('ss', $identificador, $cifrado);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows[0] ?? null;
        } finally {
            $cn->close();
        }
    }

    /** Conserva el criterio vigente de pagos vencidos del modelo web. */
    public function pagosVencidos(string $db): bool
    {
        $cn = $this->connectionDBLocal(DB_MAIN);
        try {
            $fechaEfectiva = "(CASE WHEN cc.fecha = LAST_DAY(cc.fecha)
                AND DATE(cc.fecha_registro) = cc.fecha
                AND TIME(cc.fecha_registro) >= '23:00:00'
                THEN DATE_ADD(cc.fecha, INTERVAL 1 DAY) ELSE cc.fecha END)";
            $sql = "SELECT 1 FROM server_customers AS sc
                    INNER JOIN clientes AS c ON sc.clientes_id = c.clientes_id
                    INNER JOIN cobrar_clientes AS cc ON cc.clientes_id = sc.clientes_id
                    WHERE cc.estado = 1 AND sc.db = ?
                    AND ({$fechaEfectiva} < DATE_FORMAT(CURDATE(), '%Y-%m-01')
                         OR (DAY(CURDATE()) >= 16
                             AND YEAR({$fechaEfectiva}) = YEAR(CURDATE())
                             AND MONTH({$fechaEfectiva}) = MONTH(CURDATE())))
                    LIMIT 1";
            $stmt = $cn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('No se pudo consultar la cuenta.');
            }
            $stmt->bind_param('s', $db);
            $stmt->execute();
            $pendiente = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            return $pendiente;
        } finally {
            $cn->close();
        }
    }
}

final class IzzyApiAuthService {
    private IzzyApiLoginModel $modelo;
    public function __construct() { $this->modelo = new IzzyApiLoginModel(); }

    public function limitarIntentos(string $correo, string $ip): void {
        // Ventana fija de 15 minutos, max. 8 intentos por IP+correo.
        $identificador=hash('sha256',strtolower(trim($correo)).'|'.$ip);
        $conn=$this->modelo->connectionDBLocal(DB_MAIN);
        try {
            $stmt=$conn->prepare('INSERT INTO api_auth_rate_limits (identifier_hash, window_start, attempts) VALUES (?, NOW(), 1) ON DUPLICATE KEY UPDATE attempts=IF(window_start < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, attempts+1), window_start=IF(window_start < DATE_SUB(NOW(), INTERVAL 15 MINUTE), NOW(), window_start)');
            if (!$stmt) throw new RuntimeException('No se configuró el límite de intentos del API.');
            $stmt->bind_param('s',$identificador); $stmt->execute(); $stmt->close();
            $stmt=$conn->prepare('SELECT attempts FROM api_auth_rate_limits WHERE identifier_hash=?');
            $stmt->bind_param('s',$identificador); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
            if ((int)($row['attempts']??0)>8) throw new OverflowException('Demasiados intentos. Vuelva a intentarlo en 15 minutos.');
        } finally { $conn->close(); }
    }

    public function login(string $correo, string $clave): array {
        $correo = trim($correo);
        if ($correo === '' || $clave === '' || strlen($correo) > 254 || strlen($clave) > 4096) {
            throw new InvalidArgumentException('Ingrese su correo y contraseña.');
        }
        $esCorreo=filter_var($correo,FILTER_VALIDATE_EMAIL)!==false;
        $cliente = $esCorreo ? $this->modelo->buscarCliente($correo) : $this->modelo->buscarClientePorUsuario($correo);
        if (!$cliente) throw new DomainException('Credenciales inválidas.');
        $db = trim((string)($cliente['db'] ?? '')) ?: DB_MAIN;
        // Nunca aceptar nombres de DB enviados por el dispositivo.
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $db)) throw new DomainException('No se pudo validar la cuenta.');
        if ($db !== DB_MAIN && (int)($cliente['estado'] ?? 0) !== 1) throw new DomainException('La cuenta del cliente no está activa.');
        $GLOBALS['db'] = $db;
        // Resolver el tenant desde DB_MAIN y validar contra su base asignada.
        $cifrado=$this->modelo->encryption($clave);
        $u = $this->modelo->autenticar($correo, $cifrado, $db, $esCorreo);
        if (!$u) throw new DomainException('Credenciales inválidas.');
        $soloLectura = false;
        if ($db !== DB_MAIN && (int)($cliente['validar'] ?? 1) !== 2) {
            $soloLectura = $this->modelo->pagosVencidos($db);
        }
        $token = 'izzy_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $conn = $this->modelo->connectionDBLocal(DB_MAIN);
        try {
            $q = $conn->prepare('INSERT INTO api_auth_tokens (token_hash, users_id, empresa_id, server_customers_id, db_cliente, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))');
            if (!$q) throw new RuntimeException('No se encuentra configurado el almacenamiento de tokens.');
            $uid=(int)$u['users_id']; $empresa=(int)$u['empresa_id']; $sc=(int)($cliente['server_customers_id'] ?? 0);
            $q->bind_param('siiis', $hash, $uid, $empresa, $sc, $db);
            if (!$q->execute()) throw new RuntimeException('No fue posible crear la sesión móvil.');
            $q->close();
        } finally { $conn->close(); }
        $perfil = $this->perfilMovil($db, $u, $cliente, $soloLectura);
        return [
            'access_token'=>$token, 'token_type'=>'Bearer', 'expires_in'=>3600,
            'usuario'=>$perfil['usuario'],
            'cliente'=>$perfil['cliente'],
            'permisos'=>$perfil['permisos'],
            'solo_lectura_pago'=>$soloLectura
        ];
    }


    /** La app debe construir sus menús con estos permisos; el servidor deberá
     * volver a comprobarlos en CADA endpoint protegido (nunca confiar en el móvil). */
    private function obtenerPermisos(string $db, int $privilegio, int $plan): array {
        $salida = ['menus'=>[], 'submenus'=>[], 'submenus1'=>[]];
        if ($privilegio <= 0 || $plan <= 0) return $salida;
        $cn = $this->modelo->connectionDBLocal($db);
        try {
            $consultas = [
                'menus' => 'SELECT m.name AS clave FROM acceso_menu a INNER JOIN menu m ON m.menu_id=a.menu_id INNER JOIN menu_plan p ON p.menu_id=m.menu_id AND p.planes_id=? WHERE a.privilegio_id=? AND a.estado=1 AND p.estado=1 ORDER BY m.menu_id',
                'submenus' => 'SELECT m.name AS clave FROM acceso_submenu a INNER JOIN submenu m ON m.submenu_id=a.submenu_id INNER JOIN submenu_plan p ON p.submenu_id=m.submenu_id AND p.planes_id=? WHERE a.privilegio_id=? AND a.estado=1 AND p.estado=1 ORDER BY m.submenu_id',
                'submenus1' => 'SELECT m.name AS clave FROM acceso_submenu1 a INNER JOIN submenu1 m ON m.submenu1_id=a.submenu1_id INNER JOIN submenu1_plan p ON p.submenu1_id=m.submenu1_id AND p.planes_id=? WHERE a.privilegio_id=? AND a.estado=1 AND p.estado=1 ORDER BY m.submenu1_id'
            ];
            foreach ($consultas as $tipo=>$sql) {
                $stmt=$cn->prepare($sql);
                if (!$stmt) throw new RuntimeException('No se pudo consultar los permisos de '.$tipo);
                $stmt->bind_param('ii',$plan,$privilegio);
                if (!$stmt->execute()) throw new RuntimeException('No se pudieron consultar los permisos.');
                $rs=$stmt->get_result();
                while ($fila=$rs->fetch_assoc()) $salida[$tipo][]=(string)$fila['clave'];
                $stmt->close();
            }
        } finally { $cn->close(); }
        return $salida;
    }

    private function perfilMovil(string $db, array $u, array $cliente, bool $soloLectura): array {
        $plan=(int)($cliente['planes_id']??0);
        $privilegio=(int)($u['privilegio_id']??0);
        return [
            'usuario'=>[
                'id'=>(int)$u['users_id'],
                'nombre'=>(string)($u['nombre']??$u['username']??''),
                'username'=>(string)($u['username']??''),
                'correo'=>(string)($u['email']??''),
                'empresa_id'=>(int)$u['empresa_id'],
                'colaborador_id'=>(int)($u['colaboradores_id']??0),
                'privilegio_id'=>$privilegio,
                'tipo_user_id'=>(int)($u['tipo_user_id']??0),
                'tipo_cuenta'=>(string)($u['cuentaTipo']??'')
            ],
            'cliente'=>[
                'codigo'=>(string)($cliente['codigo_cliente']??''),
                'plan_id'=>$plan,
                'server_customers_id'=>(int)($cliente['server_customers_id']??0),
                'solo_lectura_pago'=>$soloLectura
            ],
            'permisos'=>$this->obtenerPermisos($db,$privilegio,$plan)
        ];
    }

    /** Obtiene permisos actuales sin una segunda solicitud de contraseña. */
    public function miPerfil(string $token): array {
        $sesion=$this->autenticarToken($token);
        $db=$sesion['db_cliente'];
        $cn=$this->modelo->connectionDBLocal($db);
        try {
            $stmt=$cn->prepare('SELECT u.*, t.nombre AS cuentaTipo FROM users u INNER JOIN tipo_user t ON t.tipo_user_id=u.tipo_user_id WHERE u.users_id=? AND u.empresa_id=? AND u.estado=1 LIMIT 1');
            $id=(int)$sesion['users_id']; $empresa=(int)$sesion['empresa_id'];
            $stmt->bind_param('ii',$id,$empresa);$stmt->execute();$u=$stmt->get_result()->fetch_assoc();$stmt->close();
        } finally {$cn->close();}
        if (!$u) throw new DomainException('Usuario no autorizado.');
        $principal=$this->modelo->connectionDBLocal(DB_MAIN);
        try {
            $stmt=$principal->prepare('SELECT server_customers_id, codigo_cliente, planes_id, validar, estado FROM server_customers WHERE db=? LIMIT 1');
            $stmt->bind_param('s',$db);$stmt->execute();$cliente=$stmt->get_result()->fetch_assoc();$stmt->close();
        } finally {$principal->close();}
        if ($db !== DB_MAIN && (!$cliente || (int)$cliente['estado']!==1)) throw new DomainException('Cuenta no autorizada.');
        if (!$cliente) $cliente=['planes_id'=>0];
        $soloLectura=false;
        if ($db !== DB_MAIN && (int)($cliente['validar']??1)!==2) {
            $GLOBALS['db']=$db;
            $soloLectura = $this->modelo->pagosVencidos($db);
        }
        return $this->perfilMovil($db,$u,$cliente,$soloLectura);
    }

    public function autenticarToken(string $token): array {
        if (!preg_match('/^izzy_[a-f0-9]{64}$/D', $token)) throw new DomainException('Token inválido.');
        $hash = hash('sha256', $token);
        $conn = $this->modelo->connectionDBLocal(DB_MAIN);
        try {
            $stmt=$conn->prepare('SELECT users_id, empresa_id, server_customers_id, db_cliente FROM api_auth_tokens WHERE token_hash=? AND revoked_at IS NULL AND expires_at > NOW() LIMIT 1');
            $stmt->bind_param('s',$hash); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
        } finally { $conn->close(); }
        if (!$row) throw new DomainException('La sesión móvil venció o fue revocada.');
        $db=(string)$row['db_cliente'];
        if (!preg_match('/^[a-zA-Z0-9_]+$/D',$db)) throw new DomainException('Sesión inválida.');
        $GLOBALS['db']=$db;
        $dbconn=$this->modelo->connectionDBLocal($db);
        try {
            $stmt=$dbconn->prepare('SELECT users_id, empresa_id, estado, privilegio_id FROM users WHERE users_id=? AND empresa_id=? LIMIT 1');
            $id=(int)$row['users_id']; $eid=(int)$row['empresa_id'];
            $stmt->bind_param('ii',$id,$eid); $stmt->execute(); $actual=$stmt->get_result()->fetch_assoc(); $stmt->close();
        } finally { $dbconn->close(); }
        if (!$actual || (int)$actual['estado']!==1) throw new DomainException('Usuario no autorizado.');
        return ['users_id'=>$id, 'empresa_id'=>$eid, 'privilegio_id'=>(int)$actual['privilegio_id'], 'db_cliente'=>$db];
    }

    public function logout(string $token): void {
        $this->autenticarToken($token);
        $hash=hash('sha256',$token);
        $conn=$this->modelo->connectionDBLocal(DB_MAIN);
        try {
            $q=$conn->prepare('UPDATE api_auth_tokens SET revoked_at=NOW() WHERE token_hash=? AND revoked_at IS NULL');
            $q->bind_param('s',$hash); $q->execute(); $q->close();
        } finally { $conn->close(); }
    }
}
