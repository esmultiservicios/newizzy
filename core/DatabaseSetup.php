<?php
// DatabaseSetup.php - Versión optimizada para estructura de proyecto

// Determinar rutas base
$basePath = (isset($peticionAjax) && $peticionAjax) ? dirname(__DIR__) . '/core/' : __DIR__ . '/';

// Incluir dependencias
require_once $basePath . 'mainModel.php';

class DatabaseSetup {
    private $dbHost;
    private $dbUser;
    private $dbPassword;
    private $dbName;

    public function __construct($host = null, $user = null, $password = null, $name = null) {
        $this->dbHost = $host ?? SERVER;
        $this->dbUser = $user ?? USER;
        $this->dbPassword = $password ?? PASS;
        $this->dbName = $name ?? $GLOBALS['db'];
    }

    /**
     * Importa una base de datos desde un archivo SQL
     */
    public function importDatabase($dbName, $dbUser, $dbPassword, $sqlFile) {
        try {
            // Verificar si el archivo existe
            if (!file_exists($sqlFile)) {
                throw new Exception("Archivo SQL no encontrado: $sqlFile");
            }

            // Comando para importar la base de datos
            $command = "mysql -h {$this->dbHost} -u {$dbUser} -p{$dbPassword} {$dbName} < {$sqlFile} 2>&1";
            
            // Ejecutar el comando
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception("Error al importar: " . implode("\n", $output));
            }

            return true;
        } catch (Exception $e) {
            error_log("Error en importDatabase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Conecta a una base de datos específica
     */
    public function connectToDatabase($dbName, $dbUser, $dbPassword) {
        try {
            $connection = new mysqli($this->dbHost, $dbUser, $dbPassword, $dbName);
            
            if ($connection->connect_error) {
                throw new Exception("Error de conexión: " . $connection->connect_error);
            }

            return $connection;
        } catch (Exception $e) {
            error_log("Error en connectToDatabase: " . $e->getMessage());
            return false;
        }
    }
}