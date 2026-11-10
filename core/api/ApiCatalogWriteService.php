<?php
/** Escritura controlada de catálogos sencillos; jamás recibe el nombre de DB desde HTTP. */
declare(strict_types=1);

final class IzzyApiCatalogWriteService
{
    private mysqli $db;
    private array $identity;

    private const CATALOGS = [
        'clientes' => ['id' => 'clientes_id', 'nombre_max' => 100, 'rtn_digits' => [13, 14]],
        'proveedores' => ['id' => 'proveedores_id', 'nombre_max' => 150, 'rtn_digits' => [14]],
    ];

    public function __construct(array $identity)
    {
        $db = (string) ($identity['db'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $db)) throw new DomainException('Empresa no autorizada.');
        require_once dirname(__DIR__) . '/configGenerales.php';
        require_once dirname(__DIR__) . '/mainModel.php';
        $this->db = (new mainModel())->connectionDBLocal($db);
        $this->db->set_charset('utf8mb4');
        $this->identity = $identity;
    }

    private function fields(string $module, array $body, bool $update): array
    {
        $cfg = self::CATALOGS[$module];
        $allowed = ['nombre', 'rtn', 'fecha', 'departamentos_id', 'municipios_id', 'localidad', 'telefono', 'correo', 'estado'];
        $unknown = array_diff(array_keys($body), $allowed);
        if ($unknown) throw new InvalidArgumentException('Campos no reconocidos: '.implode(', ', $unknown).'.');
        if (!$update && !isset($body['nombre'])) throw new InvalidArgumentException('Nombre obligatorio.');
        $out = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $body)) continue;
            if (in_array($key, ['departamentos_id', 'municipios_id', 'estado'], true)) {
                if (filter_var($body[$key], FILTER_VALIDATE_INT) === false) throw new InvalidArgumentException('Valor inválido: '.$key.'.');
                $value = (int) $body[$key];
                if ($value < 0 || ($key === 'estado' && !in_array($value, [0, 1, 2], true))) throw new InvalidArgumentException('Valor inválido: '.$key.'.');
            } else {
                if (!is_string($body[$key])) throw new InvalidArgumentException('Texto inválido: '.$key.'.');
                $value = trim($body[$key]);
                $max = ['nombre' => $cfg['nombre_max'], 'rtn' => 14, 'fecha' => 10, 'localidad' => 150, 'telefono' => 8, 'correo' => 70][$key];
                $length=function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value);
                if ($length > $max) throw new InvalidArgumentException('Campo demasiado largo: '.$key.'.');
                if ($key === 'nombre' && $value === '') throw new InvalidArgumentException('Nombre obligatorio.');
                if ($key === 'rtn' && $value !== '' && !in_array(strlen($value), $cfg['rtn_digits'], true)) throw new InvalidArgumentException('Longitud de RTN inválida.');
                if ($key === 'rtn' && $value !== '' && !ctype_digit($value)) throw new InvalidArgumentException('RTN debe ser numérico.');
                if ($key === 'telefono' && $value !== '' && !preg_match('/^[0-9]{1,8}$/D', $value)) throw new InvalidArgumentException('Teléfono inválido.');
                if ($key === 'correo' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Correo inválido.');
                if ($key === 'correo') $value = strtolower($value);
                if ($key === 'fecha' && $value !== '') {
                    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Fecha inválida.');
                }
            }
            $out[$key] = $value;
        }
        if ($update && !$out) throw new InvalidArgumentException('No hay campos para actualizar.');
        return $out;
    }

    private function validateRtn(string $module, string $rtn, int $except = 0): void
    {
        if ($rtn === '') return;
        $cfg = self::CATALOGS[$module];
        $sql = 'SELECT '.$cfg['id'].' FROM '.$module.' WHERE rtn=? AND '.$cfg['id'].'<>? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $rtn, $except);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($exists) throw new DomainException('Ya existe un registro con ese RTN.');
    }

    private function planAllows(string $module): void
    {
        // Se consulta el plan en la propia base de datos asociada al token.
        $result = $this->db->query('SELECT p.configuraciones FROM planes p INNER JOIN plan a ON a.planes_id=p.planes_id LIMIT 1');
        $row = $result ? $result->fetch_assoc() : null;
        if (!$row) throw new DomainException('No se pudo verificar el plan de la empresa.');
        $limits = json_decode((string) $row['configuraciones'], true);
        if (!is_array($limits)) throw new DomainException('Configuración del plan inválida.');
        if (!array_key_exists($module, $limits)) return;
        $max = (int) $limits[$module];
        if ($max <= 0) throw new DomainException('El plan no permite nuevos registros de '.$module.'.');
        $res = $this->db->query('SELECT COUNT(*) AS total FROM '.$module);
        if ((int) $res->fetch_assoc()['total'] >= $max) throw new DomainException('Se alcanzó el límite del plan para '.$module.'.');
    }

    public function save(string $module, array $body, ?int $id): array
    {
        if (!isset(self::CATALOGS[$module])) throw new InvalidArgumentException('Escritura no habilitada para este módulo.');
        $update = $id !== null;
        if ($update && $id <= 0) throw new InvalidArgumentException('ID inválido.');
        $fields = $this->fields($module, $body, $update);
        $lock = 'izzy_api_cat_' . substr(hash('sha256', (string)$this->identity['db'].':'.$module), 0, 40);
        $stmt = $this->db->prepare('SELECT GET_LOCK(?, 5) AS locked');
        $stmt->bind_param('s', $lock);
        $stmt->execute();
        $locked = (int) ($stmt->get_result()->fetch_assoc()['locked'] ?? 0);
        $stmt->close();
        if ($locked !== 1) throw new RuntimeException('El catálogo está ocupado; intente nuevamente.');
        try {
            $cfg = self::CATALOGS[$module];
            if ($update) {
                $stmt = $this->db->prepare('SELECT '.$cfg['id'].' FROM '.$module.' WHERE '.$cfg['id'].'=? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute(); $exists = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$exists) throw new OutOfBoundsException('Registro no encontrado.');
            } else {
                $this->planAllows($module);
                $stmt = $this->db->prepare('SELECT COALESCE(MAX('.$cfg['id'].'),0)+1 AS next_id FROM '.$module);
                $stmt->execute(); $id = (int)$stmt->get_result()->fetch_assoc()['next_id']; $stmt->close();
                $fields += ['rtn'=>'', 'fecha'=>date('Y-m-d'), 'departamentos_id'=>0, 'municipios_id'=>0,
                    'localidad'=>'', 'telefono'=>'', 'correo'=>'', 'estado'=>1];
            }
            if (isset($fields['rtn'])) $this->validateRtn($module, (string)$fields['rtn'], $update ? $id : 0);
            $types = ''; $values = [];
            if ($update) {
                $assignments = [];
                foreach ($fields as $key=>$value) { $assignments[]=$key.'=?'; $types.=is_int($value)?'i':'s'; $values[]=$value; }
                $types.='i'; $values[]=$id;
                $sql='UPDATE '.$module.' SET '.implode(', ', $assignments).' WHERE '.$cfg['id'].'=?';
            } else {
                $fields = [$cfg['id']=>$id] + $fields + ['colaboradores_id'=>max(1,(int)($this->identity['colaborador_id']??1)), 'fecha_registro'=>date('Y-m-d H:i:s')];
                if ($module==='clientes') $fields += ['empresa'=>'', 'eslogan'=>'', 'otra_informacion'=>'', 'whatsapp'=>''];
                foreach ($fields as $value) { $types.=is_int($value)?'i':'s'; $values[]=$value; }
                $sql='INSERT INTO '.$module.' ('.implode(', ',array_keys($fields)).') VALUES ('.implode(', ',array_fill(0,count($fields),'?')).')';
            }
            $stmt=$this->db->prepare($sql);
            $stmt->bind_param($types,...$values);
            $stmt->execute(); $stmt->close();
            return ['id'=>$id, 'modulo'=>$module, 'operacion'=>$update?'actualizado':'creado'];
        } finally {
            $stmt=$this->db->prepare('SELECT RELEASE_LOCK(?)');
            if ($stmt) { $stmt->bind_param('s',$lock); $stmt->execute(); $stmt->close(); }
        }
    }

    public function __destruct() { if (isset($this->db)) $this->db->close(); }
}
