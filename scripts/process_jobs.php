<?php
//process_jobs.php

// Configuración CLI - usar rutas absolutas
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/core/configGenerales.php';
require_once APP_ROOT . '/core/mainModel.php';
require_once APP_ROOT . '/core/DatabaseSetup.php';

class JobProcessor {
    private $conexion;
    private $dbSetup;
    private $mainModel;
    
    public function __construct() {
        $this->mainModel = new mainModel();
        $this->conexion = $this->mainModel->connection();
        $this->dbSetup = new DatabaseSetup();
        
        // Verificar conexión
        if ($this->conexion->connect_error) {
            throw new Exception("Error de conexión: " . $this->conexion->connect_error);
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
                        $this->handleJobFailure($job['id'], "Error desconocido al procesar el job");
                        $failed++;
                    }
                } catch (Exception $e) {
                    $this->handleJobFailure($job['id'], $e->getMessage());
                    $failed++;
                    error_log("Error procesando job ID {$job['id']}: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($jobs)
            ];
        } catch (Exception $e) {
            error_log("Error en processPendingJobs: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed' => 0,
                'failed' => 0
            ];
        } finally {
            if ($this->conexion) {
                $this->conexion->close();
            }
        }
    }
    
    private function getPendingJobs() {
        $query = "SELECT * FROM jobs_queue 
                 WHERE status = 'pending' AND attempts < max_attempts 
                 ORDER BY created_at ASC 
                 LIMIT 5";
        
        $result = $this->conexion->query($query);
        
        if (!$result) {
            throw new Exception("Error al obtener jobs: " . $this->conexion->error);
        }
        
        $jobs = [];
        while ($row = $result->fetch_assoc()) {
            // Decodificar datos JSON
            $row['data'] = json_decode($row['data'], true);
            $row['colaborador_data'] = json_decode($row['colaborador_data'], true);
            $row['usuario_data'] = json_decode($row['usuario_data'], true);
            
            // Validar datos mínimos requeridos para db_import
            if ($row['job_type'] == 'db_import') {
                if (empty($row['data']['server_customers_id']) || 
                    empty($row['data']['db_name']) || 
                    empty($row['data']['sql_file'])) {
                    $this->handleJobFailure($row['id'], "Datos incompletos para job de importación");
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
            // Puedes agregar más tipos de jobs aquí
            default:
                throw new Exception("Tipo de job no soportado: {$job['job_type']}");
        }
    }
    
    private function processDbImportJob($job) {
        // Validar datos requeridos
        if (empty($job['db_name']) || empty($job['db_user']) || empty($job['db_password'])) {
            throw new Exception("Credenciales de base de datos incompletas");
        }
        
        // 1. Importar la base de datos
        $imported = $this->dbSetup->importDatabase(
            $job['data']['db_name'],
            $job['db_user'],
            $job['db_password'],
            $job['data']['sql_file']
        );
        
        if (!$imported) {
            throw new Exception("Error al importar la base de datos");
        }
        
        // 2. Conectar a la nueva base de datos
        $newDbConn = $this->dbSetup->connectToDatabase(
            $job['data']['db_name'],
            $job['db_user'],
            $job['db_password']
        );
        
        if (!$newDbConn) {
            throw new Exception("Error al conectar a la nueva base de datos");
        }
        
        try {
            // 3. Crear colaborador en la nueva base de datos
            if (!empty($job['colaborador_data'])) {
                $this->createColaboradorInNewDb($newDbConn, $job['colaborador_data']);
            }
            
            // 4. Crear usuario en la nueva base de datos
            if (!empty($job['usuario_data'])) {
                $this->createUsuarioInNewDb($newDbConn, $job['usuario_data']);
            }
            
            // 5. Actualizar server_customers para marcar como importado
            $this->markDatabaseAsImported($job['data']['server_customers_id']);
            
            // 6. Notificar por email si está configurado
            if (!empty($job['notify_email'])) {
                $this->sendNotificationEmail($job);
            }
            
            return true;
        } finally {
            if ($newDbConn) {
                $newDbConn->close();
            }
        }
    }
    
    private function createColaboradorInNewDb($connection, $colaboradorData) {
        $stmt = $connection->prepare(
            "INSERT INTO colaboradores 
            (colaboradores_id, puestos_id, nombre, identidad, estado, telefono, empresa_id, fecha_registro, fecha_ingreso, fecha_egreso) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta para colaborador: " . $connection->error);
        }
        
        $stmt->bind_param("iissiissss", 
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
        
        if (!$stmt->execute()) {
            throw new Exception("Error al crear colaborador: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    private function createUsuarioInNewDb($connection, $usuarioData) {
        $stmt = $connection->prepare(
            "INSERT INTO users 
            (users_id, colaboradores_id, privilegio_id, password, email, tipo_user_id, estado, fecha_registro, empresa_id, server_customers_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta para usuario: " . $connection->error);
        }
        
        $stmt->bind_param("iiissiisii", 
            $usuarioData['users_id'],
            $usuarioData['colaboradores_id'],
            $usuarioData['privilegio_id'],
            $usuarioData['password'],
            $usuarioData['email'],
            $usuarioData['tipo_user_id'],
            $usuarioData['estado'],
            $usuarioData['fecha_registro'],
            $usuarioData['empresa_id'],
            $usuarioData['server_customers_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al crear usuario: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    private function markDatabaseAsImported($server_customers_id) {
        $stmt = $this->conexion->prepare(
            "UPDATE server_customers SET db_imported = 1 WHERE server_customers_id = ?"
        );
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta para marcar DB: " . $this->conexion->error);
        }
        
        $stmt->bind_param("i", $server_customers_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al marcar base de datos como importada: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    private function markJobAsProcessing($jobId) {
        $stmt = $this->conexion->prepare(
            "UPDATE jobs_queue 
             SET status = 'processing', 
                 attempts = attempts + 1,
                 processed_at = NULL,
                 error_message = NULL
             WHERE id = ?"
        );
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta para marcar job como procesando: " . $this->conexion->error);
        }
        
        $stmt->bind_param("i", $jobId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al marcar job como procesando: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    private function markJobAsCompleted($jobId) {
        $stmt = $this->conexion->prepare(
            "UPDATE jobs_queue 
             SET status = 'completed', 
                 processed_at = NOW(),
                 error_message = NULL
             WHERE id = ?"
        );
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta para marcar job como completado: " . $this->conexion->error);
        }
        
        $stmt->bind_param("i", $jobId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al marcar job como completado: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    private function handleJobFailure($jobId, $error = null) {
        $statusQuery = "SELECT attempts, max_attempts FROM jobs_queue WHERE id = ?";
        $statusStmt = $this->conexion->prepare($statusQuery);
        
        if (!$statusStmt) {
            throw new Exception("Error al preparar consulta para verificar intentos: " . $this->conexion->error);
        }
        
        $statusStmt->bind_param("i", $jobId);
        $statusStmt->execute();
        $result = $statusStmt->get_result();
        $row = $result->fetch_assoc();
        $statusStmt->close();
        
        $status = ($row['attempts'] + 1 >= $row['max_attempts']) ? 'failed' : 'pending';
        
        $stmt = $this->conexion->prepare(
            "UPDATE jobs_queue 
             SET status = ?, 
                 attempts = attempts + 1,
                 processed_at = IF(? = 'failed', NOW(), NULL),
                 error_message = ?
             WHERE id = ?"
        );
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta para manejar fallo: " . $this->conexion->error);
        }
        
        $stmt->bind_param("sssi", $status, $status, $error, $jobId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al manejar fallo del job: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    private function sendNotificationEmail($job) {
        // Implementar lógica de envío de email aquí
        // Puedes usar PHPMailer o cualquier otro método
        // Este es solo un esqueleto
        try {
            $to = $job['notify_email'];
            $subject = "Job completado: {$job['job_type']}";
            $message = "El job ID {$job['id']} ha sido completado exitosamente.";
            
            // mail($to, $subject, $message);
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar notificación por email: " . $e->getMessage());
            return false;
        }
    }
}

// Ejecutar el procesador como script independiente
if (php_sapi_name() === 'cli') {
    try {
        $processor = new JobProcessor();
        $result = $processor->processPendingJobs();

        if ($result['success']) {
            echo "Procesamiento completado. Jobs procesados: {$result['processed']}, fallidos: {$result['failed']} de {$result['total']}\n";
            exit(0);
        } else {
            echo "Error en el procesamiento: {$result['error']}\n";
            exit(1);
        }
    } catch (Exception $e) {
        error_log("Error fatal en JobProcessor: " . $e->getMessage());
        echo "Error fatal: " . $e->getMessage() . "\n";
        exit(1);
    }
}