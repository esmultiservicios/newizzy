<?php
// scripts/process_jobs.php

/*
    Procesa jobs pendientes creados desde el registro autónomo.
    Este script debe ejecutarse por CLI/manual o por Cron Job.
*/

if (php_sapi_name() === 'cli') {
    $appUrl = getenv('APP_URL');

    if (!$appUrl) {
        $appUrl = 'https://newizzy.test/';
    }

    $partesUrl = parse_url($appUrl);

    $_SERVER['HTTPS'] = 'on';
    $_SERVER['HTTP_HOST'] = $partesUrl['host'] ?? 'newizzy.test';
    $_SERVER['SERVER_NAME'] = $partesUrl['host'] ?? 'newizzy.test';
    $_SERVER['SERVER_PORT'] = isset($partesUrl['port']) ? (string)$partesUrl['port'] : '443';
    $_SERVER['REQUEST_URI'] = $partesUrl['path'] ?? '/';
}

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/core/configGenerales.php';
require_once APP_ROOT . '/core/mainModel.php';
require_once APP_ROOT . '/core/DatabaseSetup.php';
require_once APP_ROOT . '/core/correo/sendEmail.php';

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

    private function getPendingJobs() {
        $query = "
            SELECT *
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
            $row['data'] = json_decode($row['data'], true);
            $row['colaborador_data'] = json_decode($row['colaborador_data'], true);
            $row['usuario_data'] = json_decode($row['usuario_data'], true);

            if (!is_array($row['data'])) {
                $this->handleJobFailure($row['id'], 'El campo data no contiene JSON válido.');
                continue;
            }

            if (!is_array($row['colaborador_data'])) {
                $row['colaborador_data'] = [];
            }

            if (!is_array($row['usuario_data'])) {
                $row['usuario_data'] = [];
            }

            if ($row['job_type'] === 'db_import') {
                if (
                    empty($row['data']['server_customers_id']) ||
                    empty($row['data']['db_name']) ||
                    empty($row['data']['sql_file'])
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

        $imported = $this->dbSetup->importDatabase(
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
            if (!empty($job['colaborador_data'])) {
                $this->createOrUpdateColaboradorInNewDb($newDbConn, $job['colaborador_data']);
            }

            if (!empty($job['usuario_data'])) {
                $this->createOrUpdateUsuarioInNewDb($newDbConn, $job['usuario_data']);
            }

            $this->markDatabaseAsImported($job['data']['server_customers_id']);

            if (!empty($job['notify_email'])) {
                $this->sendAccessReadyEmail($job);
            }

            $this->clearTemporaryPassword($job);

            return true;

        } finally {
            if ($newDbConn) {
                $newDbConn->close();
            }
        }
    }

    private function createOrUpdateColaboradorInNewDb($connection, $colaboradorData) {
        $colaboradores_id = (int)$colaboradorData['colaboradores_id'];

        $existe = $this->recordExists($connection, 'colaboradores', 'colaboradores_id', $colaboradores_id);

        if ($existe) {
            $stmt = $connection->prepare("
                UPDATE colaboradores SET
                    puestos_id = ?,
                    nombre = ?,
                    identidad = ?,
                    estado = ?,
                    telefono = ?,
                    empresa_id = ?,
                    fecha_registro = ?,
                    fecha_ingreso = ?,
                    fecha_egreso = ?
                WHERE colaboradores_id = ?
            ");

            if (!$stmt) {
                throw new Exception('Error al preparar actualización de colaborador: ' . $connection->error);
            }

            $stmt->bind_param(
                'issisisssi',
                $colaboradorData['puestos_id'],
                $colaboradorData['nombre'],
                $colaboradorData['identidad'],
                $colaboradorData['estado'],
                $colaboradorData['telefono'],
                $colaboradorData['empresa_id'],
                $colaboradorData['fecha_registro'],
                $colaboradorData['fecha_ingreso'],
                $colaboradorData['fecha_egreso'],
                $colaboradores_id
            );
        } else {
            $stmt = $connection->prepare("
                INSERT INTO colaboradores
                (
                    colaboradores_id,
                    puestos_id,
                    nombre,
                    identidad,
                    estado,
                    telefono,
                    empresa_id,
                    fecha_registro,
                    fecha_ingreso,
                    fecha_egreso
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception('Error al preparar inserción de colaborador: ' . $connection->error);
            }

            $stmt->bind_param(
                'iissiissss',
                $colaboradorData['colaboradores_id'],
                $colaboradorData['puestos_id'],
                $colaboradorData['nombre'],
                $colaboradorData['identidad'],
                $colaboradorData['estado'],
                $colaboradorData['telefono'],
                $colaboradorData['empresa_id'],
                $colaboradorData['fecha_registro'],
                $colaboradorData['fecha_ingreso'],
                $colaboradorData['fecha_egreso']
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Error al guardar colaborador en nueva DB: ' . $stmt->error);
        }

        $stmt->close();
    }

    private function createOrUpdateUsuarioInNewDb($connection, $usuarioData) {
        $users_id = (int)$usuarioData['users_id'];

        if (!isset($usuarioData['username']) || trim($usuarioData['username']) === '') {
            $usuarioData['username'] = $this->generarUsername($usuarioData['email'] ?? '', $usuarioData['users_id'] ?? '');
        }

        $existe = $this->recordExists($connection, 'users', 'users_id', $users_id);

        if ($existe) {
            $stmt = $connection->prepare("
                UPDATE users SET
                    colaboradores_id = ?,
                    privilegio_id = ?,
                    username = ?,
                    password = ?,
                    email = ?,
                    tipo_user_id = ?,
                    estado = ?,
                    fecha_registro = ?,
                    empresa_id = ?,
                    server_customers_id = ?
                WHERE users_id = ?
            ");

            if (!$stmt) {
                throw new Exception('Error al preparar actualización de usuario: ' . $connection->error);
            }

            $stmt->bind_param(
                'iisssiisiii',
                $usuarioData['colaboradores_id'],
                $usuarioData['privilegio_id'],
                $usuarioData['username'],
                $usuarioData['password'],
                $usuarioData['email'],
                $usuarioData['tipo_user_id'],
                $usuarioData['estado'],
                $usuarioData['fecha_registro'],
                $usuarioData['empresa_id'],
                $usuarioData['server_customers_id'],
                $users_id
            );
        } else {
            $stmt = $connection->prepare("
                INSERT INTO users
                (
                    users_id,
                    colaboradores_id,
                    privilegio_id,
                    username,
                    password,
                    email,
                    tipo_user_id,
                    estado,
                    fecha_registro,
                    empresa_id,
                    server_customers_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception('Error al preparar inserción de usuario: ' . $connection->error);
            }

            $stmt->bind_param(
                'iiisssiiiii',
                $usuarioData['users_id'],
                $usuarioData['colaboradores_id'],
                $usuarioData['privilegio_id'],
                $usuarioData['username'],
                $usuarioData['password'],
                $usuarioData['email'],
                $usuarioData['tipo_user_id'],
                $usuarioData['estado'],
                $usuarioData['fecha_registro'],
                $usuarioData['empresa_id'],
                $usuarioData['server_customers_id']
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Error al guardar usuario en nueva DB: ' . $stmt->error);
        }

        $stmt->close();
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
                processed_at = NULL,
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

            $sendEmail->enviarCorreo(
                [$to => $nombre],
                [],
                $asunto,
                $mensaje,
                1,
                1,
                []
            );

            return true;

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