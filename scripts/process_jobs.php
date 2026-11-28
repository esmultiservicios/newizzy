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
require_once APP_ROOT . '/core/registro/cPanelAPI.php';
require_once APP_ROOT . '/core/correo/sendEmail.php';
require_once APP_ROOT . '/core/registro/ProvisioningSync.php';

class JobProcessor {
    private $conexion;
    private $dbSetup;
    private $mainModel;

    public function __construct() {
        $this->mainModel = new mainModel();
        $this->conexion = $this->mainModel->connectionDBLocal(DB_MAIN);
        $this->dbSetup = new DatabaseSetup();

        if ($this->conexion->connect_error) {
            throw new Exception('Error de conexión: ' . $this->conexion->connect_error);
        }
    }

    public function checkRequirements(): void {
        if (!is_readable(APP_ROOT . '/plantilla/plantilla_izzy.sql') || filesize(APP_ROOT . '/plantilla/plantilla_izzy.sql') < 1) {
            throw new RuntimeException('No se puede leer plantilla/plantilla_izzy.sql.');
        }
        $this->conexion->query('SELECT id,job_type,data,status,attempts,max_attempts,created_at,processed_at,error_message FROM jobs_queue LIMIT 0')->free();
        $this->conexion->query('SELECT server_customers_id,db,db_imported FROM server_customers LIMIT 0')->free();
        if (!function_exists('exec')) throw new RuntimeException('PHP CLI tiene exec deshabilitado.');
        exec('mysql --version 2>&1', $output, $status);
        if ($status !== 0) throw new RuntimeException('El cron no encuentra mysql en su PATH.');
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
            $this->retryInterruptedImports();
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
                } catch (Throwable $e) {
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

        } catch (Throwable $e) {
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

    private function retryInterruptedImports(): void {
        $this->conexion->query("UPDATE jobs_queue
            SET data=JSON_SET(data,'$.resume_schema_v1',1), status='pending', attempts=0, processed_at=NULL
            WHERE job_type='db_import' AND status='failed' AND JSON_VALID(data)
              AND JSON_EXTRACT(data,'$.provision_database')=true
              AND JSON_EXTRACT(data,'$.resume_schema_v1') IS NULL
              AND error_message LIKE 'Importación incompleta: faltan %'");
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
            ORDER BY created_at ASC, id ASC
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
            $row['db_user'] = trim((string)($data['db_user'] ?? CPANEL_DB_USERNAME));
            $row['db_password'] = (string)(CPANEL_DB_PASSWORD ?: ($data['db_password'] ?? ''));
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

    private function resumeInitialSchema(mysqli $db, string $template, array $expected, array $available): ?string {
        $prefix = array_slice($expected, 0, count($available));
        if (array_diff($prefix, $available) || array_diff($available, $prefix)) return null;

        // Reanudar únicamente en la primera fase de CREATE TABLE, antes de cargar datos e índices.
        $boundary = strpos($template, 'CREATE TABLE `'.$expected[count($prefix)].'`');
        $firstTable = strpos($template, 'CREATE TABLE');
        if ($boundary === false || $firstTable === false
            || preg_match('/^\h*(ALTER|INSERT|CREATE\s+VIEW|DROP)\b/im', substr($template, $firstTable, $boundary - $firstTable))) {
            return null;
        }
        foreach ($prefix as $table) {
            if ($db->query('SELECT 1 FROM `'.$table.'` LIMIT 1')->num_rows > 0) return null;
            $indexes = $db->query('SHOW INDEX FROM `'.$table.'`');
            if ($indexes->num_rows > 0) return null;
        }
        return substr($template, 0, $firstTable).substr($template, $boundary);
    }

    private function processDbImportJob($job) {
        if (empty($job['db_user']) || empty($job['db_password'])) {
            throw new Exception('Credenciales de base de datos incompletas.');
        }

        $dbName = trim($job['data']['db_name']);
        $sqlFile = APP_ROOT . '/plantilla/plantilla_izzy.sql';

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
        $job = $this->verifyMainRegistration($job);
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
        if (!empty($job['data']['provision_database'])) {
            $cpanel = new cPanelAPI();
            $resume = !empty($job['data']['provision_started']);
            if (!$resume) {
                if ($cpanel->databaseListed($dbName)) {
                    throw new RuntimeException('Ya existe una base con el nombre reservado. Se requiere revisión del alta.');
                }
                $job['data']['provision_started'] = true;
                $data = json_encode($job['data'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $jobId = (int)$job['id'];
                $stmt = $this->conexion->prepare('UPDATE jobs_queue SET data=? WHERE id=?');
                $stmt->bind_param('si', $data, $jobId);
                $stmt->execute();
                $stmt->close();
            }
            $cpanel->setupCompleteDatabase([
                'db_name' => $dbName,
                'db_user' => $job['db_user'],
                'db_password' => $job['db_password'],
            ], $resume);
        }

        $skipImport = false;
        $resumeSql = null;
        $preflightConn = $this->dbSetup->connectToDatabase($dbName, $job['db_user'], $job['db_password']);
        if (!$preflightConn) throw new Exception('Base de datos todavía no disponible.');
        try {
            $tables=$preflightConn->query('SHOW TABLES');
            if (!$tables) throw new Exception('No se pudo consultar las tablas del cliente.');
            if ($tables->num_rows>0) {
                $available=[];
                while ($r=$tables->fetch_row()) $available[]=$r[0];
                $missing=array_diff($expectedTables,$available);
                if ($missing) {
                    $resumeSql = $this->resumeInitialSchema($preflightConn, $sqlSchema, array_values($expectedTables), $available);
                    if ($resumeSql === null) {
                        throw new RuntimeException('Importación incompleta: faltan '.count($missing).' tablas. La base requiere revisión.');
                    }
                } else {
                    if (!in_array('v_promos_vigentes_producto', $available, true)) {
                        throw new RuntimeException('La plantilla no completó la importación.');
                    }
                    $skipImport = true;
                }
            }
        } finally { $preflightConn->close(); }

        $temporarySql = null;
        try {
            if ($resumeSql !== null) {
                $temporarySql = tempnam(sys_get_temp_dir(), 'izzy_schema_');
                if ($temporarySql === false || file_put_contents($temporarySql, $resumeSql) !== strlen($resumeSql)) {
                    throw new RuntimeException('No se pudo preparar la reanudación de la plantilla.');
                }
            }
            $imported = $skipImport || $this->dbSetup->importDatabase(
                $dbName,
                $job['db_user'],
                $job['db_password'],
                $temporarySql ?: $sqlFile
            );
        } finally {
            if ($temporarySql) @unlink($temporarySql);
        }

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
            $newDbConn->begin_transaction();
            // Los IDs de usuarios y colaboradores pertenecen a cada base por separado.
            $localColaboradorId = $this->syncTenantColaborador($newDbConn, $job);
            $this->syncTenantCompany($newDbConn, $job, $localColaboradorId);
            $this->syncTenantUser($newDbConn, $job, $localColaboradorId);
            // La cuenta principal puede haber cambiado mientras se importaba.
            $job = $this->verifyMainRegistration($job);
            $this->verifyTenantIdentity($newDbConn, $job, $localColaboradorId);

            ProvisioningSync::sync($this->conexion, $newDbConn, (int)$job['data']['server_customers_id']);
            $newDbConn->commit();

            $this->conexion->begin_transaction();
            try {
                $this->markDatabaseAsImported($job['data']['server_customers_id']);
                $this->enqueueAccessEmails($job);
                $this->clearTemporaryPassword($job);
                $this->conexion->commit();
            } catch (Throwable $e) {
                $this->conexion->rollback();
                throw $e;
            }

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
        $stmt = $this->conexion->prepare('SELECT p.nombre FROM planes p JOIN server_customers sc USING (planes_id) WHERE sc.server_customers_id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $plan = (string)($stmt->get_result()->fetch_row()[0] ?? 'Gratis');
        $stmt->close();
        $code = $this->getCodigoCliente($id);
        $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $html = '<p>Hola, <strong>'.$escape($name).'</strong>.</p>'
          .'<p>Su espacio de trabajo <strong>'.$escape($company).'</strong> ya está listo en el <strong>plan '.$escape($plan).' de IZZY</strong>.</p>'
          .'<p>Antes: <span style="display:inline-block;border-radius:20px;background:#e6f3fb;color:#13466b;padding:5px 12px;font-weight:bold">En preparación</span>'
          .' &nbsp; Ahora: <span style="display:inline-block;border-radius:20px;background:#e6f6ed;color:#146443;padding:5px 12px;font-weight:bold">Acceso habilitado</span></p>'
          .'<p><strong>Correo de acceso:</strong> '.$escape($to).'</p><p><strong>Código de cliente '.$escape($code).'</strong>. Guárdelo como referencia para soporte.</p>'
          .(!empty($data['managed_registration'])
            ? '<p>Use «Recuperar contraseña» en la pantalla de acceso para establecer su contraseña con el correo indicado.</p>'
            : '<p>Inicie sesión con el correo y la contraseña que eligió en el registro. Si necesita cambiarla, utilice «Recuperar contraseña».</p>')
          .'<p><a href="'.$escape($url).'">Iniciar sesión en IZZY</a></p>'
          .'<p>Bienvenido a IZZY.<br><strong>Equipo de IZZY · ES MULTISERVICIOS</strong><br><a href="https://izzycloud.app">Visita nuestra web de IZZY</a></p>';
        $this->enqueueEmail('access_email','access_user_'.$id,[$to=>$name], 'IZZY | Acceso habilitado', $html);
        if ($internal) {
            $internalHtml = '<p><strong>Acceso habilitado</strong>. Se importó la base y se activó la cuenta.</p>'
                .'<p>Empresa: <strong>'.$escape($company).'</strong><br>Titular: '.$escape($name)
                .'<br>Correo: '.$escape($to).'<br>Plan: '.$escape($plan).'<br>Código: '.$escape($code)
                .'<br>Cuenta: '.$id.'</p>';
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
        $mailer = new sendEmail(DB_MAIN);
        $failureCode = null;
        ob_start();
        try {
            $result = $mailer->enviarCorreo($recipients, [], (string)($data['subject'] ?? ''),
                (string)($data['html'] ?? ''), 1, 1, [], 'info', $failureCode);
        } finally {
            ob_end_clean();
        }
        if ($result !== 1) {
            throw new RuntimeException('No fue posible enviar el correo. Diagnóstico: '.($failureCode ?? 'error_de_envio').'.');
        }
        // Conserva los metadatos del trabajo y descarta el cuerpo tras el envío.
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
        $eslogan = (string)($data['eslogan'] ?? '');
        $informacion = (string)($data['otra_informacion'] ?? '');
        $celular = trim((string)($data['celular'] ?? '')) ?: $telefono;
        $ubicacion = (string)($data['ubicacion'] ?? '');
        if ($nombre === '') throw new RuntimeException('Falta el nombre de empresa en el registro.');
        $stmt = $connection->prepare('SELECT empresa_id FROM empresa WHERE empresa_id = 1 LIMIT 1');
        if (!$stmt) throw new RuntimeException('No existe la estructura empresa en la plantilla.');
        $stmt->execute(); $exists = $stmt->get_result()->num_rows > 0; $stmt->close();
        if ($exists) return; // Nunca sobrescribir datos de empresa configurados por el cliente.
        $stmt = $connection->prepare("INSERT INTO empresa
            (empresa_id, razon_social, nombre, otra_informacion, eslogan, celular,
            telefono, correo, logotipo, rtn, ubicacion, facebook, sitioweb,
            horario, estado, colaboradores_id, fecha_registro, firma_documento, MostrarFirma)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, '', '', '', 1, ?, NOW(), '', 0)");
        if (!$stmt) throw new RuntimeException('No se pudo preparar empresa del cliente: '.$connection->error);
        $stmt->bind_param('sssssssssi', $nombre, $nombre, $informacion, $eslogan, $celular, $telefono, $correo, $rtn, $ubicacion, $localColaboradorId);
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
            (colaboradores_id,puestos_id,nombre,identidad,estado,telefono,empresa_id,fecha_registro,fecha_ingreso,fecha_egreso)
            VALUES (?,?,?,?,?,?,1,?,?,?)');
        if (!$stmt) throw new RuntimeException('No se pudo preparar colaborador: '.$connection->error);
        $puesto=(int)$source['puestos_id']; $nombre=(string)$source['nombre'];
        $estado=(int)$source['estado']; $tel=(string)$source['telefono'];
        $registro=(string)$source['fecha_registro']; $ingreso=(string)$source['fecha_ingreso'];
        $egreso=$source['fecha_egreso'];
        $id = (int)$connection->query('SELECT GREATEST(1,COALESCE(MAX(colaboradores_id),0))+1 FROM colaboradores')->fetch_row()[0];
        $stmt->bind_param('iississss',$id,$puesto,$nombre,$identity,$estado,$tel,$registro,$ingreso,$egreso);
        if (!$stmt->execute()) throw new RuntimeException('No se pudo crear colaborador local: '.$stmt->error);
        $stmt->close();
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
        $privilegio = 2; $tipo = 2;
        $estado=(int)$source['estado']; $fecha=(string)$source['fecha_registro'];
        if ($existing) {
            if ((int)$existing['users_id'] === 1) throw new RuntimeException('No se debe modificar el usuario de soporte.');
            $stmt=$connection->prepare('UPDATE users SET colaboradores_id=?,privilegio_id=?,username=?,password=?,email=?,tipo_user_id=?,estado=?,fecha_registro=?,empresa_id=1 WHERE users_id=? AND server_customers_id=?');
            if (!$stmt) throw new RuntimeException('No se pudo preparar usuario local: '.$connection->error);
            $id=(int)$existing['users_id'];
            $stmt->bind_param('iisssiisii',$colaboradorId,$privilegio,$username,$password,$email,$tipo,$estado,$fecha,$id,$customerId);
        } else {
            // No introducir el ID de db_main; MySQL asigna el ID local disponible.
            $stmt=$connection->prepare('INSERT INTO users (users_id,colaboradores_id,privilegio_id,username,password,email,tipo_user_id,estado,fecha_registro,empresa_id,server_customers_id) VALUES (?,?,?,?,?,?,?,?,?,1,?)');
            if (!$stmt) throw new RuntimeException('No se pudo preparar inserción de usuario local: '.$connection->error);
            $localId = (int)$connection->query('SELECT GREATEST(1,COALESCE(MAX(users_id),0))+1 FROM users')->fetch_row()[0];
            $stmt->bind_param('iiisssiisi',$localId,$colaboradorId,$privilegio,$username,$password,$email,$tipo,$estado,$fecha,$customerId);
        }
        if (!$stmt->execute()) throw new RuntimeException('No se pudo guardar usuario local: '.$stmt->error);
        $stmt->close();
    }

    /**
     * La referencia entre bases es el ID central de server_customers,
     * no los IDs locales de usuarios o colaboradores.
     */
    private function verifyMainRegistration(array $job): array {
        $customerId = (int)($job['data']['server_customers_id'] ?? 0);
        $expected = $job['usuario_data'] ?? [];
        if ($customerId <= 0 || !is_array($expected) ||
            trim((string)($expected['username'] ?? '')) === '' ||
            trim((string)($expected['email'] ?? '')) === '') {
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
            (string)$row['password'] === '' ||
            (int)$row['users_id'] !== (int)($expected['users_id'] ?? 0) ||
            (int)$row['colaboradores_id'] !== (int)($job['colaborador_data']['colaboradores_id'] ?? 0) ||
            (isset($expected['password']) && !hash_equals((string)$row['password'], (string)$expected['password']))) {
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
        $job['usuario_data']['password'] = $row['password'];
        return $job;
    }

    private function verifyTenantIdentity(mysqli $connection, array $job, int $localColaboradorId): void {
        $source=$job['usuario_data'];
        $customerId=(int)$job['data']['server_customers_id'];
        $row=$this->getTenantUserByCustomer($connection,$customerId);
        if (!$row || (int)$row['users_id'] === 1 ||
            (int)$row['colaboradores_id'] !== $localColaboradorId ||
            (int)$row['privilegio_id'] !== 2 || (int)$row['tipo_user_id'] !== 2 ||
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
        unset($data['password_temporal'], $data['db_password'], $data['usuario_data']['password']);

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
        if (in_array('--check', $argv, true)) {
            $processor->checkRequirements();
            echo "DB_MAIN, cola, plantilla y cliente MySQL disponibles. No se procesaron registros.\n";
            exit(0);
        }
        $result = $processor->processPendingJobs();

        if ($result['success']) {
            echo "Procesamiento completado. Jobs procesados: {$result['processed']}, fallidos: {$result['failed']} de {$result['total']}\n";
            exit(0);
        }

        echo "Error en el procesamiento: {$result['error']}\n";
        exit(1);

    } catch (Throwable $e) {
        error_log('Error fatal en JobProcessor: ' . $e->getMessage());
        echo 'Error fatal: ' . $e->getMessage() . "\n";
        exit(1);
    }
}