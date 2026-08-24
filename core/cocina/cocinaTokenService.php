<?php
// core/cocina/cocinaTokenService.php
declare(strict_types=1);
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/cocinaTokenConfig.php';

final class CocinaTokenService
{
    private static function master(): mysqli {
        $model = new mainModel();
        return $model->connectionLogin();
    }
    public static function masterDbName(): string {
        // Fuente de verdad: la misma conexión que IZZY utiliza para DB_MAIN.
        // Esto evita depender de que DB_MAIN exista como constante/global con un nombre específico.
        $master=self::master();
        $rs=$master->query("SELECT DATABASE() AS db_actual");
        if($rs){
            $row=$rs->fetch_assoc();
            $name=trim((string)($row['db_actual']??''));
            if($name!=='' && preg_match('/^[A-Za-z0-9_]+$/',$name)) return $name;
        }

        // Fallback defensivo únicamente si el driver no devolviera DATABASE().
        $candidates=[
            $GLOBALS['DB_MAIN'] ?? '',
            defined('DB_MAIN') ? DB_MAIN : ''
        ];
        foreach($candidates as $candidate){
            $name=trim((string)$candidate);
            if($name!=='' && preg_match('/^[A-Za-z0-9_]+$/',$name)) return $name;
        }
        throw new RuntimeException('No se pudo identificar DB_MAIN desde la conexión principal de IZZY.');
    }

    public static function tenant(string $db): mysqli {
        if ($db === '' || !preg_match('/^[A-Za-z0-9_]+$/', $db)) throw new RuntimeException('Base de datos de empresa inválida.');
        $model = new mainModel();
        return $model->connectionDBLocal($db);
    }
    public static function tenantDbForServerCustomer(int $serverCustomerId): string {
        if($serverCustomerId===0){
            $main=self::masterDbName();
            if($main==='') throw new RuntimeException('No se pudo resolver la base principal de IZZY.');
            return $main;
        }
        if($serverCustomerId<0) throw new RuntimeException('Cliente principal inválido.');
        $db=self::master();
        $st=$db->prepare("SELECT db FROM server_customers WHERE server_customers_id=? LIMIT 1");
        if(!$st) throw new RuntimeException('No se pudo resolver la base de datos del cliente.');
        $st->bind_param('i',$serverCustomerId);
        $st->execute();
        $rs=$st->get_result();
        $row=$rs?$rs->fetch_assoc():null;
        $st->close();
        $tenantDb=trim((string)($row['db']??''));
        if($tenantDb==='' || !preg_match('/^[A-Za-z0-9_]+$/',$tenantDb)){
            throw new RuntimeException('No se pudo resolver una base de datos válida para esta empresa.');
        }
        return $tenantDb;
    }
    public static function generateToken(): string { return bin2hex(random_bytes(IZZY_COCINA_TOKEN_BYTES)); }
    public static function tokenHash(string $token): string { return hash('sha256', strtolower(trim($token))); }
    private static function cipherKey(): string {
        $hex = trim((string)IZZY_COCINA_TOKEN_CIPHER_KEY);
        if (!preg_match('/^[a-f0-9]{64}$/i', $hex)) throw new RuntimeException('La clave de cifrado de Cocina no es válida.');
        return hex2bin($hex);
    }
    public static function encryptToken(string $token): string {
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($token, 'aes-256-gcm', self::cipherKey(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($cipher === false) throw new RuntimeException('No se pudo proteger el token de Cocina.');
        return base64_encode($iv . $tag . $cipher);
    }
    public static function decryptToken(?string $payload): string {
        if (!$payload) return '';
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 29) return '';
        $iv=substr($raw,0,12); $tag=substr($raw,12,16); $cipher=substr($raw,28);
        $plain=openssl_decrypt($cipher,'aes-256-gcm',self::cipherKey(),OPENSSL_RAW_DATA,$iv,$tag,'');
        return is_string($plain) ? $plain : '';
    }

    private static function base64UrlEncode(string $raw): string {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
    private static function base64UrlDecode(string $value): string {
        $value=strtr(trim($value), '-_', '+/');
        $pad=strlen($value)%4;
        if($pad) $value.=str_repeat('=',4-$pad);
        $raw=base64_decode($value,true);
        return $raw===false?'':$raw;
    }
    public static function createAdminContext(string $tenantDb,int $empresaId,string $sessionId): string {
        $tenantDb=trim($tenantDb);
        if($tenantDb==='' || !preg_match('/^[A-Za-z0-9_]+$/',$tenantDb) || $empresaId<=0 || $sessionId==='') return '';
        $payload=json_encode([
            'db'=>$tenantDb,
            'empresa_id'=>$empresaId,
            'sid'=>$sessionId,
            'exp'=>time()+7200
        ],JSON_UNESCAPED_SLASHES);
        if(!is_string($payload)) return '';
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt($payload,'aes-256-gcm',self::cipherKey(),OPENSSL_RAW_DATA,$iv,$tag,'IZZY_COCINA_ADMIN',16);
        if($cipher===false) return '';
        return self::base64UrlEncode($iv.$tag.$cipher);
    }
    public static function resolveAdminContext(string $context,string $sessionId,int $empresaSesion): array {
        $raw=self::base64UrlDecode($context);
        if($raw==='' || strlen($raw)<29) throw new DomainException('Contexto de Cocina inválido.',403);
        $iv=substr($raw,0,12); $tag=substr($raw,12,16); $cipher=substr($raw,28);
        $plain=openssl_decrypt($cipher,'aes-256-gcm',self::cipherKey(),OPENSSL_RAW_DATA,$iv,$tag,'IZZY_COCINA_ADMIN');
        $data=is_string($plain)?json_decode($plain,true):null;
        if(!is_array($data)) throw new DomainException('Contexto de Cocina inválido.',403);
        $db=trim((string)($data['db']??''));
        $emp=(int)($data['empresa_id']??0);
        $sid=(string)($data['sid']??'');
        $exp=(int)($data['exp']??0);
        if($db==='' || !preg_match('/^[A-Za-z0-9_]+$/',$db) || $emp<=0 || $exp<time()) throw new DomainException('El contexto de Cocina expiró o es inválido.',403);
        if($sessionId==='' || $sid==='' || !hash_equals($sessionId,$sid)) throw new DomainException('El contexto de Cocina no pertenece a la sesión activa.',403);
        if($empresaSesion<=0 || $emp!==$empresaSesion) throw new DomainException('La empresa del contexto no coincide con la sesión activa.',403);
        return ['db'=>$db,'empresa_id'=>$emp];
    }
    public static function serverCustomerForTenantDb(string $tenantDb): int {
        $tenantDb=trim($tenantDb);
        if($tenantDb==='' || !preg_match('/^[A-Za-z0-9_]+$/',$tenantDb)){
            throw new RuntimeException('Base de datos de sesión inválida.');
        }

        // REGLA IZZY:
        // La instalación principal usa DB_MAIN y NO es un cliente de server_customers.
        // Por lo tanto, si la BD autenticada es DB_MAIN, jamás intentamos buscarla ahí.
        $mainDb=self::masterDbName();
        if(strcasecmp($tenantDb,$mainDb)===0){
            return 0; // Identificador interno reservado exclusivamente para DB_MAIN.
        }

        // Solo una BD distinta de DB_MAIN debe corresponder a un cliente real.
        $master=self::master();
        $st=$master->prepare(
            'SELECT server_customers_id, db FROM server_customers '
            .'WHERE LOWER(TRIM(db))=LOWER(TRIM(?)) LIMIT 2'
        );
        if(!$st) throw new RuntimeException('No se pudo consultar server_customers.');

        $st->bind_param('s',$tenantDb);
        $st->execute();
        $rs=$st->get_result();
        $rows=[];
        while($rs && ($r=$rs->fetch_assoc())) $rows[]=$r;
        $st->close();

        if(count($rows)===1){
            $id=(int)($rows[0]['server_customers_id']??0);
            if($id<=0) throw new RuntimeException('El registro del cliente en server_customers no tiene un identificador válido.');
            return $id;
        }
        if(count($rows)>1){
            throw new RuntimeException('La base activa está vinculada a más de un cliente en server_customers.');
        }

        throw new RuntimeException(
            'La base activa no es DB_MAIN y tampoco está registrada en server_customers.'
        );
    }

    public static function requireSchema(): void {
        $r=self::master()->query("SHOW TABLES LIKE 'restaurante_pantalla_accesos'");
        if(!$r || $r->num_rows===0) throw new RuntimeException('Falta ejecutar SQL_MASTER_COCINA_TOKEN.sql.');
    }
    public static function resolve(string $rawToken,string $deviceSecret=''): array {
        $token=strtolower(trim($rawToken));
        if(!preg_match('/^[a-f0-9]{64}$/',$token)) throw new DomainException('Acceso de Cocina inválido.',401);
        self::requireSchema();
        $hash=self::tokenHash($token); $db=self::master();
        $sql="SELECT a.acceso_id,a.server_customers_id,a.empresa_id,a.tipo,a.activo,
                     COALESCE(s.db,'') AS db, COALESCE(s.codigo_cliente,'') AS codigo_cliente
              FROM restaurante_pantalla_accesos a
              LEFT JOIN server_customers s ON s.server_customers_id=a.server_customers_id
              WHERE a.token_hash=? AND a.tipo='cocina' AND a.activo=1 LIMIT 1";
        $st=$db->prepare($sql); if(!$st) throw new RuntimeException('No se pudo validar el acceso de Cocina.');
        $st->bind_param('s',$hash); $st->execute(); $rs=$st->get_result(); $row=$rs?$rs->fetch_assoc():null; $st->close();
        if(!$row) throw new DomainException('El enlace de Cocina es inválido, fue regenerado o está inactivo.',401);

        // Si la pantalla fue vinculada mediante código, exige que ese dispositivo siga autorizado.
        // El enlace privado alternativo puede seguir funcionando sin identificador de dispositivo.
        $deviceSecret=strtolower(trim($deviceSecret));
        if($deviceSecret!==''){
            if(!preg_match('/^[a-f0-9]{64}$/',$deviceSecret)) throw new DomainException('Dispositivo de Cocina inválido.',401);
            self::ensureDeviceSchema();
            $deviceHash=hash('sha256',$deviceSecret);
            $accessId=(int)$row['acceso_id'];
            $dev=self::master()->prepare("SELECT dispositivo_id,activo FROM restaurante_pantalla_dispositivos WHERE acceso_id=? AND dispositivo_hash=? LIMIT 1");
            if(!$dev) throw new RuntimeException('No se pudo validar el dispositivo de Cocina.');
            $dev->bind_param('is',$accessId,$deviceHash); $dev->execute(); $devRs=$dev->get_result(); $devRow=$devRs?$devRs->fetch_assoc():null; $dev->close();
            if(!$devRow || (int)$devRow['activo']!==1) throw new DomainException('Esta pantalla fue desvinculada. Vincúlela nuevamente desde Restaurante.',401);
            $deviceId=(int)$devRow['dispositivo_id'];
            $up=self::master()->prepare("UPDATE restaurante_pantalla_dispositivos SET ultima_conexion=NOW(),fecha_actualizacion=NOW() WHERE dispositivo_id=?");
            if($up){$up->bind_param('i',$deviceId);$up->execute();$up->close();}
        }
        $tenantDb=trim((string)($row['db']??''));
        if((int)$row['server_customers_id']===0){
            $tenantDb=self::masterDbName();
        }
        if($tenantDb==='') throw new RuntimeException('No se pudo resolver la base de datos vinculada al acceso de Cocina.');
        $tenant=self::tenant($tenantDb);
        $empresa=(int)$row['empresa_id'];
        $cfgSt=$tenant->prepare("SELECT usar_comandas,etiqueta_cocina,flujo_cocina,pantalla_cocina_activa FROM restaurante_configuracion WHERE empresa_id=? LIMIT 1");
        if(!$cfgSt) throw new RuntimeException('La configuración Restaurante no está actualizada. Ejecute SQL_CLIENTE_COCINA.sql.');
        $cfgSt->bind_param('i',$empresa); $cfgSt->execute(); $cfgRs=$cfgSt->get_result(); $cfg=$cfgRs?$cfgRs->fetch_assoc():null; $cfgSt->close();
        if(!$cfg || (int)$cfg['usar_comandas']!==1 || (int)$cfg['pantalla_cocina_activa']!==1) throw new DomainException('La Pantalla de Cocina está inactiva para esta empresa.',403);
        return ['access'=>$row,'tenant'=>$tenant,'empresa_id'=>$empresa,'config'=>$cfg];
    }
    public static function getAdminAccess(int $serverCustomerId,int $empresaId): ?array {
        self::requireSchema(); $db=self::master();
        $st=$db->prepare("SELECT acceso_id,activo,token_cifrado,fecha_regeneracion,fecha_actualizacion FROM restaurante_pantalla_accesos WHERE server_customers_id=? AND empresa_id=? AND tipo='cocina' LIMIT 1");
        $st->bind_param('ii',$serverCustomerId,$empresaId); $st->execute(); $rs=$st->get_result(); $row=$rs?$rs->fetch_assoc():null; $st->close(); return $row?:null;
    }
    public static function saveActive(int $serverCustomerId,int $empresaId,bool $active,string $tenantDb): array {
        self::requireSchema(); $db=self::master(); $existing=self::getAdminAccess($serverCustomerId,$empresaId);
        if(!$existing){
            $token=self::generateToken(); $hash=self::tokenHash($token); $enc=self::encryptToken($token); $activo=$active?1:0;
            $st=$db->prepare("INSERT INTO restaurante_pantalla_accesos(server_customers_id,empresa_id,tipo,token_hash,token_cifrado,activo,fecha_registro,fecha_actualizacion,fecha_regeneracion) VALUES(?,?,'cocina',?,?,?,NOW(),NOW(),NOW())");
            $st->bind_param('iissi',$serverCustomerId,$empresaId,$hash,$enc,$activo); $st->execute(); $st->close();
        } else {
            $activo=$active?1:0; $id=(int)$existing['acceso_id']; $st=$db->prepare("UPDATE restaurante_pantalla_accesos SET activo=?,fecha_actualizacion=NOW() WHERE acceso_id=?"); $st->bind_param('ii',$activo,$id); $st->execute(); $st->close();
        }
        self::syncTenantActive($tenantDb,$empresaId,$active);
        return self::getAdminAccess($serverCustomerId,$empresaId) ?: [];
    }
    public static function regenerate(int $serverCustomerId,int $empresaId,string $tenantDb): array {
        self::requireSchema(); $token=self::generateToken(); $hash=self::tokenHash($token); $enc=self::encryptToken($token); $db=self::master();
        $existing=self::getAdminAccess($serverCustomerId,$empresaId);
        if($existing){
            $id=(int)$existing['acceso_id']; self::unlinkAllDevicesByAccess($id); $st=$db->prepare("UPDATE restaurante_pantalla_accesos SET token_hash=?,token_cifrado=?,activo=1,fecha_actualizacion=NOW(),fecha_regeneracion=NOW() WHERE acceso_id=?");
            $st->bind_param('ssi',$hash,$enc,$id); $st->execute(); $st->close();
        }else{
            $st=$db->prepare("INSERT INTO restaurante_pantalla_accesos(server_customers_id,empresa_id,tipo,token_hash,token_cifrado,activo,fecha_registro,fecha_actualizacion,fecha_regeneracion) VALUES(?,?,'cocina',?,?,1,NOW(),NOW(),NOW())");
            $st->bind_param('iiss',$serverCustomerId,$empresaId,$hash,$enc); $st->execute(); $st->close();
        }
        self::syncTenantActive($tenantDb,$empresaId,true);
        return ['token'=>$token,'row'=>self::getAdminAccess($serverCustomerId,$empresaId)];
    }
    public static function syncTenantActive(string $tenantDb,int $empresaId,bool $active): void {
        $tenant=self::tenant($tenantDb); $v=$active?1:0;
        $st=$tenant->prepare("UPDATE restaurante_configuracion SET pantalla_cocina_activa=?,fecha_actualizacion=NOW() WHERE empresa_id=?");
        if(!$st) throw new RuntimeException('Falta ejecutar SQL_CLIENTE_COCINA.sql en la base del cliente.');
        $st->bind_param('ii',$v,$empresaId); $st->execute(); $st->close();
    }

    // ===========================================================
    // VINCULACIÓN DE TV / DISPOSITIVOS MEDIANTE CÓDIGO TEMPORAL
    // ===========================================================
    public static function ensureDeviceSchema(): void {
        $db=self::master();
        $sql="CREATE TABLE IF NOT EXISTS restaurante_pantalla_dispositivos (
            dispositivo_id BIGINT NOT NULL AUTO_INCREMENT,
            acceso_id BIGINT NOT NULL,
            dispositivo_hash CHAR(64) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            fecha_vinculacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ultima_conexion DATETIME NULL,
            fecha_actualizacion DATETIME NULL,
            fecha_desvinculacion DATETIME NULL,
            PRIMARY KEY(dispositivo_id),
            UNIQUE KEY uq_rest_pantalla_dispositivo(acceso_id,dispositivo_hash),
            KEY idx_rest_pantalla_disp_acceso(acceso_id,activo),
            KEY idx_rest_pantalla_disp_ultima(ultima_conexion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        if(!$db->query($sql)){
            throw new RuntimeException('No se pudo preparar el registro de pantallas de Cocina. Ejecute SQL_VINCULACION_COCINA.sql en DB_MAIN.');
        }
    }

    public static function ensurePairingSchema(): void {
        $db=self::master();
        $sql="CREATE TABLE IF NOT EXISTS restaurante_pantalla_vinculos (
            vinculo_id BIGINT NOT NULL AUTO_INCREMENT,
            codigo CHAR(6) NOT NULL,
            dispositivo_hash CHAR(64) NOT NULL,
            acceso_id BIGINT NULL,
            estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
            fecha_expira DATETIME NOT NULL,
            fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_vinculacion DATETIME NULL,
            fecha_consumo DATETIME NULL,
            PRIMARY KEY(vinculo_id),
            UNIQUE KEY uq_rest_pantalla_vinculo_codigo(codigo),
            KEY idx_rest_pantalla_vinculo_disp(dispositivo_hash,estado),
            KEY idx_rest_pantalla_vinculo_acceso(acceso_id,estado),
            KEY idx_rest_pantalla_vinculo_expira(fecha_expira)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        if(!$db->query($sql)){
            throw new RuntimeException('No se pudo preparar la vinculación de dispositivos de Cocina. Ejecute SQL_VINCULACION_COCINA.sql en DB_MAIN.');
        }
    }

    private static function normalizePairCode(string $code): string {
        $code=preg_replace('/\\D+/', '', trim($code)) ?? '';
        if(!preg_match('/^\\d{6}$/',$code)) throw new InvalidArgumentException('El código de vinculación debe tener 6 dígitos.');
        return $code;
    }

    public static function createPairing(string $deviceSecret): array {
        self::ensurePairingSchema();
        $deviceSecret=strtolower(trim($deviceSecret));
        if(!preg_match('/^[a-f0-9]{64}$/',$deviceSecret)) throw new InvalidArgumentException('Identificador de dispositivo inválido.');
        $hash=hash('sha256',$deviceSecret);
        $db=self::master();

        // Limpieza suave de códigos vencidos/consumidos para no acumular registros temporales.
        $db->query("DELETE FROM restaurante_pantalla_vinculos WHERE (fecha_expira<NOW() OR estado IN ('consumido','expirado')) AND fecha_registro<DATE_SUB(NOW(),INTERVAL 1 DAY)");
        $stOld=$db->prepare("UPDATE restaurante_pantalla_vinculos SET estado='expirado' WHERE dispositivo_hash=? AND estado='pendiente'");
        if($stOld){ $stOld->bind_param('s',$hash); $stOld->execute(); $stOld->close(); }

        for($i=0;$i<30;$i++){
            $code=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
            $st=$db->prepare("INSERT INTO restaurante_pantalla_vinculos(codigo,dispositivo_hash,estado,fecha_expira,fecha_registro) VALUES(?,?,'pendiente',DATE_ADD(NOW(),INTERVAL 10 MINUTE),NOW())");
            if(!$st) throw new RuntimeException('No se pudo preparar el código de vinculación.');
            $st->bind_param('ss',$code,$hash);
            if($st->execute()){
                $st->close();
                return ['codigo'=>$code,'expira_segundos'=>600];
            }
            $errno=$st->errno;
            $st->close();
            if($errno!==1062) throw new RuntimeException('No se pudo crear el código de vinculación.');
        }
        throw new RuntimeException('No fue posible generar un código de vinculación único.');
    }

    public static function pairingStatus(string $code,string $deviceSecret): array {
        self::ensurePairingSchema();
        $code=self::normalizePairCode($code);
        $deviceSecret=strtolower(trim($deviceSecret));
        if(!preg_match('/^[a-f0-9]{64}$/',$deviceSecret)) throw new InvalidArgumentException('Identificador de dispositivo inválido.');
        $hash=hash('sha256',$deviceSecret);
        $db=self::master();
        $st=$db->prepare("SELECT vinculo_id,acceso_id,estado,fecha_expira FROM restaurante_pantalla_vinculos WHERE codigo=? AND dispositivo_hash=? LIMIT 1");
        if(!$st) throw new RuntimeException('No se pudo consultar la vinculación de Cocina.');
        $st->bind_param('ss',$code,$hash); $st->execute(); $rs=$st->get_result(); $row=$rs?$rs->fetch_assoc():null; $st->close();
        if(!$row) return ['estado'=>'no_encontrado'];
        $id=(int)$row['vinculo_id'];
        if(strtotime((string)$row['fecha_expira'])<time()){
            $up=$db->prepare("UPDATE restaurante_pantalla_vinculos SET estado='expirado' WHERE vinculo_id=? AND estado<>'consumido'");
            if($up){$up->bind_param('i',$id);$up->execute();$up->close();}
            return ['estado'=>'expirado'];
        }
        $estado=(string)$row['estado'];
        if($estado!=='vinculado') return ['estado'=>$estado];
        $accesoId=(int)($row['acceso_id']??0);
        if($accesoId<=0) return ['estado'=>'pendiente'];
        $access=self::getAccessById($accesoId);
        if(!$access || (int)$access['activo']!==1) return ['estado'=>'inactivo'];
        $token=self::decryptToken((string)($access['token_cifrado']??''));
        if(!preg_match('/^[a-f0-9]{64}$/',$token)) throw new RuntimeException('El acceso vinculado no contiene un token válido.');
        $up=$db->prepare("UPDATE restaurante_pantalla_vinculos SET estado='consumido',fecha_consumo=NOW() WHERE vinculo_id=?");
        if($up){$up->bind_param('i',$id);$up->execute();$up->close();}
        return ['estado'=>'vinculado','token'=>$token];
    }

    public static function getAccessById(int $accessId): ?array {
        self::requireSchema();
        if($accessId<=0) return null;
        $db=self::master();
        $st=$db->prepare("SELECT acceso_id,server_customers_id,empresa_id,tipo,token_hash,token_cifrado,activo FROM restaurante_pantalla_accesos WHERE acceso_id=? AND tipo='cocina' LIMIT 1");
        if(!$st) throw new RuntimeException('No se pudo consultar el acceso de Cocina.');
        $st->bind_param('i',$accessId); $st->execute(); $rs=$st->get_result(); $row=$rs?$rs->fetch_assoc():null; $st->close();
        return $row?:null;
    }

    public static function linkPairingCode(int $serverCustomerId,int $empresaId,string $code,string $deviceName=''): void {
        self::ensurePairingSchema();
        self::ensureDeviceSchema();
        $code=self::normalizePairCode($code);
        $access=self::getAdminAccess($serverCustomerId,$empresaId);
        if(!$access || (int)$access['activo']!==1){
            throw new DomainException('Active y guarde primero la Pantalla de Cocina antes de vincular la TV.',409);
        }
        $accessId=(int)$access['acceso_id'];
        $db=self::master();

        $find=$db->prepare("SELECT vinculo_id,dispositivo_hash FROM restaurante_pantalla_vinculos WHERE codigo=? AND estado='pendiente' AND fecha_expira>=NOW() LIMIT 1");
        if(!$find) throw new RuntimeException('No se pudo consultar el código de vinculación.');
        $find->bind_param('s',$code); $find->execute(); $rs=$find->get_result(); $pair=$rs?$rs->fetch_assoc():null; $find->close();
        if(!$pair) throw new DomainException('El código no existe, ya fue utilizado o venció. Genere uno nuevo en la TV.',422);

        $deviceHash=(string)$pair['dispositivo_hash'];
        $deviceName=trim($deviceName);
        if($deviceName==='') $deviceName='Pantalla Cocina '.strtoupper(substr($deviceHash,0,4));
        if(function_exists('mb_substr')) $deviceName=mb_substr($deviceName,0,100,'UTF-8'); else $deviceName=substr($deviceName,0,100);

        $stDev=$db->prepare("INSERT INTO restaurante_pantalla_dispositivos(acceso_id,dispositivo_hash,nombre,activo,fecha_vinculacion,ultima_conexion,fecha_actualizacion)
            VALUES(?,?,?,1,NOW(),NULL,NOW())
            ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),activo=1,fecha_vinculacion=NOW(),fecha_desvinculacion=NULL,fecha_actualizacion=NOW()");
        if(!$stDev) throw new RuntimeException('No se pudo registrar la pantalla vinculada.');
        $stDev->bind_param('iss',$accessId,$deviceHash,$deviceName); $stDev->execute(); $stDev->close();

        $st=$db->prepare("UPDATE restaurante_pantalla_vinculos SET acceso_id=?,estado='vinculado',fecha_vinculacion=NOW() WHERE vinculo_id=? AND estado='pendiente'");
        if(!$st) throw new RuntimeException('No se pudo vincular la TV.');
        $pairId=(int)$pair['vinculo_id']; $st->bind_param('ii',$accessId,$pairId); $st->execute(); $affected=$st->affected_rows; $st->close();
        if($affected<1) throw new DomainException('El código ya fue utilizado. Genere uno nuevo en la TV.',422);
    }

    public static function listDevices(int $serverCustomerId,int $empresaId): array {
        self::ensureDeviceSchema();
        $access=self::getAdminAccess($serverCustomerId,$empresaId);
        if(!$access) return [];
        $accessId=(int)$access['acceso_id'];
        $db=self::master();
        $st=$db->prepare("SELECT dispositivo_id,nombre,activo,fecha_vinculacion,ultima_conexion,fecha_desvinculacion
            FROM restaurante_pantalla_dispositivos WHERE acceso_id=? ORDER BY activo DESC,COALESCE(ultima_conexion,fecha_vinculacion) DESC,dispositivo_id DESC");
        if(!$st) throw new RuntimeException('No se pudieron consultar las pantallas vinculadas.');
        $st->bind_param('i',$accessId); $st->execute(); $rs=$st->get_result(); $out=[];
        while($rs && ($r=$rs->fetch_assoc())){
            $last=$r['ultima_conexion']?:null;
            $online=false;
            if($last){ $ts=strtotime((string)$last); $online=$ts!==false && $ts>=time()-45; }
            $out[]=[
                'dispositivo_id'=>(int)$r['dispositivo_id'],
                'nombre'=>(string)$r['nombre'],
                'activo'=>(int)$r['activo'],
                'en_linea'=>$online?1:0,
                'fecha_vinculacion'=>$r['fecha_vinculacion'],
                'ultima_conexion'=>$r['ultima_conexion'],
                'fecha_desvinculacion'=>$r['fecha_desvinculacion']
            ];
        }
        $st->close();
        return $out;
    }

    public static function unlinkDevice(int $serverCustomerId,int $empresaId,int $deviceId): void {
        if($deviceId<=0) throw new InvalidArgumentException('Pantalla inválida.');
        self::ensureDeviceSchema();
        $access=self::getAdminAccess($serverCustomerId,$empresaId);
        if(!$access) throw new DomainException('No existe acceso de Cocina para esta empresa.',404);
        $accessId=(int)$access['acceso_id'];
        $db=self::master();
        $st=$db->prepare("UPDATE restaurante_pantalla_dispositivos SET activo=0,fecha_desvinculacion=NOW(),fecha_actualizacion=NOW() WHERE dispositivo_id=? AND acceso_id=? AND activo=1");
        if(!$st) throw new RuntimeException('No se pudo desvincular la pantalla.');
        $st->bind_param('ii',$deviceId,$accessId); $st->execute(); $affected=$st->affected_rows; $st->close();
        if($affected<1) throw new DomainException('La pantalla ya estaba desvinculada o no pertenece a esta empresa.',404);
    }

    public static function unlinkAllDevicesByAccess(int $accessId): void {
        if($accessId<=0) return;
        self::ensureDeviceSchema();
        $db=self::master();
        $st=$db->prepare("UPDATE restaurante_pantalla_dispositivos SET activo=0,fecha_desvinculacion=NOW(),fecha_actualizacion=NOW() WHERE acceso_id=? AND activo=1");
        if($st){$st->bind_param('i',$accessId);$st->execute();$st->close();}
    }


    public static function ensureEnhancementsSchema(): void {
        $db=self::master();
        $sql1="CREATE TABLE IF NOT EXISTS restaurante_configuracion_historial (
            historial_id BIGINT NOT NULL AUTO_INCREMENT,
            server_customers_id INT NOT NULL DEFAULT 0,
            empresa_id INT NOT NULL,
            users_id INT NOT NULL DEFAULT 0,
            colaborador_id INT NOT NULL DEFAULT 0,
            categoria VARCHAR(40) NOT NULL DEFAULT 'general',
            resumen VARCHAR(255) NOT NULL DEFAULT '',
            cambios_json LONGTEXT NULL,
            fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(historial_id),
            KEY idx_rest_cfg_hist_empresa(server_customers_id,empresa_id,fecha_registro)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        if(!$db->query($sql1)) throw new RuntimeException('No se pudo preparar el historial de configuración.');
        $sql2="CREATE TABLE IF NOT EXISTS restaurante_pantalla_pruebas (
            prueba_id BIGINT NOT NULL AUTO_INCREMENT,
            acceso_id BIGINT NOT NULL,
            mensaje VARCHAR(160) NOT NULL DEFAULT 'Prueba de conexión IZZY',
            fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_expira DATETIME NOT NULL,
            PRIMARY KEY(prueba_id),
            KEY idx_rest_pantalla_prueba(acceso_id,fecha_expira)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        if(!$db->query($sql2)) throw new RuntimeException('No se pudo preparar las pruebas de Pantalla Cocina.');
    }

    public static function renameDevice(int $serverCustomerId,int $empresaId,int $deviceId,string $name): void {
        if($deviceId<=0) throw new InvalidArgumentException('Pantalla inválida.');
        $name=trim($name);
        if($name==='') throw new InvalidArgumentException('Escriba un nombre para la pantalla.');
        if(function_exists('mb_substr')) $name=mb_substr($name,0,100,'UTF-8'); else $name=substr($name,0,100);
        self::ensureDeviceSchema();
        $access=self::getAdminAccess($serverCustomerId,$empresaId);
        if(!$access) throw new DomainException('No existe acceso de Cocina para esta empresa.',404);
        $accessId=(int)$access['acceso_id']; $db=self::master();
        $st=$db->prepare("UPDATE restaurante_pantalla_dispositivos SET nombre=?,fecha_actualizacion=NOW() WHERE dispositivo_id=? AND acceso_id=?");
        if(!$st) throw new RuntimeException('No se pudo renombrar la pantalla.');
        $st->bind_param('sii',$name,$deviceId,$accessId); $st->execute(); $affected=$st->affected_rows; $st->close();
        if($affected<1) throw new DomainException('La pantalla no existe o no pertenece a esta empresa.',404);
    }

    public static function sendKitchenTest(int $serverCustomerId,int $empresaId,string $message='Prueba de conexión IZZY'): void {
        self::ensureEnhancementsSchema();
        $access=self::getAdminAccess($serverCustomerId,$empresaId);
        if(!$access || (int)$access['activo']!==1) throw new DomainException('Active primero la Pantalla de Cocina.',409);
        $accessId=(int)$access['acceso_id'];
        $message=trim($message); if($message==='') $message='Prueba de conexión IZZY';
        if(function_exists('mb_substr')) $message=mb_substr($message,0,160,'UTF-8'); else $message=substr($message,0,160);
        $db=self::master();
        $db->query("DELETE FROM restaurante_pantalla_pruebas WHERE fecha_expira<NOW()");
        $st=$db->prepare("INSERT INTO restaurante_pantalla_pruebas(acceso_id,mensaje,fecha_registro,fecha_expira) VALUES(?,?,NOW(),DATE_ADD(NOW(),INTERVAL 45 SECOND))");
        if(!$st) throw new RuntimeException('No se pudo enviar la prueba de Cocina.');
        $st->bind_param('is',$accessId,$message); $st->execute(); $st->close();
    }

    public static function getKitchenTests(int $accessId): array {
        if($accessId<=0) return [];
        self::ensureEnhancementsSchema(); $db=self::master();
        $st=$db->prepare("SELECT prueba_id,mensaje,fecha_registro FROM restaurante_pantalla_pruebas WHERE acceso_id=? AND fecha_expira>=NOW() ORDER BY prueba_id DESC LIMIT 3");
        if(!$st) return [];
        $st->bind_param('i',$accessId); $st->execute(); $rs=$st->get_result(); $out=[];
        while($rs && ($r=$rs->fetch_assoc())) $out[]=$r;
        $st->close(); return $out;
    }

    public static function recordConfigHistory(int $serverCustomerId,int $empresaId,int $usersId,int $colaboradorId,string $category,string $summary,array $changes=[]): void {
        self::ensureEnhancementsSchema();
        $category=preg_replace('/[^a-z0-9_-]/i','',strtolower(trim($category))) ?: 'general';
        $summary=trim($summary); if($summary==='') $summary='Configuración actualizada';
        if(function_exists('mb_substr')) $summary=mb_substr($summary,0,255,'UTF-8'); else $summary=substr($summary,0,255);
        $json=json_encode($changes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $db=self::master(); $st=$db->prepare("INSERT INTO restaurante_configuracion_historial(server_customers_id,empresa_id,users_id,colaborador_id,categoria,resumen,cambios_json,fecha_registro) VALUES(?,?,?,?,?,?,?,NOW())");
        if(!$st) throw new RuntimeException('No se pudo registrar el historial.');
        $st->bind_param('iiiisss',$serverCustomerId,$empresaId,$usersId,$colaboradorId,$category,$summary,$json); $st->execute(); $st->close();
    }

    public static function listConfigHistory(int $serverCustomerId,int $empresaId,int $limit=20): array {
        self::ensureEnhancementsSchema(); $limit=max(1,min(50,$limit)); $db=self::master();
        $st=$db->prepare("SELECT historial_id,users_id,colaborador_id,categoria,resumen,cambios_json,fecha_registro FROM restaurante_configuracion_historial WHERE server_customers_id=? AND empresa_id=? ORDER BY historial_id DESC LIMIT ?");
        if(!$st) throw new RuntimeException('No se pudo consultar el historial.');
        $st->bind_param('iii',$serverCustomerId,$empresaId,$limit); $st->execute(); $rs=$st->get_result(); $out=[];
        while($rs && ($r=$rs->fetch_assoc())){
            $r['cambios']=json_decode((string)($r['cambios_json']??''),true) ?: [];
            unset($r['cambios_json']); $out[]=$r;
        }
        $st->close(); return $out;
    }


}
