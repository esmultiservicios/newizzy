<?php
// Determinar rutas base
$peticionAjax = false; // Siempre false para worker CLI
$rutaBase = $peticionAjax ? '../' : './';

require_once $rutaBase.'core/mainModel.php';
require_once $rutaBase.'core/DatabaseSetup.php';

// Validación exclusiva para CLI (cron job)
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos');
}

// Configuración
$config = [
    'max_jobs' => 5,               // Máximo de trabajos por ejecución
    'lock_file' => __DIR__.'/worker.lock', // Archivo de bloqueo
    'log_file' => __DIR__.'/worker.log'    // Archivo de registro
];

// Evitar ejecuciones duplicadas
if (file_exists($config['lock_file'])) {
    $lockTime = filemtime($config['lock_file']);
    if (time() - $lockTime < 3600) { // Máximo 1 hora de ejecución
        error_log(date('[Y-m-d H:i:s]')." Worker ya en ejecución\n", 3, $config['log_file']);
        exit(0);
    }
    @unlink($config['lock_file']); // Eliminar lock antiguo
}

file_put_contents($config['lock_file'], getmypid());

// Registrar inicio
error_log(date('[Y-m-d H:i:s]')." Iniciando worker\n", 3, $config['log_file']);

try {
    $mainDB = new mainModel();
    $processed = 0;
    
    for ($i = 0; $i < $config['max_jobs']; $i++) {
        // Usar consulta no estática
        $jobs = $mainDB->ejecutar_consulta_simple(
            "SELECT * FROM jobs_queue 
             WHERE status = 'pending' AND attempts < max_attempts
             ORDER BY created_at ASC 
             LIMIT 1"
        );
        
        if ($jobs->num_rows === 0) break;
        
        $job = $jobs->fetch_assoc();
        $data = json_decode($job['data'], true);
        
        try {
            // Actualizar estado
            $mainDB->ejecutar_consulta_simple(
                "UPDATE jobs_queue SET 
                 status = 'processing',
                 started_at = NOW(),
                 attempts = attempts + 1 
                 WHERE id = {$job['id']}"
            );
            
            // Procesar importación
            $dbSetup = new DatabaseSetup(
                SERVER,
                CPANEL_DB_USERNAME,
                CPANEL_DB_PASSWORD,
                $data['db_name']
            );
            
            $sqlFile = $_SERVER['DOCUMENT_ROOT'].'/plantilla/plantilla_izzy.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("Archivo SQL no encontrado: $sqlFile");
            }
            
            $dbSetup->importSQL($sqlFile);
            
            // Actualizar estados
            $mainDB->ejecutar_consulta_simple(
                "UPDATE server_customers SET 
                 db_imported = 1 
                 WHERE server_customers_id = {$data['server_customers_id']}"
            );
            
            $mainDB->ejecutar_consulta_simple(
                "UPDATE jobs_queue SET 
                 status = 'completed', 
                 finished_at = NOW()
                 WHERE id = {$job['id']}"
            );
            
            $processed++;
            error_log(date('[Y-m-d H:i:s]')." Job {$job['id']} completado\n", 3, $config['log_file']);
        } catch (Exception $e) {
            $errorMsg = $mainDB->connection()->real_escape_string($e->getMessage());
            $mainDB->ejecutar_consulta_simple(
                "UPDATE jobs_queue SET 
                 status = IF(attempts >= max_attempts-1, 'failed', 'pending'),
                 error_message = '$errorMsg'
                 WHERE id = {$job['id']}"
            );
            error_log(date('[Y-m-d H:i:s]')." Error en job {$job['id']}: $errorMsg\n", 3, $config['log_file']);
        }
    }
    
    error_log(date('[Y-m-d H:i:s]')." Procesados $processed trabajos\n", 3, $config['log_file']);
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s]')." Error general: ".$e->getMessage()."\n", 3, $config['log_file']);
} finally {
    if (file_exists($config['lock_file'])) {
        @unlink($config['lock_file']);
    }
    if (isset($mainDB)) {
        $mainDB->connection()->close();
    }
}