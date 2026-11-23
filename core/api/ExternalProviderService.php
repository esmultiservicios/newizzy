<?php
/** Conectores salientes (exclusivo DB_MAIN): secretos AES-256-GCM; sin exposición al navegador. */
declare(strict_types=1);
final class IzzyExternalProviderService {
    private mysqli $db;
    public function __construct() {
        require_once dirname(__DIR__).'/configGenerales.php';
        require_once dirname(__DIR__).'/mainModel.php';
        $this->db=(new mainModel())->connectionDBLocal(DB_MAIN);
    }
    /**
     * Autoaprovisionamiento seguro: sin comandos manuales ni secretos en el código.
     * Usa el mismo directorio EXTERNO al document root del .env seleccionado por IZZY.
     * Un keyfile existente es inmutable: si está dañado o falta estando ya cifradas
     * otras conexiones, se bloquea el guardado, nunca se rota automáticamente.
     */
    private function protectedKeyPath(): string {
        $envOverride=trim((string)(getenv('IZZY_ENV_FILE') ?: ($_ENV['IZZY_ENV_FILE']??'')));
        if ($envOverride!=='') $envFile=$envOverride;
        elseif (PHP_OS_FAMILY==='Windows') $envFile='C:\\credentials\\newizzy\\.env';
        else {
            $host=strtolower((string)($_SERVER['HTTP_HOST']??''));
            $envFile=($host==='demo.izzycloud.app'||str_starts_with($host,'demo.'))
                ?'/home/esmultiservicios/credentials/devizzy/.env'
                :'/home/esmultiservicios/credentials/newizzy/.env';
        }
        $folder=realpath(dirname($envFile));
        if($folder===false || !is_dir($folder) || !is_file($envFile))
            throw new RuntimeException('EXTERNAL_KEY_MISSING: No se encontró el directorio protegido de credenciales del entorno.');
        $publicRoot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));
        $prefix=$publicRoot!==false?rtrim(str_replace('\\','/',strtolower($publicRoot)),'/').'/':'';
        $normalized=rtrim(str_replace('\\','/',strtolower($folder)),'/').'/';
        if($prefix!=='' && str_starts_with($normalized,$prefix))
            throw new RuntimeException('EXTERNAL_KEY_MISSING: El directorio de credenciales no debe estar dentro de la carpeta pública.');
        return $folder.DIRECTORY_SEPARATOR.'.izzy_external_api_aes.key';
    }
    private function key(): string {
        $secret=trim((string)(getenv('IZZY_EXTERNAL_API_ENCRYPTION_KEY') ?: ($_ENV['IZZY_EXTERNAL_API_ENCRYPTION_KEY'] ?? '')));
        if($secret!=='') {
            if(!preg_match('/^[a-f0-9]{64}$/i',$secret))
                throw new RuntimeException('EXTERNAL_KEY_MISSING: La clave existente en el .env es inválida; no fue reemplazada.');
            return hex2bin($secret);
        }
        // Solo se crea el archivo protegido cuando aún no existe ninguna credencial
        // cifrada. Si lo anterior existe y falta su clave, NO generar otra.
        $path=$this->protectedKeyPath();
        if(!is_file($path)){
            $check=$this->db->query("SELECT COUNT(*) AS n FROM api_external_connections WHERE token_cifrado IS NOT NULL AND token_cifrado<>''");
            $encryptedCount=(int)($check->fetch_assoc()['n']??0);
            if($encryptedCount>0)throw new RuntimeException('EXTERNAL_KEY_MISSING: Hay credenciales cifradas existentes. Restaure su clave original; no se generó otra.');
        }
        $handle=@fopen($path,'c+b');
        if($handle===false)throw new RuntimeException('EXTERNAL_KEY_MISSING: PHP no tiene permisos para guardar la clave en el directorio protegido de IZZY.');
        try {
            if(!flock($handle,LOCK_EX))throw new RuntimeException('No se pudo bloquear el archivo de cifrado.');
            rewind($handle);$stored=trim((string)stream_get_contents($handle));
            if($stored===''){
                $stored=bin2hex(random_bytes(32));
                rewind($handle);
                if(fwrite($handle,$stored."\n")!==65 || !fflush($handle))
                    throw new RuntimeException('No fue posible persistir la clave de cifrado.');
                @chmod($path,0600);
            }
            if(!preg_match('/^[a-f0-9]{64}$/i',$stored))
                throw new RuntimeException('EXTERNAL_KEY_MISSING: Archivo de cifrado inválido; se conserva para revisión.');
            return hex2bin($stored);
        } finally {flock($handle,LOCK_UN);fclose($handle);}
    }
    private function encrypt(string $value): string {
        $nonce=random_bytes(12);$tag='';
        $encrypted=openssl_encrypt($value,'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,$nonce,$tag);
        if ($encrypted===false) throw new RuntimeException('Error cifrando credenciales.');
        return base64_encode($nonce.$tag.$encrypted);
    }
    private function decrypt(string $value): string {
        $bin=base64_decode($value,true);
        if ($bin===false || strlen($bin)<29) throw new RuntimeException('Credencial cifrada inválida.');
        $result=openssl_decrypt(substr($bin,28),'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,substr($bin,0,12),substr($bin,12,16));
        if ($result===false) throw new RuntimeException('No se pudo descifrar la credencial.');
        return $result;
    }
    /** Diagnóstico sin secretos ni acceso externo: comprueba dependencias antes de guardar. */
    public function diagnostics(): array {
        $required=['api_external_connections','api_external_checks','api_external_categories','api_external_operations'];
        $available=[];
        foreach($required as $name){
            $stmt=$this->db->prepare('SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
            $stmt->bind_param('s',$name);$stmt->execute();$result=$stmt->get_result()->fetch_assoc();$stmt->close();
            $available[$name]=((int)($result['n']??0))===1;
        }
        $secret=(string)(getenv('IZZY_EXTERNAL_API_ENCRYPTION_KEY') ?: ($_ENV['IZZY_EXTERNAL_API_ENCRYPTION_KEY']??''));
        $keyFileReady=false; $keyAutoReady=false;
        try { $keyPath=$this->protectedKeyPath(); $keyFileReady=is_file($keyPath) && (bool)preg_match('/^[a-f0-9]{64}$/i',trim((string)@file_get_contents($keyPath))); $keyAutoReady=$keyFileReady || is_writable(dirname($keyPath)); } catch (Throwable $e) {}
        return ['tablas'=>$available,'cifrado_configurado'=>(bool)preg_match('/^[a-f0-9]{64}$/i',trim($secret)) || $keyFileReady,
            'cifrado_autoconfigurable'=>$keyAutoReady,
            'curl_disponible'=>function_exists('curl_init'),'openssl_disponible'=>function_exists('openssl_encrypt')];
    }
    private static function safeJsonOk(?array $data): bool {
        if(!is_array($data))return false;
        if(array_key_exists('ok',$data))return $data['ok']===true;
        if(array_key_exists('success',$data))return $data['success']===true;
        return false;
    }
    /** Identifica únicamente un canal WhatsApp; si existen varios evita asignar el incorrecto. */
    private function identifyChannel(int $connectionId,array $decoded): array {
        $channels=$decoded['data']['channels']??$decoded['channels']??null;
        if(!is_array($channels))return ['canal_detectado'=>false,'motivo'=>'La respuesta no contiene data.channels.'];
        $matching=[];
        foreach($channels as $channel){
            if(!is_array($channel))continue;
            $type=strtolower((string)($channel['type']??$channel['channel']??''));
            if($type==='whatsapp' && isset($channel['id']) && is_scalar($channel['id']))$matching[]=trim((string)$channel['id']);
        }
        $matching=array_values(array_unique(array_filter($matching,static fn($v)=>$v!=='')));
        if(count($matching)!==1)return ['canal_detectado'=>false,'canales_whatsapp'=>count($matching),
            'motivo'=>count($matching)>1?'Hay varios canales WhatsApp autorizados; no se asignó ninguno automáticamente.':'No se encontró un canal WhatsApp identificable.'];
        $id=$matching[0];
        $stmt=$this->db->prepare('UPDATE api_external_connections SET canal_id=? WHERE id=? AND archivado=0');
        $stmt->bind_param('si',$id,$connectionId);$stmt->execute();$stmt->close();
        return ['canal_detectado'=>true,'canales_whatsapp'=>1,'canal_id'=>$id];
    }
    public function categories(): array {
        $r=$this->db->query('SELECT nombre FROM api_external_categories WHERE activo=1 ORDER BY nombre');
        return array_column($r->fetch_all(MYSQLI_ASSOC),'nombre');
    }
    private function validateConfig(string $value): string {
        $value=trim($value);
        if($value==='') return '{}';
        if(strlen($value)>4000) throw new InvalidArgumentException('Los parámetros superan 4000 caracteres.');
        try { $decoded=json_decode($value,false,32,JSON_THROW_ON_ERROR); } catch (JsonException $e) { throw new InvalidArgumentException('Los parámetros deben ser un objeto JSON válido.'); }
        if(!($decoded instanceof stdClass)) throw new InvalidArgumentException('Los parámetros deben ser un objeto JSON.');
        $scan=function($item)use(&$scan){
            if(!is_array($item) && !($item instanceof stdClass))return;
            foreach($item as $key=>$val){
                if(preg_match('/(password|secret|token|api.?key|authorization|credential|private.?key|clave|contrasena)/i',(string)$key))throw new InvalidArgumentException('No guarde secretos en parámetros JSON; use la credencial cifrada.');
                $scan($val);
            }
        };$scan($decoded);
        return json_encode($decoded,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }
    public function listing(): array {
        $r=$this->db->query('SELECT id,nombre,proveedor,categoria,descripcion,url_base,tipo_auth,canal_id,configuracion_json,activo,ultima_prueba,ultimo_resultado,archivado,created_at FROM api_external_connections ORDER BY id DESC');
        return $r->fetch_all(MYSQLI_ASSOC);
    }
    /** Guarda solo formato editorial básico sin depender de extensiones DOM adicionales. */
    private static function sanitizeDescription(string $html): string {
        $html=trim($html);
        if ($html==='') return '';
        if (strlen($html)>20000) throw new InvalidArgumentException('Descripción demasiado larga.');
        // Eliminar bloques peligrosos con contenido completo antes de filtrar etiquetas.
        $html=preg_replace('~<(script|style|iframe|object|embed|svg|form|video|audio)[^>]*>.*?</\1\s*>~is','',$html);
        $html=preg_replace('~<(script|style|iframe|object|embed|svg|form|video|audio|img)\b[^>]*\/?\s*>~is','',$html);
        $html=strip_tags($html,'<p><br><strong><b><em><i><ul><ol><li>');
        // No conservar atributos HTML, eventos, identificadores, estilos ni URLs.
        $html=preg_replace_callback('~<\s*(/?)\s*(p|br|strong|b|em|i|ul|ol|li)\b[^>]*>~i',static function($m){
            $tag=strtolower($m[2]);return $tag==='br'?'<br>':'<'.$m[1].$tag.'>';
        },$html);
        if (strlen($html)>500) throw new InvalidArgumentException('La descripción supera los 500 caracteres de almacenamiento.');
        return $html;
    }
    public function save(array $input): int {
        $id=(int)($input['id']??0);$name=trim((string)($input['nombre']??''));$provider=trim((string)($input['proveedor']??''));
        $category=trim((string)($input['categoria']??''));$description=self::sanitizeDescription((string)($input['descripcion']??''));
        $url=trim((string)($input['url_base']??''));$auth=trim((string)($input['tipo_auth']??''));$channel=trim((string)($input['canal_id']??''));
        $token=trim((string)($input['token']??''));$config=$this->validateConfig((string)($input['configuracion_json']??'{}')); 
        if ($name==='' || strlen($name)>100 || strlen($description)>500 || strlen($channel)>120) throw new InvalidArgumentException('Revise los campos de conexión.');
        // Registro genérico seguro: guardar una URL no concede permiso para llamarla desde el servidor.
        // Las pruebas HTTP solo están implementadas para el host ZYNKO fijado más abajo.
        if ($provider==='' || strlen($provider)>50 || $category==='' || strlen($category)>40 ||
            !in_array($auth,['bearer','api_key','basic','none'],true)) throw new InvalidArgumentException('Proveedor, categoría o autenticación inválidos.');
        $parts=parse_url($url);
        if (!is_array($parts) || strtolower((string)($parts['scheme']??''))!=='https' || empty($parts['host']) ||
            isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) ||
            strlen($url)>255 || preg_match('/[\x00-\x1f\x7f]/',$url)) throw new InvalidArgumentException('Introduzca una URL HTTPS válida sin credenciales incrustadas.');
        // No permitir nombres de host privados/locales en el catálogo administrativo.
        $host=strtolower(rtrim((string)$parts['host'],'.'));
        if ($host==='localhost' || str_ends_with($host,'.localhost') || str_ends_with($host,'.local') ||
            filter_var($host,FILTER_VALIDATE_IP) || strpos($host,'.')===false) throw new InvalidArgumentException('Utilice un dominio HTTPS público.');
        if ($id===0 && $auth!=='none' && $token==='') throw new InvalidArgumentException('Ingrese la credencial de autenticación.');
        if ($token!=='' && strlen($token)>4096) throw new InvalidArgumentException('Token demasiado largo.');
        $secret=$token!==''?$this->encrypt($token):($id===0?'':null);
        if ($id>0) {
            $stmt=$this->db->prepare('UPDATE api_external_connections SET nombre=?,proveedor=?,categoria=?,descripcion=?,url_base=?,tipo_auth=?,canal_id=?,configuracion_json=?,token_cifrado=COALESCE(?,token_cifrado) WHERE id=? AND archivado=0');
            $stmt->bind_param('sssssssssi',$name,$provider,$category,$description,$url,$auth,$channel,$config,$secret,$id);
        } else {
            $stmt=$this->db->prepare('INSERT INTO api_external_connections (nombre,proveedor,categoria,descripcion,url_base,tipo_auth,canal_id,configuracion_json,token_cifrado) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('sssssssss',$name,$provider,$category,$description,$url,$auth,$channel,$config,$secret);
        }
        $stmt->execute();$result=$id>0?$id:$stmt->insert_id;$stmt->close();
        $catStmt=$this->db->prepare('INSERT IGNORE INTO api_external_categories (nombre) VALUES (?)');
        $catStmt->bind_param('s',$category);$catStmt->execute();$catStmt->close();
        return (int)$result;
    }
    public function toggle(int $id,bool $on): void {
        if ($id<1) throw new InvalidArgumentException('Conexión inválida.');
        $active=$on?1:0;$stmt=$this->db->prepare('UPDATE api_external_connections SET activo=? WHERE id=? AND archivado=0');$stmt->bind_param('ii',$active,$id);$stmt->execute();$stmt->close();
    }
    public function archive(int $id): void {
        if ($id<1) throw new InvalidArgumentException('Conexión inválida.');
        $stmt=$this->db->prepare('UPDATE api_external_connections SET activo=0,archivado=1 WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();
    }
    private function conn(int $id): array {
        $stmt=$this->db->prepare('SELECT * FROM api_external_connections WHERE id=? AND archivado=0 LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
        if (!$row) throw new DomainException('Conexión no encontrada.');return $row;
    }
    /** Solo lectura de recursos ZYNKO documentados. Los envíos requieren contrato verificado. */
    public function request(int $id,string $route,string $method='GET',?array $payload=null,?string $idempotency=null): array {
        $row=$this->conn($id);
        if (strtoupper(trim((string)$row['proveedor']))!=='ZYNKO' || $row['tipo_auth']!=='bearer' || $row['url_base']!=='https://zynkocloud.app/api.php')
            throw new DomainException('Conector ZYNKO no autorizado para esta conexión.');
        if (!(int)$row['activo']) throw new DomainException('Active primero la conexión para comprobarla.');
        $allowed=['v1/channels','v1/whatsapp/history','v1/whatsapp/status'];
        if($method!=='GET'||!in_array($route,$allowed,true)||$payload!==null||$idempotency!==null)
            throw new DomainException('Solo se permiten consultas de lectura autorizadas. No se han activado envíos.');
        if(!function_exists('curl_init'))throw new RuntimeException('CURL_MISSING: PHP necesita la extensión cURL para realizar conexiones HTTPS.');
        $url='https://zynkocloud.app/api.php?r='.rawurlencode($route);
        if($route==='v1/whatsapp/history')$url.='&page=1&per_page=20';
        $token=$this->decrypt((string)$row['token_cifrado']);
        $headers=['Authorization: Bearer '.$token,'Accept: application/json'];
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,
            CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_MAXREDIRS=>0,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_MAXFILESIZE=>1048576]);
        $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $curlErr=$body===false?curl_errno($ch):0;curl_close($ch);
        unset($token,$headers);
        // Evitar procesar o registrar documentos excesivamente grandes.
        $decoded=is_string($body)&&strlen($body)<=1048576?json_decode($body,true):null;
        $ok=$curlErr===0 && $status>=200 && $status<300 && self::safeJsonOk(is_array($decoded)?$decoded:null);
        $channelInfo=[];
        if($ok&&$route==='v1/channels')$channelInfo=$this->identifyChannel($id,$decoded);
        $logMessage=$curlErr?'Error de transporte HTTPS (cURL '.$curlErr.')':($status===401?'Credencial rechazada (HTTP 401)':($status===403?'Permisos, IP o canal no autorizado (HTTP 403)':($ok?'Consulta autenticada confirmada':'El proveedor devolvió HTTP '.$status.' o una respuesta no exitosa')));
        $stmt=$this->db->prepare('INSERT INTO api_external_checks (connection_id,ruta,http_status,exitoso,descripcion) VALUES (?,?,?,?,?)');
        $flag=$ok?1:0;$stmt->bind_param('isiis',$id,$route,$status,$flag,$logMessage);$stmt->execute();$stmt->close();
        $stmt=$this->db->prepare('UPDATE api_external_connections SET ultima_prueba=NOW(),ultimo_resultado=? WHERE id=?');
        $stmt->bind_param('si',$logMessage,$id);$stmt->execute();$stmt->close();
        // Nunca exponer bodies completos: historial y estados pueden incluir teléfonos y documentos.
        return ['ok'=>$ok,'http_status'=>$status,'detalle'=>$logMessage,'canal'=>$channelInfo,
            'respuesta_exitosa'=>$ok,'estado'=>'consulta_sin_confirmar_entrega'];
    }
    /** Catálogo declarativo: el registro NO autoriza ejecutar HTTP arbitrario. */
    public function operations(int $connectionId): array {
        $this->conn($connectionId);
        $stmt=$this->db->prepare('SELECT id,connection_id,codigo,nombre,metodo,ruta,parametros_json,respuesta_json,descripcion,activo FROM api_external_operations WHERE connection_id=? ORDER BY id ASC');
        $stmt->bind_param('i',$connectionId);$stmt->execute();$result=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $result;
    }
    private static function jsonObject(string $source,string $label): string {
        if(strlen($source)>12000)throw new InvalidArgumentException($label.' demasiado grande.');
        try{$v=json_decode($source,false,24,JSON_THROW_ON_ERROR);}catch(JsonException $e){throw new InvalidArgumentException($label.' debe ser JSON válido.');}
        if(!($v instanceof stdClass))throw new InvalidArgumentException($label.' debe ser un objeto JSON.');
        $walk=function($obj)use(&$walk){foreach($obj as $k=>$val){if(preg_match('/(token|secret|password|authorization|api.?key|credential|clave|contrasena)/i',(string)$k))throw new InvalidArgumentException('No se permiten secretos en operaciones.');if(is_array($val)||$val instanceof stdClass)$walk($val);}};
        $walk($v);return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }
    public function saveOperation(array $in): int {
        $connectionId=(int)($in['connection_id']??0);$this->conn($connectionId);
        $id=(int)($in['operation_id']??0);$code=trim((string)($in['codigo']??''));$name=trim((string)($in['nombre']??''));
        $method=strtoupper(trim((string)($in['metodo']??'')));$path=trim((string)($in['ruta']??''));$description=trim((string)($in['descripcion']??''));
        if(!preg_match('/^[a-z][a-z0-9_]{1,79}$/',$code)||$name===''||mb_strlen($name)>120||!in_array($method,['GET','POST','PUT','PATCH','DELETE'],true)||strlen($path)>512||strlen($description)>2000)throw new InvalidArgumentException('Revise la operación API.');
        // Ruta relativa únicamente; no permite URL completa, escapes, puertos ni path traversal.
        if($path===''||!in_array($path[0],['/','?'],true)||str_starts_with($path,'//')||preg_match('~(?:\\|\.\.|[\x00-\x1f]|://|@)~',$path))throw new InvalidArgumentException('La ruta debe ser relativa y segura, comenzando con / o ?.');
        $params=self::jsonObject((string)($in['parametros_json']??'{}'),'Parámetros');$response=self::jsonObject((string)($in['respuesta_json']??'{}'),'Esquema de respuesta');
        if($id){$stmt=$this->db->prepare('UPDATE api_external_operations SET codigo=?,nombre=?,metodo=?,ruta=?,parametros_json=?,respuesta_json=?,descripcion=? WHERE id=? AND connection_id=?');$stmt->bind_param('sssssssii',$code,$name,$method,$path,$params,$response,$description,$id,$connectionId);}else{$stmt=$this->db->prepare('INSERT INTO api_external_operations(connection_id,codigo,nombre,metodo,ruta,parametros_json,respuesta_json,descripcion,activo) VALUES(?,?,?,?,?,?,?,?,0)');$stmt->bind_param('isssssss',$connectionId,$code,$name,$method,$path,$params,$response,$description);}
        $stmt->execute();$result=$id?:$stmt->insert_id;$stmt->close();return (int)$result;
    }
    public function seedZynkoOperations(int $connectionId): int {
        $row=$this->conn($connectionId);
        if(strtoupper(trim((string)$row['proveedor']))!=='ZYNKO'||$row['url_base']!=='https://zynkocloud.app/api.php')throw new DomainException('El catálogo ZYNKO solo corresponde a su conexión oficial.');
        // GET/POST y rutas conocidas. La API puede utilizar query r, no un path REST.
        $items=[['channels_read','Consultar canales','GET','?r=v1/channels'],['whatsapp_send','Enviar WhatsApp individual','POST','?r=v1/whatsapp/send'],['whatsapp_status','Consultar estado','GET','?r=v1/whatsapp/status'],['whatsapp_history','Consultar historial','GET','?r=v1/whatsapp/history']];
        $stmt=$this->db->prepare("INSERT IGNORE INTO api_external_operations(connection_id,codigo,nombre,metodo,ruta,parametros_json,respuesta_json,descripcion,activo) VALUES (?,?,?,?,?,'{}','{}','Ruta preconfigurada. Validar contrato antes de activar.',0)");
        $count=0;foreach($items as $item){[$code,$name,$method,$route]=$item;$stmt->bind_param('issss',$connectionId,$code,$name,$method,$route);$stmt->execute();$count+=$stmt->affected_rows;}$stmt->close();return $count;
    }
    /** Importación OpenAPI 3 JSON únicamente. Se analizan documentos, NO se ejecutan. */
    public function previewOpenApi(string $source): array {
        if(strlen($source)>131072)throw new InvalidArgumentException('OpenAPI supera el límite de 128 KiB.');
        try{$spec=json_decode($source,true,32,JSON_THROW_ON_ERROR);}catch(JsonException $e){throw new InvalidArgumentException('Use OpenAPI JSON válido.');}
        if(!is_array($spec)||!isset($spec['openapi'])||!preg_match('/^3\./',(string)$spec['openapi'])||!is_array($spec['paths']??null))throw new InvalidArgumentException('Se requiere OpenAPI 3.x en JSON.');
        $out=[];
        foreach($spec['paths'] as $path=>$verbs){if(!is_string($path)||!str_starts_with($path,'/')||str_contains($path,'..')||!is_array($verbs))continue;
            foreach($verbs as $verb=>$def){$method=strtoupper((string)$verb);if(!in_array($method,['GET','POST','PUT','PATCH','DELETE'],true)||!is_array($def))continue;
                $out[]=['codigo'=>substr(preg_replace('/[^a-z0-9_]/','_',strtolower((string)($def['operationId']??$method.'_'.$path))),0,80),'nombre'=>substr((string)($def['summary']??$method.' '.$path),0,120),'metodo'=>$method,'ruta'=>substr($path,0,512)];if(count($out)>=100)break 2;
            }
        }
        return $out;
    }
    public function checks(int $id): array {
        $stmt=$this->db->prepare('SELECT ruta,http_status,exitoso,descripcion,created_at FROM api_external_checks WHERE connection_id=? ORDER BY id DESC LIMIT 50');$stmt->bind_param('i',$id);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;
    }
}
