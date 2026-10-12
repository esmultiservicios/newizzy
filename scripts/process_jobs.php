<?php
// scripts/process_jobs.php

/*
    Procesa jobs pendientes creados desde el registro autónomo.
    Este script debe ejecutarse por CLI/manual o por Cron Job.
*/

if (php_sapi_name() === 'cli') {
    $appUrl = getenv('APP_URL');

    if (!$appUrl) {
        $appUrl = 'https://izzycloud.app/';
    }

    $partesUrl = parse_url($appUrl);

    $_SERVER['HTTPS'] = 'on';
    $_SERVER['HTTP_HOST'] = $partesUrl['host'] ?? 'izzycloud.app';
    $_SERVER['SERVER_NAME'] = $partesUrl['host'] ?? 'izzycloud.app';
    $_SERVER['SERVER_PORT'] = isset($partesUrl['port']) ? (string)$partesUrl['port'] : '443';
    $_SERVER['REQUEST_URI'] = $partesUrl['path'] ?? '/';
}

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/core/configGenerales.php';
require_once APP_ROOT . '/core/mainModel.php';
require_once APP_ROOT . '/core/registro/DatabaseSetup.php';
require_once APP_ROOT . '/core/correo/sendEmail.php';
require_once APP_ROOT . '/core/registro/ProvisioningSync.php';

class JobProcessor {
    private $conexion;
    private $dbSetup;
    private $mainModel;

    public function __construct() {
        $this->mainModel = new mainModel();
        $this->conexion = $this->mainModel->connection();
        $this->dbSetup = new DatabaseSetup();

        if ($this->conexion->connect_error) {
            throw new Exception('Error de conexión: ' . $this->conexion->connect_error);
        }
    }

    public function processPendingJobs() {
        try {
            // Bloqueo global para impedir dos CRON simultáneos.
            $lock = $this->conexion->query("SELECT GET_LOCK('izzy_registration_jobs', 0) AS obtained");
            $lockRow = $lock ? $lock->fetch_assoc() : null;
            if (!$lockRow || (int)$lockRow['obtained'] !== 1) {
                return ['success'=>true,'processed'=>0,'failed'=>0,'total'=>0,'message'=>'Otro worker está activo'];
            }
            try {
            $this->recoverStaleJobs();
            $jobs = $this->getPendingJobs();
            $processed = 0;
            $failed = 0;

            foreach ($jobs as $job) {
                $this->markJobAsProcessing($job['id']);

                try {
                    if ($this->processJob($job)) {
                        $this->markJobAsCompleted($job['id']);
                        $processed++;
                    } else {
                        $this->handleJobFailure($job['id'], 'Error desconocido al procesar el job');
                        $failed++;
                    }
                } catch (Exception $e) {
                    $this->handleJobFailure($job['id'], $e->getMessage());
                    $failed++;
                    error_log('Error procesando job ID ' . $job['id'] . ': ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($jobs)
            ];
            } finally {
                $this->conexion->query("SELECT RELEASE_LOCK('izzy_registration_jobs')");
            }

        } catch (Exception $e) {
            error_log('Error en processPendingJobs: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed' => 0,
                'failed' => 0,
                'total' => 0
            ];

        } finally {
            if ($this->conexion) {
                $this->conexion->close();
            }
        }
    }

    private function recoverStaleJobs() {
        // Requiere impedir que dos invocaciones del cron procesen el mismo lote.
        $this->conexion->query("UPDATE jobs_queue SET status=IF(attempts >= max_attempts,'failed','pending'), error_message='Proceso interrumpido; pendiente de revisión/reintento' WHERE status='processing' AND processed_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    }

    private function getPendingJobs() {
        $query = "
            SELECT
                id,
                job_type,
                data,
                status,
                attempts,
                max_attempts,
                created_at,
                processed_at,
                error_message
            FROM jobs_queue
            WHERE status = 'pending'
              AND attempts < max_attempts
            ORDER BY created_at ASC
            LIMIT 5
        ";

        $result = $this->conexion->query($query);

        if (!$result) {
            throw new Exception('Error al obtener jobs: ' . $this->conexion->error);
        }

        $jobs = [];

        while ($row = $result->fetch_assoc()) {
            $data = json_decode($row['data'], true);

            if (!is_array($data)) {
                $this->handleJobFailure($row['id'], 'El campo data no contiene JSON válido.');
                continue;
            }

            $row['data'] = $data;

            /*
                La tabla jobs_queue NO tiene columnas separadas para:
                db_user, db_password, colaborador_data, usuario_data, notify_email.

                Todo viene dentro del JSON data, creado desde clientesControlador.php.
            */
            $row['db_user'] = isset($data['db_user']) ? trim((string)$data['db_user']) : '';
            $row['db_password'] = isset($data['db_password']) ? trim((string)$data['db_password']) : '';
            $row['notify_email'] = isset($data['notify_email']) ? trim((string)$data['notify_email']) : '';

            $row['colaborador_data'] = [];
            if (isset($data['colaborador_data']) && is_array($data['colaborador_data'])) {
                $row['colaborador_data'] = $data['colaborador_data'];
            }

            $row['usuario_data'] = [];
            if (isset($data['usuario_data']) && is_array($data['usuario_data'])) {
                $row['usuario_data'] = $data['usuario_data'];
            }

            if ($row['job_type'] === 'db_import') {
                if (
                    empty($row['data']['server_customers_id']) ||
                    empty($row['data']['db_name']) ||
                    empty($row['data']['sql_file']) ||
                    empty($row['db_user']) ||
                    empty($row['db_password'])
                ) {
                    $this->handleJobFailure($row['id'], 'Datos incompletos para job de importación.');
                    continue;
                }
            }

            $jobs[] = $row;
        }

        return $jobs;
    }

    private function processJob($job) {
        switch ($job['job_type']) {
            case 'db_import':
                return $this->processDbImportJob($job);
            case 'registration_email':
            case 'access_email':
                return $this->processEmailJob($job);

            default:
                throw new Exception('Tipo de job no soportado: ' . $job['job_type']);
        }
    }

    private function processDbImportJob($job) {
        if (empty($job['db_user']) || empty($job['db_password'])) {
            throw new Exception('Credenciales de base de datos incompletas.');
        }

        $dbName = trim($job['data']['db_name']);
        $sqlFile = trim($job['data']['sql_file']);

        if (!file_exists($sqlFile)) {
            throw new Exception('No existe el archivo SQL de plantilla: ' . $sqlFile);
        }

        $sourceStmt = $this->conexion->prepare('SELECT db,db_imported FROM server_customers WHERE server_customers_id=? LIMIT 1');
        $customerId = (int)$job['data']['server_customers_id'];
        $sourceStmt->bind_param('i', $customerId);
        $sourceStmt->execute();
        $source = $sourceStmt->get_result()->fetch_assoc();
        $sourceStmt->close();
        if (!$source || trim((string)$source['db']) !== $dbName) {
            throw new Exception('Nombre de base no coincide con server_customers.');
        }
        // db_main.server_customers es la referencia canónica de la cuenta.
        // Nunca aprovisionar si el usuario principal no apunta a esa cuenta.
        $this->verifyMainRegistration($job);
        if ((int)$source['db_imported'] === 1) {
            // El proceso principal ya está finalizado: recuperar notificación pendiente.
            $this->enqueueAccessEmails($job);
            $this->clearTemporaryPassword($job);
            return true;
        }

        // La plantilla se importa tal como existe actualmente.
        // Su actualización de esquema y catálogos se realizará por separado.
        // No bloquear esta etapa por columnas todavía pendientes de actualizar.
        $sqlSchema = file_get_contents($sqlFile);
        if ($sqlSchema === false || trim($sqlSchema) === '') {
            throw new Exception('El archivo SQL de plantilla está vacío o no se puede leer.');
        }
        preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $sqlSchema, $expectedMatches);
        $expectedTables = array_unique($expectedMatches[1]);
        if (count($expectedTables) < 10) throw new Exception('Plantilla SQL incompleta: faltan tablas del sistema.');
        $skipImport = false;
        $preflightConn = $this->dbSetup->connectToDatabase($dbName, $job['db_user'], $job['db_password']);
        if (!$preflightConn) throw new Exception('Base de datos todavía no disponible.');
        try {
            $tables=$preflightConn->query('SHOW TABLES');
            if (!$tables) throw new Exception('No se pudo consultar las tablas del cliente.');
            if ($tables->num_rows>0) {
                $available=[];
                while ($r=$tables->fetch_row()) $available[]=$r[0];
                $missing=array_diff($expectedTables,$available);
                if ($missing) throw new Exception('Importación incompleta: faltan '.count($missing).' tablas. No se borrará información.');
                // Reanudación idempotente tras importar la estructura pero fallar una inserción.
                $skipImport=true;
            }
        } finally { $preflightConn->close(); }

        $imported = $skipImport || $this->dbSetup->importDatabase(
            $dbName,
            $job['db_user'],
            $job['db_password'],
            $sqlFile
        );

        if (!$imported) {
            throw new Exception('Error al importar la base de datos.');
        }

        $newDbConn = $this->dbSetup->connectToDatabase(
            $dbName,
            $job['db_user'],
            $job['db_password']
        );

        if (!$newDbConn) {
            throw new Exception('Error al conectar a la nueva base de datos.');
        }

        try {
            // Los IDs de usuarios y colaboradores pertenecen a cada base por separado.
            $localColaboradorId = $this->syncTenantColaborador($newDbConn, $job);
            $this->syncTenantCompany($newDbConn, $job, $localColaboradorId);
            $this->syncTenantUser($newDbConn, $job, $localColaboradorId);
            $this->verifyTenantIdentity($newDbConn, $job, $localColaboradorId);
            // La cuenta principal puede haber cambiado mientras se importaba.
            $this->verifyMainRegistration($job);

            ProvisioningSync::sync($this->conexion, $newDbConn, (int)$job['data']['server_customers_id']);
            $this->markDatabaseAsImported($job['data']['server_customers_id']);

            $this->enqueueAccessEmails($job);
            $this->clearTemporaryPassword($job);

            return true;

        } finally {
            if ($newDbConn) {
                $newDbConn->close();
            }
        }
    }

    private function internalRecipients(): array {
        $out = [];
        $rs = $this->conexion->query('SELECT correo,nombre FROM notificaciones WHERE activo=1');
        if (!$rs) throw new Exception('No se pudieron consultar destinatarios internos: '.$this->conexion->error);
        while ($r=$rs->fetch_assoc()) {
            $email = trim((string)$r['correo']);
            if (filter_var($email,FILTER_VALIDATE_EMAIL)) $out[$email] = trim((string)$r['nombre']) ?: $email;
        }
        return $out;
    }

    private function enqueueAccessEmails(array $job): void {
        $id = (int)$job['data']['server_customers_id'];
        $data = $job['data'];
        $to = trim((string)($data['notify_email'] ?? $job['notify_email'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new Exception('Correo del cliente inválido.');
        $name = (string)($data['colaborador_data']['nombre'] ?? 'Cliente');
        $internal = $this->internalRecipients();
        $url = (string)($data['login_url'] ?? SERVERURL.'login/');
        $company = (string)($data['empresa_nombre'] ?? '');
        $code = $this->getCodigoCliente($id);
        $plain = (string)($data['password_temporal'] ?? '');
        $html = '<p>Tu acceso a IZZY está habilitado.</p><p>Plan asignado: <b>Gratis</b>.</p>'
          .'<p>Empresa: '.htmlspecialchars($company,ENT_QUOTES,'UTF-8').'</p>'
          .'<p>Usuario: '.htmlspecialchars((string)($data['usuario_data']['username'] ?? $to),ENT_QUOTES,'UTF-8').'</p>'
          .($plain !== '' ? '<p>Contraseña inicial: '.htmlspecialchars($plain,ENT_QUOTES,'UTF-8').'</p>' : '<p>Usa la contraseña elegida durante tu registro.</p>')
          .'<p>Código: '.htmlspecialchars($code,ENT_QUOTES,'UTF-8').'</p>'
          .'<p><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">Entrar a IZZY</a></p>';
        $this->enqueueEmail('access_email','access_user_'.$id,[$to=>$name], 'IZZY | Acceso habilitado', $html);
        if ($internal) {
            $internalHtml = '<p>Acceso habilitado: '.htmlspecialchars($company,ENT_QUOTES,'UTF-8').'</p><p>Cliente ID: '.$id.'</p><p>Plan Gratis (ID 6).</p>';
            $this->enqueueEmail('access_email','access_admin_'.$id,$internal,'IZZY | Nuevo acceso habilitado',$internalHtml);
        }
    }

    private function enqueueEmail(string $type,string $key,array $recipients,string $subject,string $html): void {
        $like = '%"notification_key":"'.$key.'"%';
        $stmt=$this->conexion->prepare('SELECT id FROM jobs_queue WHERE job_type=? AND data LIKE ? LIMIT 1');
        $stmt->bind_param('ss',$type,$like);$stmt->execute();$existing=$stmt->get_result()->num_rows;$stmt->close();
        if ($existing) return;
        $json=json_encode(['notification_key'=>$key,'recipients'=>$recipients,'subject'=>$subject,'html'=>$html],JSON_UNESCAPED_UNICODE);
        if ($json===false) throw new Exception('No se pudo preparar correo pendiente.');
        $stmt=$this->conexion->prepare("INSERT INTO jobs_queue(job_type,data,status,attempts,max_attempts) VALUES(?,?,'pending',0,10)");
        if (!$stmt) throw new Exception('Error guardando correo pendiente.');
        $stmt->bind_param('ss',$type,$json);
        if (!$stmt->execute()) throw new Exception('Error al guardar correo pendiente: '.$stmt->error);
        $stmt->close();
    }

    private function processEmailJob(array $job): bool {
        $data=$job['data'];
        $recipients=$data['recipients'] ?? [];
        if (!$recipients || !is_array($recipients)) throw new Exception('Correo sin destinatarios.');
        $mailer=new sendEmail();
        $result=$mailer->enviarCorreo($recipients,[],(string)($data['subject']??''),(string)($data['html']??''),1,1,[]);
        if ($result !== 1) throw new Exception('No fue posible enviar el correo; se reintentará.');
        // Borra la contraseña del contenido de correo después de enviarlo.
        $data['html']='';
        $json=json_encode($data,JSON_UNESCAPED_UNICODE);
        $stmt=$this->conexion->prepare('UPDATE jobs_queue SET data=? WHERE id=?');
        $id=(int)$job['id'];$stmt->bind_param('si',$json,$id);$stmt->execute();$stmt->close();
        return true;
    }

    /** IDs locales independientes; empresa pertenece únicamente a la base del cliente. */
    private function syncTenantCompany(mysqli $connection, array $job, int $localColaboradorId): void {
        $data = $job['data'];
        $nombre = trim((string)($data['empresa_nombre'] ?? ''));
        $correo = trim((string)($data['correo'] ?? ''));
        $telefono = trim((string)($data['telefono'] ?? ''));
        $rtn = trim((string)($data['rtn'] ?? ''));
        if ($nombre === '') throw new RuntimeException('Falta el nombre de empresa en el registro.');
        $stmt = $connection->prepare('SELECT empresa_id FROM empresa WHERE empresa_id = 1 LIMIT 1');
        if (!$stmt) throw new RuntimeException('No existe la estructura empresa en la plantilla.');
        $stmt->execute(); $exists = $stmt->get_result()->num_rows > 0; $stmt->close();
        if ($exists) return; // Nunca sobrescribir datos de empresa configurados por el cliente.
        $stmt = $connection->prepare("INSERT INTO empresa
            (empresa_id, razon_social, nombre, otra_informacion, eslogan, celular,
            telefono, correo, logotipo, rtn, ubicacion, facebook, sitioweb,
            horario, estado, colaboradores_id, fecha_registro, firma_documento, MostrarFirma)
            VALUES (1, ?, ?, '', '', ?, ?, ?, ?, '', '', '', '', '', 1, ?, NOW(), '', 0)");
        if (!$stmt) throw new RuntimeException('No se pudo preparar empresa del cliente: '.$connection->error);
        $stmt->bind_param('ssssssi', $nombre, $nombre, $telefono, $telefono, $correo, $rtn, $localColaboradorId);
        if (!$stmt->execute()) throw new RuntimeException('No se pudo guardar empresa del cliente: '.$stmt->error);
        $stmt->close();
    }

    private function getTenantUserByCustomer(mysqli $connection, int $customerId): ?array {
        $stmt = $connection->prepare('SELECT * FROM users WHERE server_customers_id=? LIMIT 2');
        if (!$stmt) throw new RuntimeException('No se puede consultar usuario local: '.$connection->error);
        $stmt->bind_param('i', $customerId);
        $stmt->execute(); $result = $stmt->get_result();
        if ($result->num_rows > 1) throw new RuntimeException('Existen usuarios duplicados para la cuenta local.');
        $row = $result->fetch_assoc() ?: null; $stmt->close();
        return $row;
    }

    private function syncTenantColaborador(mysqli $connection, array $job): int {
        $customerId = (int)$job['data']['server_customers_id'];
        $source = $job['colaborador_data'] ?? null;
        if (!is_array($source)) throw new RuntimeException('Faltan datos del colaborador en cola.');
        $localUser = $this->getTenantUserByCustomer($connection, $customerId);
        if ($localUser) return (int)$localUser['colaboradores_id'];

        // En un reintento después de insertar colaborador, localizarlo por identidad
        // solo si es inequívoco; nunca utilizar el colaborador de soporte ID 1.
        $identity = trim((string)($source['identidad'] ?? ''));
        if ($identity !== '') {
            $stmt = $connection->prepare('SELECT colaboradores_id FROM colaboradores WHERE identidad=? AND colaboradores_id<>1 LIMIT 2');
            $stmt->bind_param('s', $identity); $stmt->execute(); $rs=$stmt->get_result();
            if ($rs->num_rows > 1) throw new RuntimeException('Identidad de colaborador duplicada en base local.');
            $row=$rs->fetch_assoc(); $stmt->close();
            if ($row) return (int)$row['colaboradores_id'];
        }
        $stmt=$connection->prepare('INSERT INTO colaboradores
            (puestos_id,nombre,identidad,estado,telefono,empresa_id,fecha_registro,fecha_ingreso,fecha_egreso)
            VALUES (?,?,?,?,?,1,?,?,?)');
        if (!$stmt) throw new RuntimeException('No se pudo preparar colaborador: '.$connection->error);
        $puesto=(int)$source['puestos_id']; $nombre=(string)$source['nombre'];
        $estado=(int)$source['estado']; $tel=(string)$source['telefono'];
        $registro=(string)$source['fecha_registro']; $ingreso=(string)$source['fecha_ingreso'];
        $egreso=$source['fecha_egreso'];
        $stmt->bind_param('ississss',$puesto,$nombre,$identity,$estado,$tel,$registro,$ingreso,$egreso);
        if (!$stmt->execute()) throw new RuntimeException('No se pudo crear colaborador local: '.$stmt->error);
        $id=(int)$connection->insert_id; $stmt->close();
        return $id;
    }

    private function syncTenantUser(mysqli $connection, array $job, int $colaboradorId): void {
        $source = $job['usuario_data'] ?? null;
        if (!is_array($source)) throw new RuntimeException('Faltan datos del usuario en cola.');
        $customerId = (int)$job['data']['server_customers_id'];
        $existing = $this->getTenantUserByCustomer($connection, $customerId);
        $username = trim((string)($source['username'] ?? ''));
        if ($username === '') $username = $this->generarUsername($source['email'] ?? '', $customerId);
        $email=(string)$source['email']; $password=(string)$source['password'];
        $privilegio=(int)$source['privilegio_id']; $tipo=(int)$source['tipo_user_id'];
        $estado=(int)$source['estado']; $fecha=(string)$source['fecha_registro'];
        if ($existing) {
            if ((int)$existing['users_id'] === 1) throw new RuntimeException('No se debe modificar el usuario de soporte.');
            $stmt=$connection->prepare('UPDATE users SET colaboradores_id=?,privilegio_id=?,username=?,password=?,email=?,tipo_user_id=?,estado=?,fecha_registro=?,empresa_id=1 WHERE users_id=? AND server_customers_id=?');
            if (!$stmt) throw new RuntimeException('No se pudo preparar usuario local: '.$connection->error);
            $id=(int)$existing['users_id'];
            $stmt->bind_param('iisssiisii',$colaboradorId,$privilegio,$username,$password,$email,$tipo,$estado,$fecha,$id,$customerId);
        } else {
            // No introducir el ID de db_main; MySQL asigna el ID local disponible.
            $stmt=$connection->prepare('INSERT INTO users (colaboradores_id,privilegio_id,username,password,email,tipo_user_id,estado,fecha_registro,empresa_id,server_customers_id) VALUES (?,?,?,?,?,?,?, ?,1,?)');
            if (!$stmt) throw new RuntimeException('No se pudo preparar inserción de usuario local: '.$connection->error);
            $stmt->bind_param('iisssiisi',$colaboradorId,$privilegio,$username,$password,$email,$tipo,$estado,$fecha,$customerId);
        }
        if (!$stmt->execute()) throw new RuntimeException('No se pudo guardar usuario local: '.$stmt->error);
        $stmt->close();
    }

    /**
     * La referencia entre bases es el ID central de server_customers,
     * no los IDs locales de usuarios o colaboradores.
     */
    private function verifyMainRegistration(array $job): void {
        $customerId = (int)($job['data']['server_customers_id'] ?? 0);
        $expected = $job['usuario_data'] ?? [];
        if ($customerId <= 0 || !is_array($expected) ||
            trim((string)($expected['username'] ?? '')) === '' ||
            trim((string)($expected['email'] ?? '')) === '' ||
            (string)($expected['password'] ?? '') === '') {
            throw new RuntimeException('Identidad de registro central incompleta.');
        }
        $stmt = $this->conexion->prepare(
            'SELECT u.users_id,u.colaboradores_id,u.empresa_id,u.server_customers_id,u.username,u.email,u.password '
            . 'FROM users u INNER JOIN server_customers s '
            . 'ON s.server_customers_id=u.server_customers_id '
            . 'WHERE s.server_customers_id=? LIMIT 2'
        );
        if (!$stmt) throw new RuntimeException('No se pudo consultar la identidad central: '.$this->conexion->error);
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows !== 1) {
            $stmt->close();
            throw new RuntimeException('Debe existir exactamente un usuario principal asociado a server_customers.');
        }
        $row = $result->fetch_assoc();
        $stmt->close();
        if ((int)$row['server_customers_id'] !== $customerId ||
            (int)$row['empresa_id'] !== 1 ||
            (int)$row['colaboradores_id'] <= 0 ||
            (string)$row['username'] !== (string)$expected['username'] ||
            strcasecmp((string)$row['email'], (string)$expected['email']) !== 0 ||
            !hash_equals((string)$row['password'], (string)$expected['password'])) {
            throw new RuntimeException('El usuario central no coincide con la cuenta registrada; aprovisionamiento detenido.');
        }
        $stmt = $this->conexion->prepare('SELECT colaboradores_id FROM colaboradores WHERE colaboradores_id=? LIMIT 1');
        if (!$stmt) throw new RuntimeException('No se pudo consultar el colaborador central.');
        $collaboratorId = (int)$row['colaboradores_id'];
        $stmt->bind_param('i', $collaboratorId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows === 1;
        $stmt->close();
        if (!$exists) throw new RuntimeException('El colaborador central asociado no existe.');
    }

    private function verifyTenantIdentity(mysqli $connection, array $job, int $localColaboradorId): void {
        $source=$job['usuario_data'];
        $customerId=(int)$job['data']['server_customers_id'];
        $row=$this->getTenantUserByCustomer($connection,$customerId);
        if (!$row || (int)$row['users_id'] === 1 ||
            (int)$row['colaboradores_id'] !== $localColaboradorId ||
            (int)$row['empresa_id'] !== 1 ||
            (int)$row['server_customers_id'] !== $customerId ||
            (string)$row['username'] !== (string)$source['username'] ||
            strcasecmp((string)$row['email'],(string)$source['email']) !== 0 ||
            !hash_equals((string)$row['password'],(string)$source['password'])) {
            throw new RuntimeException('Datos del usuario local no coinciden con el registro principal.');
        }
        $stmt=$connection->prepare('SELECT colaboradores_id FROM colaboradores WHERE colaboradores_id=? AND empresa_id=1 LIMIT 1');
        $stmt->bind_param('i',$localColaboradorId);$stmt->execute();
        $valid=$stmt->get_result()->num_rows === 1;$stmt->close();
        if (!$valid) throw new RuntimeException('Falta colaborador local relacionado con usuario.');
    }

    private function recordExists($connection, $table, $field, $value) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);

        $stmt = $connection->prepare("SELECT {$field} FROM {$table} WHERE {$field} = ? LIMIT 1");

        if (!$stmt) {
            throw new Exception('Error al verificar existencia de registro: ' . $connection->error);
        }

        $value = (int)$value;
        $stmt->bind_param('i', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        $stmt->close();

        return $exists;
    }

    private function markDatabaseAsImported($server_customers_id) {
        $stmt = $this->conexion->prepare("
            UPDATE server_customers
            SET db_imported = 1,
                estado = 1
            WHERE server_customers_id = ?
        ");

        if (!$stmt) {
            throw new Exception('Error al preparar consulta para marcar DB: ' . $this->conexion->error);
        }

        $server_customers_id = (int)$server_customers_id;
        $stmt->bind_param('i', $server_customers_id);

        if (!$stmt->execute()) {
            throw new Exception('Error al marcar base de datos como importada: ' . $stmt->error);
        }

        $stmt->close();
    }

    private function markJobAsProcessing($jobId) {
        $stmt = $this->conexion->prepare("
            UPDATE jobs_queue
            SET status = 'processing',
                attempts = attempts + 1,
                processed_at = NOW(),
                error_message = NULL
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception('Error al preparar consulta para marcar job como procesando: ' . $this->conexion->error);
        }

        $jobId = (int)$jobId;
        $stmt->bind_param('i', $jobId);

        if (!$stmt->execute()) {
            throw new Exception('Error al marcar job como procesando: ' . $stmt->error);
        }

        $stmt->close();
    }

    private function markJobAsCompleted($jobId) {
        $stmt = $this->conexion->prepare("
            UPDATE jobs_queue
            SET status = 'completed',
                processed_at = NOW(),
                error_message = NULL
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception('Error al preparar consulta para marcar job como completado: ' . $this->conexion->error);
        }

        $jobId = (int)$jobId;
        $stmt->bind_param('i', $jobId);

        if (!$stmt->execute()) {
            throw new Exception('Error al marcar job como completado: ' . $stmt->error);
        }

        $stmt->close();
    }

    private function handleJobFailure($jobId, $error = null) {
        $statusStmt = $this->conexion->prepare("SELECT attempts, max_attempts FROM jobs_queue WHERE id = ? LIMIT 1");

        if (!$statusStmt) {
            throw new Exception('Error al preparar consulta para verificar intentos: ' . $this->conexion->error);
        }

        $jobId = (int)$jobId;
        $statusStmt->bind_param('i', $jobId);
        $statusStmt->execute();
        $result = $statusStmt->get_result();
        $row = $result->fetch_assoc();
        $statusStmt->close();

        $attempts = isset($row['attempts']) ? (int)$row['attempts'] : 0;
        $maxAttempts = isset($row['max_attempts']) ? (int)$row['max_attempts'] : 3;
        $status = ($attempts >= $maxAttempts) ? 'failed' : 'pending';

        $stmt = $this->conexion->prepare("
            UPDATE jobs_queue
            SET status = ?,
                processed_at = IF(? = 'failed', NOW(), NULL),
                error_message = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception('Error al preparar consulta para manejar fallo: ' . $this->conexion->error);
        }

        $stmt->bind_param('sssi', $status, $status, $error, $jobId);

        if (!$stmt->execute()) {
            throw new Exception('Error al manejar fallo del job: ' . $stmt->error);
        }

        $stmt->close();
    }

    private function sendAccessReadyEmail($job) {
        try {
            $to = trim((string)$job['notify_email']);

            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            $nombre = $job['colaborador_data']['nombre'] ?? 'Usuario';
            $empresa = $job['data']['empresa_nombre'] ?? 'Su empresa';
            $dbName = $job['data']['db_name'] ?? '';
            $loginUrl = $job['data']['login_url'] ?? (SERVERURL . 'login/');
            $passwordTemporal = $job['data']['password_temporal'] ?? '';
            $codigoCliente = '';

            if (!empty($job['data']['server_customers_id'])) {
                $codigoCliente = $this->getCodigoCliente((int)$job['data']['server_customers_id']);
            }

            $asunto = 'Su acceso a IZZY ya está listo';

            $passwordHtml = '';

            if ($passwordTemporal !== '') {
                $passwordHtml = '<li><strong>Contraseña:</strong> '.$passwordTemporal.'</li>';
            }

            $codigoHtml = '';

            if ($codigoCliente !== '') {
                $codigoHtml = '<li><strong>Código de cliente:</strong> '.$codigoCliente.'</li>';
            }

            $mensaje = '
                <div style="padding: 20px;">
                    <p style="margin-bottom: 10px;">¡Hola '.$nombre.'!</p>

                    <p>
                        Su cuenta ya fue configurada correctamente. La base de datos del sistema fue preparada y su acceso ya está disponible.
                    </p>

                    <p><strong>Detalles de acceso:</strong></p>

                    <ul>
                        <li><strong>Empresa:</strong> '.$empresa.'</li>
                        <li><strong>Correo de acceso:</strong> '.$to.'</li>
                        '.$passwordHtml.'
                        '.$codigoHtml.'
                        <li><strong>Base asignada:</strong> '.$dbName.'</li>
                    </ul>

                    <p style="text-align: center; margin: 25px 0;">
                        <a href="'.$loginUrl.'" target="_blank"
                            style="
                                display: inline-block;
                                background: #0d6efd;
                                color: #ffffff;
                                padding: 12px 22px;
                                border-radius: 7px;
                                text-decoration: none;
                                font-weight: bold;
                            ">
                            Acceder al Sistema
                        </a>
                    </p>

                    <p>
                        Por seguridad, recomendamos cambiar su contraseña después del primer acceso.
                    </p>

                    <p>
                        Atentamente,<br>
                        <strong>El equipo de ES MULTISERVICIOS</strong>
                    </p>
                </div>
            ';

            $sendEmail = new sendEmail();

            $sent = $sendEmail->enviarCorreo(
                [$to => $nombre],
                [],
                $asunto,
                $mensaje,
                1,
                1,
                []
            );

            return $sent === 1;

        } catch (Exception $e) {
            error_log('Error al enviar correo de acceso listo: ' . $e->getMessage());
            return false;
        }
    }

    private function getCodigoCliente($server_customers_id) {
        $stmt = $this->conexion->prepare("SELECT codigo_cliente FROM server_customers WHERE server_customers_id = ? LIMIT 1");

        if (!$stmt) {
            return '';
        }

        $server_customers_id = (int)$server_customers_id;
        $stmt->bind_param('i', $server_customers_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $codigo = '';

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $codigo = (string)$row['codigo_cliente'];
        }

        $stmt->close();
        return $codigo;
    }

    private function clearTemporaryPassword($job) {
        if (!isset($job['data']) || !is_array($job['data'])) {
            return false;
        }

        $data = $job['data'];
        $data['password_temporal'] = '';
        $data['db_password'] = '';

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

        $stmt = $this->conexion->prepare("UPDATE jobs_queue SET data = ? WHERE id = ?");

        if (!$stmt) {
            return false;
        }

        $jobId = (int)$job['id'];
        $stmt->bind_param('si', $jsonData, $jobId);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    private function generarUsername($correo, $fallback = '') {
        $correo = trim((string)$correo);

        if ($correo !== '' && strpos($correo, '@') !== false) {
            $username = substr($correo, 0, strpos($correo, '@'));
        } else {
            $username = (string)$fallback;
        }

        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username));

        if ($username === '') {
            $username = 'usuario';
        }

        return substr($username, 0, 20);
    }
}

if (php_sapi_name() === 'cli') {
    try {
        $processor = new JobProcessor();
        $result = $processor->processPendingJobs();

        if ($result['success']) {
            echo "Procesamiento completado. Jobs procesados: {$result['processed']}, fallidos: {$result['failed']} de {$result['total']}\n";
            exit(0);
        }

        echo "Error en el procesamiento: {$result['error']}\n";
        exit(1);

    } catch (Exception $e) {
        error_log('Error fatal en JobProcessor: ' . $e->getMessage());
        echo 'Error fatal: ' . $e->getMessage() . "\n";
        exit(1);
    }
}