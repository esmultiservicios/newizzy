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
    /** El HTTP exitoso se valida contra el indicador del contrato JSON, nunca contra el HTTP 200 aislado. */
    private static function safeJsonOk(?array $data): bool {
        if(!is_array($data))return false;
        if(array_key_exists('ok',$data))return $data['ok']===true;
        if(array_key_exists('success',$data))return $data['success']===true;
        return false;
    }
    /** Solo codigos de error con formato restringido; nunca mensajes ni cuerpos del proveedor. */
    private static function providerCode(?array $data): string {
        if(!is_array($data))return '';
        foreach ([$data['error_code']??null, $data['code']??null,
                  is_array($data['error']??null)?($data['error']['code']??null):null] as $candidate) {
            if(is_string($candidate) && preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{0,47}$/D',$candidate))return $candidate;
        }
        return '';
    }
    /**
     * Clasifica SOLO frases conocidas de ZYNKO. Nunca registra ni devuelve
     * el mensaje HTTP recibido: podría contener datos privados o secretos.
     */
    private static function classifyAuth401(?array $data): string {
        if(!is_array($data))return 'respuesta_no_identificada';
        $messages=[];
        foreach(['message','mensaje','error','detail','description'] as $field){
            $value=$data[$field]??null;
            if(is_string($value))$messages[]=$value;
            elseif(is_array($value)){
                foreach(['message','mensaje','detail','description'] as $sub){
                    if(isset($value[$sub])&&is_string($value[$sub]))$messages[]=$value[$sub];
                }
            }
        }
        foreach($messages as $message){
            $lower=strtolower($message);
            if(str_contains($lower,'falta authorization') ||
                (str_contains($lower,'authorization') && (str_contains($lower,'bearer') || str_contains($lower,'formato'))))
                return 'authorization_ausente_o_formato';
            if(str_contains($lower,'clave api inválida o revocada') || str_contains($lower,'clave api invalida o revocada'))
                return 'clave_no_encontrada_o_revocada';
            if(str_contains($lower,'clave api inválida') || str_contains($lower,'clave api invalida'))
                return 'formato_credencial_invalido';
        }
        return 'respuesta_no_identificada';
    }
    /**
     * Clasificación cerrada de rechazos 403 publicados por ZYNKO.
     * No se devuelve ni persiste el cuerpo JSON, origen, IP, token o encabezados.
     */
    private static function classifyAuth403(?array $data): string {
        if(!is_array($data))return 'rechazo_no_identificado';
        // ZYNKO v060: preferir códigos estables, sin depender del texto de error.
        $code=strtoupper(self::providerCode($data));
        $known=[
            'IP_NOT_ALLOWED'=>'ip_no_autorizada',
            'API_KEY_POLICY_DISABLED'=>'politica_desactivada',
            'ORIGIN_NOT_ALLOWED'=>'origen_no_autorizado',
            'HTTPS_REQUIRED'=>'https_requerido',
            'API_SCOPE_REQUIRED'=>'permiso_insuficiente',
            'API_PLAN_REQUIRED'=>'plan_sin_acceso_api',
            'API_SUBSCRIPTION_REQUIRED'=>'suscripcion_sin_asignar',
            'API_SUBSCRIPTION_INACTIVE'=>'suscripcion_inactiva',
            'API_PLAN_INACTIVE'=>'plan_inactivo',
        ];
        if(isset($known[$code]))return $known[$code];
        $messages=[];
        foreach(['message','mensaje','detail','description','error'] as $field){
            $value=$data[$field]??null;
            if(is_string($value))$messages[]=$value;
            if(is_array($value))foreach(['message','mensaje','detail','description'] as $sub){
                if(isset($value[$sub]) && is_string($value[$sub]))$messages[]=$value[$sub];
            }
        }
        foreach($messages as $message){
            $lower=strtolower($message);
            if(str_contains($lower,'política de acceso') && str_contains($lower,'desactivada'))return 'politica_desactivada';
            if(str_contains($lower,'dirección ip no está autorizada') || str_contains($lower,'direccion ip no esta autorizada') ||
                str_contains($lower,'ip pública de salida no está autorizada'))return 'ip_no_autorizada';
            if(str_contains($lower,'origen web no está autorizado') || str_contains($lower,'origen web no esta autorizado'))return 'origen_no_autorizado';
            if(str_contains($lower,'exige https'))return 'https_requerido';
            if(str_contains($lower,'ninguno de los permisos requeridos') || str_contains($lower,'no tiene el permiso'))return 'permiso_insuficiente';
            if(str_contains($lower,'plan') && (str_contains($lower,'api') || str_contains($lower,'suscripci')))return 'plan_sin_acceso_api';
        }
        return 'rechazo_no_identificado';
    }
    /**
     * Diagnóstico efímero v060 para el administrador IZZY: únicamente IPs válidas.
     * No persistir, registrar en logs ni exponer headers, tokens o el JSON original.
     */
    private static function safeIpRejection(?array $json,string $code): ?array {
        if(!is_array($json) || strtoupper($code)!=='IP_NOT_ALLOWED')return null;
        $data=$json['data']??null;
        if(!is_array($data))return null;
        $received=$data['received_ip']??null;
        $received=is_string($received) && filter_var($received,FILTER_VALIDATE_IP)?$received:null;
        $candidates=$data['allowed_ips']??null;
        $allowed=[];
        if(is_array($candidates))foreach(array_slice($candidates,0,30) as $value){
            if(!is_string($value) || strlen($value)>50)continue;
            $valid=(bool)filter_var($value,FILTER_VALIDATE_IP);
            if(!$valid && str_contains($value,'/')){
                [$base,$prefix]=array_pad(explode('/',$value,2),2,'');
                $max=filter_var($base,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)?32:(filter_var($base,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)?128:-1);
                $valid=$max>=0 && ctype_digit($prefix) && (int)$prefix<=$max;
            }
            if($valid && !in_array($value,$allowed,true))$allowed[]=$value;
        }
        // REMOTE_ADDR es el único origen documentado; no confiar en etiquetas libres.
        return [
            'received_ip'=>$received,
            'allowed_ips'=>$allowed,
            'source'=>($data['source']??null)==='REMOTE_ADDR'?'REMOTE_ADDR':'no_confirmado',
            'ip_de_rechazo_disponible'=>$received!==null,
        ];
    }
    private static function auth403Label(string $category): string {
        return match($category){
            'politica_desactivada'=>'ZYNKO: política de acceso de la API Key desactivada.',
            'ip_no_autorizada'=>'ZYNKO: IP de salida de IZZY no autorizada por la política de la API Key.',
            'origen_no_autorizado'=>'ZYNKO: origen web no autorizado.',
            'https_requerido'=>'ZYNKO: política HTTPS no satisfecha.',
            'permiso_insuficiente'=>'ZYNKO: faltan permisos para esta consulta.',
            'plan_sin_acceso_api'=>'ZYNKO: el plan asignado no incluye el módulo API.',
            'suscripcion_sin_asignar'=>'ZYNKO: la empresa de la API Key no tiene una suscripción asignada.',
            'suscripcion_inactiva'=>'ZYNKO: la suscripción de la empresa de la API Key no está vigente.',
            'plan_inactivo'=>'ZYNKO: el plan asignado a la empresa está inactivo o no existe.',
            default=>'ZYNKO: HTTP 403; mensaje no reconocido, requiere diagnóstico del proveedor.'
        };
    }
    /** Solo indicadores booleanos, sin contenido, hash ni longitud del secreto. */
    private static function credentialShape(string $token): array {
        return [
            'descifrado_no_vacio'=>$token!=='',
            'espacios_extremos'=>$token!==trim($token),
            'contiene_prefijo_bearer'=>preg_match('/^bearer\s+/i',$token)===1,
            'comillas_externas'=>strlen($token)>=2 &&
                ((($token[0]??'')==='"' && substr($token,-1)==='"') || (($token[0]??'')==="'" && substr($token,-1)==="'")),
            'espacios_internos'=>preg_match('/\s/',$token)===1,
            'caracteres_control'=>preg_match('/[\x00-\x1f\x7f]/',$token)===1
        ];
    }
    private static function auth401Label(string $reason): string {
        return match($reason){
            'authorization_ausente_o_formato'=>'ZYNKO: falta Authorization Bearer o formato del encabezado inválido.',
            'formato_credencial_invalido'=>'ZYNKO: la API Key no cumple el formato esperado.',
            'clave_no_encontrada_o_revocada'=>'ZYNKO: no encontró una API Key activa coincidente o fue revocada.',
            default=>'ZYNKO: HTTP 401 sin categoría reconocible en el JSON.'
        };
    }
    /** Conserva la selección explícita y verifica que el canal esté conectado. */
    private function identifyChannel(array $connection,array $decoded): array {
        $channels=$decoded['data']['channels']??null;
        if(!is_array($channels))return ['canal_detectado'=>false,'motivo'=>'La respuesta no contiene data.channels.'];
        $matching=[];
        foreach($channels as $channel){
            if(!is_array($channel) || ($channel['type']??null)!=='whatsapp' || ($channel['status']??null)!=='connected')continue;
            $candidate=(string)($channel['id']??'');
            if(preg_match('/^[1-9][0-9]*$/D',$candidate))$matching[]=$candidate;
        }
        $matching=array_values(array_unique($matching));
        $selected=trim((string)$connection['canal_id']);
        if($selected!=='' && !in_array($selected,$matching,true))
            return ['canal_detectado'=>false,'motivo'=>'El canal configurado no está conectado o esta clave no tiene acceso. IDs conectados: '.($matching?implode(', ',array_slice($matching,0,15)):'ninguno').'.'];
        if($selected===''){
            if(count($matching)!==1)return ['canal_detectado'=>false,'canales_whatsapp'=>count($matching),
                'motivo'=>$matching?'Configure el ID de uno de los canales WhatsApp conectados: '.implode(', ',array_slice($matching,0,15)).'.':'No hay un canal WhatsApp conectado para esta clave.'];
            $selected=$matching[0];
            $connectionId=(int)$connection['id'];$key=(string)$connection['token_cifrado'];
            $stmt=$this->db->prepare("UPDATE api_external_connections SET canal_id=? WHERE id=? AND archivado=0 AND canal_id='' AND token_cifrado=?");
            $stmt->bind_param('sis',$selected,$connectionId,$key);$stmt->execute();$stmt->close();
        }
        return ['canal_detectado'=>true,'canales_whatsapp'=>count($matching),'canal_id'=>$selected];
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
        $options=json_decode($config,true,32,JSON_THROW_ON_ERROR);
        unset($options['izzy_document_sharing']);
        if(strtoupper($provider)==='ZYNKO' && $auth==='bearer' && $url==='https://zynkocloud.app/api.php'){
            $types=[];
            foreach(['factura','cotizacion'] as $type)
                if(($input['zin_doc_'.$type]??'')==='1')$types[]=$type;
            if($types){
                $tenant=(new mainModel())->connection();
                $database=(string)$tenant->query('SELECT DATABASE()')->fetch_row()[0];
                $tenant->close();
                $company=(int)($_SESSION['empresa_id_sd']??0);
                if($database===''||$company<1)throw new DomainException('Empresa de envíos no verificada.');
                $options['izzy_document_sharing']=['database'=>$database,'company'=>$company,'types'=>$types];
            }
        }
        $config=json_encode((object)$options,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $secret=$token!==''?$this->encrypt($token):($id===0?'':null);
        if ($id>0) {
            $stmt=$this->db->prepare('UPDATE api_external_connections SET nombre=?,proveedor=?,categoria=?,descripcion=?,url_base=?,tipo_auth=?,canal_id=?,configuracion_json=?,token_cifrado=COALESCE(?,token_cifrado) WHERE id=? AND archivado=0');
            $stmt->bind_param('sssssssssi',$name,$provider,$category,$description,$url,$auth,$channel,$config,$secret,$id);
        } else {
            $stmt=$this->db->prepare('INSERT INTO api_external_connections (nombre,proveedor,categoria,descripcion,url_base,tipo_auth,canal_id,configuracion_json,token_cifrado,activo) VALUES (?,?,?,?,?,?,?,?,?,1)');
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
    /** Envía únicamente a destinatarios de prueba autorizados en el entorno PHP. */
    private static function testRecipients(string $input, string $mode, string $allowed): array {
        if (!in_array($mode,['single','bulk'],true)) throw new InvalidArgumentException('Modo de envío no válido.');
        $numbers=preg_split('/[\r\n,;]+/',trim($input),-1,PREG_SPLIT_NO_EMPTY);
        $allowlist=preg_split('/[\s,;]+/',trim($allowed),-1,PREG_SPLIT_NO_EMPTY);
        $allowlist=array_map(static fn($number)=>ltrim($number,'+'),$allowlist);
        if (!$allowlist) throw new DomainException('Falta configurar IZZY_ZYNKO_TEST_NUMBERS en el servidor de IZZY.');
        $normalized=[];
        foreach($numbers as $number){
            $number=trim($number);
            if(!preg_match('/^\+?[1-9][0-9]{6,14}$/D',$number)) throw new InvalidArgumentException('Use números internacionales completos, uno por línea.');
            $number=ltrim($number,'+');
            if(!in_array($number,$allowlist,true)) throw new DomainException('Hay un destinatario fuera de la lista de pruebas autorizadas.');
            if(in_array($number,$normalized,true)) throw new InvalidArgumentException('Hay destinatarios repetidos.');
            $normalized[]=$number;
        }
        $count=count($normalized);
        if(($mode==='single' && $count!==1)||($mode==='bulk' && ($count<2||$count>100)))
            throw new InvalidArgumentException('El envío individual requiere un destinatario y el masivo entre 2 y 100.');
        return $normalized;
    }

    private static function testAllowlist(): string {
        return (string)(getenv('IZZY_ZYNKO_TEST_NUMBERS') ?: ($_ENV['IZZY_ZYNKO_TEST_NUMBERS']??''));
    }

    private static function validUuid(string $value): bool {
        return preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/D',$value)===1;
    }

    private function whatsappTestRow(int $id, string $operation): ?array {
        if(!self::validUuid($operation)) throw new InvalidArgumentException('Operación no válida.');
        $stmt=$this->db->prepare('SELECT * FROM api_zynko_test_sends WHERE connection_id=? AND operation_id=?');
        $stmt->bind_param('is',$id,$operation);$stmt->execute();
        $row=$stmt->get_result()->fetch_assoc();$stmt->close();
        return $row;
    }

    private static function testSummary(array $row): array {
        return [
            'id'=>$row['operation_id'], 'mode'=>$row['mode'], 'state'=>$row['state'],
            'http_status'=>(int)$row['http_status'], 'code'=>$row['provider_code'],
            'detail'=>$row['detail'], 'created_at'=>$row['created_at'],
            'messages'=>json_decode((string)$row['results_json'],true) ?: [],
        ];
    }

    public function assertWhatsappTestReady(int $id): void {
        $this->zynkoConnection($id);
        $this->db->query('SELECT operation_id, connection_id, credential_ref, payload_cifrado,
            consent_ref_cifrada, actor_id, mode, state, http_status, provider_code, detail, results_json, created_at
            FROM api_zynko_test_sends LIMIT 0')->free();
        if(trim(self::testAllowlist())==='') throw new DomainException('El servidor todavía no tiene números de prueba autorizados.');
    }

    public function whatsappTests(int $id): array {
        $this->conn($id);
        $stmt=$this->db->prepare('SELECT * FROM api_zynko_test_sends WHERE connection_id=? ORDER BY created_at DESC,operation_id DESC LIMIT 30');
        $stmt->bind_param('i',$id);$stmt->execute();
        $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
        return array_map([self::class,'testSummary'],$rows);
    }

    public function sendWhatsappTest(int $id, string $operation, int $actor, ?array $input): array {
        if($actor<1) throw new DomainException('No se pudo identificar al operador.');
        $connection=$this->zynkoConnection($id);
        $operationRow=$this->whatsappTestRow($id,$operation);
        $credential=hash('sha256',(string)$connection['token_cifrado']);
        if(!$operationRow && $input===null) throw new DomainException('No se encontró la operación de envío.');

        if($input!==null){
            $mode=(string)($input['mode']??'');
            $numbers=self::testRecipients((string)($input['recipients']??''),$mode,self::testAllowlist());
            $message=trim((string)($input['message']??''));
            $reference=trim((string)($input['consent_reference']??''));
            if($message==='' || mb_strlen($message,'UTF-8')>4096) throw new InvalidArgumentException('Escriba un mensaje de 1 a 4096 caracteres.');
            if(($input['consent']??'')!=='1' || $reference==='' || mb_strlen($reference,'UTF-8')>200)
                throw new InvalidArgumentException('Confirme la autorización de todos los destinatarios e indique su referencia.');
            if(!preg_match('/^[1-9][0-9]*$/D',(string)$connection['canal_id']))
                throw new DomainException('Primero ejecute Probar conexión y confirme el canal WhatsApp conectado.');
            $payload=['channel_id'=>(int)$connection['canal_id'],'to'=>$mode==='single'?$numbers[0]:$numbers,'message'=>$message];
            if($mode==='bulk')$payload['consent_confirmed']=true;
            $json=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

            if(!$operationRow){
                $probe=$this->zynkoTransport($connection,'v1/channels');
                if(!$probe['ok'] || empty($probe['canal']['canal_detectado'])){
                    throw new DomainException($probe['ok']?($probe['canal']['motivo']??'Canal WhatsApp no disponible.'):$probe['detalle']);
                }
                if((string)$payload['channel_id']!==$probe['canal']['canal_id'])
                    throw new DomainException('El canal cambió. Vuelva a revisar la conexión antes de enviar.');
                $encrypted=$this->encrypt($json);
                $evidence=$this->encrypt($reference);
                $stmt=$this->db->prepare('INSERT INTO api_zynko_test_sends
                    (connection_id,operation_id,actor_id,mode,credential_ref,payload_cifrado,consent_ref_cifrada)
                    VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE operation_id=operation_id');
                $stmt->bind_param('isissss',$id,$operation,$actor,$mode,$credential,$encrypted,$evidence);
                $stmt->execute();$stmt->close();
                $operationRow=$this->whatsappTestRow($id,$operation);
            }

            if(!hash_equals($this->decrypt($operationRow['payload_cifrado']),$json) ||
                !hash_equals($this->decrypt($operationRow['consent_ref_cifrada']),$reference))
                throw new InvalidArgumentException('La operación ya existe con otro contenido. Consulte su historial antes de crear una nueva.');
        }

        if((int)$operationRow['actor_id']!==$actor) throw new DomainException('Solo el operador original puede reintentar este envío.');
        if($operationRow['state']==='accepted') return self::testSummary($operationRow);
        if(!hash_equals($operationRow['credential_ref'],$credential))
            throw new DomainException('La clave de esta conexión cambió. Reintentar con otra clave podría duplicar el envío.');

        $json=$this->decrypt($operationRow['payload_cifrado']);
        $payload=json_decode($json,true,16,JSON_THROW_ON_ERROR);
        self::testRecipients(implode("\n",(array)$payload['to']),$operationRow['mode'],self::testAllowlist());
        $response=$this->zynkoTransport($connection,'v1/whatsapp/send',$json,'izzy-test-'.$operation);
        $state=$response['ok']?'accepted':'pending';
        $http=$response['http_status'];$code=$response['codigo_proveedor'];$detail=$response['detalle'];
        $results=json_encode($response['messages']??[],JSON_THROW_ON_ERROR);
        $stmt=$this->db->prepare("UPDATE api_zynko_test_sends SET state=?,http_status=?,provider_code=?,detail=?,results_json=?
            WHERE connection_id=? AND operation_id=? AND state='pending'");
        $stmt->bind_param('sisssis',$state,$http,$code,$detail,$results,$id,$operation);
        $stmt->execute();$stmt->close();
        return self::testSummary($this->whatsappTestRow($id,$operation));
    }

    public function whatsappTestStatus(int $id,string $operation,string $uuid): array {
        $connection=$this->zynkoConnection($id);
        $row=$this->whatsappTestRow($id,$operation);
        if(!$row || $row['state']!=='accepted' || !self::validUuid($uuid)) throw new DomainException('Envío o UUID no disponible.');
        if(!hash_equals($row['credential_ref'],hash('sha256',(string)$connection['token_cifrado'])))
            throw new DomainException('El estado requiere la misma API Key que registró el envío.');
        $messages=self::testSummary($row)['messages'];
        foreach($messages as $index=>$message){
            if($message['uuid']!==$uuid)continue;
            $response=$this->zynkoTransport($connection,'v1/whatsapp/status',null,null,$uuid);
            if($response['ok']){
                $path='$['.$index.'].status';$status=$response['message_status'];
                $stmt=$this->db->prepare('UPDATE api_zynko_test_sends SET results_json=JSON_SET(results_json,?,?)
                    WHERE connection_id=? AND operation_id=? AND JSON_UNQUOTE(JSON_EXTRACT(results_json,?))=?');
                $stmt->bind_param('ssisss',$path,$status,$id,$operation,$path,$message['status']);
                $stmt->execute();$stmt->close();
            }
            return $response;
        }
        throw new DomainException('El UUID no pertenece a esta operación.');
    }

    private static function queuedMessages(?array $decoded,array $expected): ?array {
        $messages=$decoded['data']['messages']??null;
        if(!is_array($messages)||count($messages)!==count($expected))return null;
        $results=[];$used=[];
        foreach($messages as $record){
            $to=is_array($record)?(string)($record['to']??''):'';
            $uuid=is_array($record)?(string)($record['message_uuid']??''):'';
            if(!in_array($to,$expected,true)||isset($results[$to])||isset($used[$uuid])||!self::validUuid($uuid))return null;
            $used[$uuid]=true;
            $results[$to]=['recipient'=>'••••'.substr($to,-4),'uuid'=>$uuid,'status'=>'queued'];
        }
        return array_values($results);
    }

    private function zynkoConnection(int $id): array {
        $row=$this->conn($id);
        if (strtoupper(trim((string)$row['proveedor']))!=='ZYNKO' || $row['tipo_auth']!=='bearer' || $row['url_base']!=='https://zynkocloud.app/api.php')
            throw new DomainException('Conector ZYNKO no autorizado para esta conexión.');
        if (!(int)$row['activo']) throw new DomainException('Active primero la conexión para comprobarla.');
        return $row;
    }

    public function documentConnection(string $database,int $company,string $type): ?array {
        $rows=$this->db->query("SELECT * FROM api_external_connections WHERE activo=1 AND archivado=0
            AND UPPER(TRIM(proveedor))='ZYNKO' AND tipo_auth='bearer' AND url_base='https://zynkocloud.app/api.php'");
        $match=null;
        while($row=$rows->fetch_assoc()){
            $scope=json_decode($row['configuracion_json'],true)['izzy_document_sharing']??[];
            if(($scope['database']??null)!==$database || ($scope['company']??null)!==$company ||
                !in_array($type,(array)($scope['types']??[]),true))continue;
            if($match)throw new DomainException('Hay varias conexiones habilitadas. Deje una sola para este documento.');
            $match=$row;
        }
        return $match;
    }

    public function dispatchDocument(array $connection,string $database,int $company,string $type,int $id,
        int $actor,string $phone,string $url): array {
        $connectionId=(int)$connection['id'];
        $key=hash('sha256',json_encode([$database,$company,$type,$id],JSON_THROW_ON_ERROR));
        $credential=hash('sha256',(string)$connection['token_cifrado']);
        $load=function()use($key): ?array {
            $stmt=$this->db->prepare('SELECT * FROM api_zynko_document_dispatch WHERE dispatch_key=?');
            $stmt->bind_param('s',$key);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
            return $row;
        };
        $previous=$load();
        if(!$previous){
            $probe=$this->zynkoTransport($connection,'v1/channels');
            if(!$probe['ok']||empty($probe['canal']['canal_detectado']))
                throw new DomainException($probe['canal']['motivo']??$probe['detalle']);
            $label=$type==='factura'?'factura':'cotización';
            $encrypted=$this->encrypt(json_encode([
                'channel_id'=>(int)$probe['canal']['canal_id'],'to'=>$phone,
                'message'=>'Su '.$label.' de IZZY está disponible: '.$url
            ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            $stmt=$this->db->prepare('INSERT INTO api_zynko_document_dispatch
                (dispatch_key,connection_id,actor_id,credential_ref,payload_cifrado) VALUES (?,?,?,?,?)
                ON DUPLICATE KEY UPDATE dispatch_key=dispatch_key');
            $stmt->bind_param('siiss',$key,$connectionId,$actor,$credential,$encrypted);
            $stmt->execute();$stmt->close();
            $previous=$load();
        }
        if(!empty($previous['message_uuid']))return ['ok'=>true,'detail'=>'ZYNKO ya aceptó este documento. UUID: '.$previous['message_uuid']];
        if((int)$previous['connection_id']!==$connectionId || !hash_equals($previous['credential_ref'],$credential))
            throw new DomainException('La conexión cambió desde el primer intento. Revise el historial de ZYNKO.');
        $json=$this->decrypt($previous['payload_cifrado']);
        if(json_decode($json,true,16,JSON_THROW_ON_ERROR)['to']!==$phone)
            throw new DomainException('El teléfono cambió desde el primer intento. Revise el historial de ZYNKO.');
        $result=$this->zynkoTransport($connection,'v1/whatsapp/send',$json,'izzy-doc-'.$key);
        if(!$result['ok'])return ['ok'=>false,'detail'=>$result['detalle'],'code'=>$result['codigo_proveedor']];
        $uuid=$result['messages'][0]['uuid'];
        $stmt=$this->db->prepare('UPDATE api_zynko_document_dispatch SET message_uuid=? WHERE dispatch_key=?');
        $stmt->bind_param('ss',$uuid,$key);$stmt->execute();$stmt->close();
        return ['ok'=>true,'detail'=>'Documento en cola en ZYNKO. UUID: '.$uuid];
    }


    public function request(int $id,string $route): array {
        if(!in_array($route,['v1/channels','v1/whatsapp/history'],true))
            throw new InvalidArgumentException('Consulta de conexión no admitida.');
        return $this->zynkoTransport($this->zynkoConnection($id),$route);
    }

    private function zynkoTransport(array $row,string $route,?string $json=null,?string $idempotency=null,?string $uuid=null): array {
        $id=(int)$row['id'];
        if(!function_exists('curl_init'))throw new RuntimeException('CURL_MISSING: PHP necesita la extensión cURL para realizar conexiones HTTPS.');
        $url='https://zynkocloud.app/api.php?r='.rawurlencode($route);
        if($route==='v1/whatsapp/history')$url.='&page=1&per_page=20';
        if($uuid!==null)$url.='&message_uuid='.rawurlencode($uuid);
        $token=$this->decrypt((string)$row['token_cifrado']);
        if($token==='' || preg_match('/[\r\n\x00]/',$token)) {
            throw new DomainException('Credencial descifrada vacía o con caracteres de control. No se realizó la solicitud.');
        }
        // No cambiar el token descifrado: diagnosticar su forma sin revelar contenido.
        $credentialShape=self::credentialShape($token);
        // La cabecera sale solo desde PHP. No se registra ni se devuelve la credencial.
        $headers=['Authorization: Bearer '.$token,'Accept: application/json'];
        if($json!==null){
            $headers[]='Content-Type: application/json';
            $headers[]='Idempotency-Key: '.$idempotency;
        }
        $ch=curl_init($url);
        if($ch===false)throw new RuntimeException('CURL_MISSING: No se pudo inicializar cURL.');
        $body='';$responseTooLarge=false;
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_HTTPHEADER=>$headers,
            CURLINFO_HEADER_OUT=>true,
            CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_MAXREDIRS=>0,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk)use(&$body,&$responseTooLarge):int {
                if(strlen($body)+strlen($chunk)>1048576){$responseTooLarge=true;return 0;}
                $body.=$chunk;return strlen($chunk);
            }]);
        if($json!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$json]);
        $executed=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $curlErr=$executed===false?curl_errno($ch):0;
        // Inspección TRANSITORIA de los encabezados salientes preparados por cURL.
        // Contienen el secreto: nunca registrar, devolver ni persistir su contenido.
        $outgoing=(string)curl_getinfo($ch,CURLINFO_HEADER_OUT);
        $authorizationLines=[];
        foreach(preg_split('/\r?\n/', $outgoing) as $line){
            if(strncasecmp($line,'Authorization:',14)===0)$authorizationLines[]=$line;
        }
        $bearerExact=count($authorizationLines)===1 &&
            hash_equals($headers[0],$authorizationLines[0]);
        $sentAuthorization=count($authorizationLines)===1;
        $effectiveUrl=(string)curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
        $redirectCount=(int)curl_getinfo($ch,CURLINFO_REDIRECT_COUNT);
        $effectiveUrlExpected=hash_equals($url,$effectiveUrl);
        $httpRedirect=$status>=300 && $status<400;
        $localTransport=[
            'autorizacion_en_solicitud_curl'=>$sentAuthorization,
            'bearer_exacto_en_solicitud_curl'=>$bearerExact,
            'url_efectiva'=>$effectiveUrlExpected?$url:'URL inesperada (no divulgada)',
            'url_esperada_coincide'=>$effectiveUrlExpected,
            'redirecciones_seguidas'=>$redirectCount,
            'seguimiento_redirecciones_habilitado'=>false,
            'respuesta_redireccion'=>$httpRedirect,
            'codigo_http'=>$status,
            'error_curl'=>$curlErr
        ];
        $outgoing='';$authorizationLines=[];unset($outgoing,$authorizationLines);
        curl_close($ch);unset($token,$headers);
        $decoded=json_decode($body,true,32);
        $validJson=json_last_error()===JSON_ERROR_NONE && is_array($decoded);
        $code=self::providerCode($validJson?$decoded:null);
        $auth401=$status===401?self::classifyAuth401($validJson?$decoded:null):null;
        $auth403=$status===403?self::classifyAuth403($validJson?$decoded:null):null;
        // Solo se devuelve al administrador y nunca entra al historial SQL.
        $ipDiagnostics=($status===403 && $auth403==='ip_no_autorizada')
            ?self::safeIpRejection($validJson?$decoded:null,$code):null;
        $ok=$curlErr===0 && $status>=200 && $status<300 && self::safeJsonOk($validJson?$decoded:null);
        $channelInfo=[];$messages=[];$messageStatus=null;
        if($ok&&$route==='v1/channels')$channelInfo=$this->identifyChannel($row,$decoded);
        if($route==='v1/whatsapp/send'){
            $expected=(array)(json_decode((string)$json,true,16,JSON_THROW_ON_ERROR)['to']??[]);
            $queued=$ok && $status===202 ? self::queuedMessages($decoded,$expected):null;
            $ok=$queued!==null;
            $messages=$queued??[];
        }
        if($route==='v1/whatsapp/status' && $ok){
            $reported=$decoded['data']['status']??null;
            $ok=($decoded['data']['message_uuid']??null)===$uuid && is_string($reported) &&
                in_array($reported,['queued','sending','retrying','sent','delivered','read','failed','partial','uncertain'],true);
            if($ok)$messageStatus=$reported;
        }
        if($responseTooLarge)$logMessage='Respuesta de ZYNKO superior al límite de 1 MiB.';
        elseif($curlErr)$logMessage='Fallo HTTPS cURL (código '.$curlErr.').';
        elseif($status===401)$logMessage='HTTP 401: '.self::auth401Label((string)$auth401);
        elseif($status===403)$logMessage='HTTP 403: '.self::auth403Label((string)$auth403);
        elseif($status===503 && $code==='API_PLAN_LOOKUP_FAILED')$logMessage='HTTP 503: ZYNKO no pudo verificar la suscripción.';
        elseif(!$validJson)$logMessage='HTTP '.$status.': respuesta de ZYNKO no es JSON válido.';
        elseif($status===404 && $route==='v1/whatsapp/send')$logMessage='ZYNKO no encontró el canal WhatsApp para esta clave.';
        elseif($status===409 && $route==='v1/whatsapp/send')$logMessage='ZYNKO rechazó el canal o encontró un conflicto de idempotencia.';
        elseif($status===404 && $route==='v1/whatsapp/status')$logMessage='ZYNKO no encontró el mensaje para esta clave.';
        elseif($ok && $route==='v1/whatsapp/send')$logMessage='HTTP 202: en cola en ZYNKO.';
        elseif($ok)$logMessage='HTTP '.$status.': autenticación y respuesta JSON confirmadas.';
        else $logMessage='HTTP '.$status.': respuesta JSON no confirmó éxito de la operación.';
        if($code!=='')$logMessage.=' Código: '.$code.'.';
        // DB_Cambios.sql: api_external_checks.descripcion VARCHAR(160),
        // api_external_connections.ultimo_resultado VARCHAR(160).
        // Un HTTP 401 debe registrarse como error del proveedor, NO provocar
        // una excepcion SQL 1406 por exceso de longitud del diagnostico.
        $logMessage=mb_strimwidth($logMessage,0,160,'','UTF-8');
        $stmt=$this->db->prepare('INSERT INTO api_external_checks (connection_id,ruta,http_status,exitoso,descripcion) VALUES (?,?,?,?,?)');
        $flag=$ok?1:0;$stmt->bind_param('isiis',$id,$route,$status,$flag,$logMessage);$stmt->execute();$stmt->close();
        if ($route==='v1/channels') {
            $stmt=$this->db->prepare('UPDATE api_external_connections SET ultima_prueba=NOW(),ultimo_resultado=? WHERE id=?');
            $stmt->bind_param('si',$logMessage,$id);$stmt->execute();$stmt->close();
        }
        // Nunca exponer bodies completos: historial y estados pueden incluir teléfonos y documentos.
        return ['ok'=>$ok,'http_status'=>$status,'detalle'=>$logMessage,'canal'=>$channelInfo,
            'json_valido'=>$validJson,'codigo_proveedor'=>$code,
            'encabezado_bearer_configurado'=>true,
            'diagnostico_401'=>$status===401?self::auth401Label((string)$auth401):null,
            'categoria_rechazo_401'=>$auth401,
            'categoria_rechazo_403'=>$auth403,
            'diagnostico_ip'=>$ipDiagnostics,
            'integridad_credencial'=>$credentialShape,
            'diagnostico_transporte'=>$localTransport,
            'messages'=>$messages,'message_status'=>$messageStatus,
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
