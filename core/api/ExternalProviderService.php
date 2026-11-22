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
    private function key(): string {
        $secret=trim((string)getenv('IZZY_EXTERNAL_API_ENCRYPTION_KEY'));
        if (!preg_match('/^[a-f0-9]{64}$/i',$secret)) throw new RuntimeException('Configure IZZY_EXTERNAL_API_ENCRYPTION_KEY (64 caracteres hexadecimales) solo en el servidor.');
        return hex2bin($secret);
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
    public function listing(): array {
        $r=$this->db->query('SELECT id,nombre,proveedor,categoria,descripcion,url_base,tipo_auth,canal_id,activo,ultima_prueba,ultimo_resultado,archivado,created_at FROM api_external_connections ORDER BY id DESC');
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
        $token=trim((string)($input['token']??''));
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
            $stmt=$this->db->prepare('UPDATE api_external_connections SET nombre=?,proveedor=?,categoria=?,descripcion=?,url_base=?,tipo_auth=?,canal_id=?,token_cifrado=COALESCE(?,token_cifrado) WHERE id=? AND archivado=0');
            $stmt->bind_param('ssssssssi',$name,$provider,$category,$description,$url,$auth,$channel,$secret,$id);
        } else {
            $stmt=$this->db->prepare('INSERT INTO api_external_connections (nombre,proveedor,categoria,descripcion,url_base,tipo_auth,canal_id,token_cifrado) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->bind_param('ssssssss',$name,$provider,$category,$description,$url,$auth,$channel,$secret);
        }
        $stmt->execute();$result=$id>0?$id:$stmt->insert_id;$stmt->close();return (int)$result;
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
    /** Respuesta aceptada NO implica entrega en WhatsApp. */
    public function request(int $id,string $route,string $method='GET',?array $payload=null,?string $idempotency=null): array {
        $row=$this->conn($id);
        if ($row['proveedor']!=='ZYNKO' || $row['tipo_auth']!=='bearer' || $row['url_base']!=='https://zynkocloud.app/api.php') throw new DomainException('Las pruebas de conexión de este proveedor requieren un conector autorizado.');
        if (!(int)$row['activo']) throw new DomainException('La conexión está desactivada.');
        if (!in_array($route,['v1/channels','v1/whatsapp/status','v1/whatsapp/send'],true)) throw new DomainException('Ruta ZYNKO no autorizada.');
        if ($route==='v1/whatsapp/send' && ($method!=='POST' || !$idempotency)) throw new DomainException('Un envío requiere POST e Idempotency-Key.');
        if ($route!=='v1/whatsapp/send' && $method!=='GET') throw new DomainException('Método no autorizado.');
        $url='https://zynkocloud.app/api.php?r='.rawurlencode($route);
        $headers=['Authorization: Bearer '.$this->decrypt($row['token_cifrado']),'Accept: application/json'];
        if ($method==='POST') { $headers[]='Content-Type: application/json';$headers[]='Idempotency-Key: '.$idempotency; }
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_MAXREDIRS=>0]);
        if ($method==='POST') curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=$body===false?'Fallo de transporte (cURL '.curl_errno($ch).')':'';curl_close($ch);
        $decoded=is_string($body)?json_decode($body,true):null;
        $ok=$error==='' && $status>=200 && $status<300 && is_array($decoded);
        // Nunca registrar cuerpos HTTP: pueden contener tokens, teléfonos o enlaces confidenciales.
        $stmt=$this->db->prepare('INSERT INTO api_external_checks (connection_id,ruta,http_status,exitoso,descripcion) VALUES (?,?,?,?,?)');
        $logMessage=$error?:($ok?'Respuesta HTTP recibida':'HTTP inesperado o JSON inválido');$flag=$ok?1:0;
        $stmt->bind_param('isiis',$id,$route,$status,$flag,$logMessage);$stmt->execute();$stmt->close();
        $stmt=$this->db->prepare('UPDATE api_external_connections SET ultima_prueba=NOW(),ultimo_resultado=? WHERE id=?');$stmt->bind_param('si',$logMessage,$id);$stmt->execute();$stmt->close();
        return ['ok'=>$ok,'http_status'=>$status,'respuesta'=>$decoded,'estado'=>'aceptacion_no_es_entrega'];
    }
    public function checks(int $id): array {
        $stmt=$this->db->prepare('SELECT ruta,http_status,exitoso,descripcion,created_at FROM api_external_checks WHERE connection_id=? ORDER BY id DESC LIMIT 50');$stmt->bind_param('i',$id);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;
    }
}
