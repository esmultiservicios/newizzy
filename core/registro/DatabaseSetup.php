<?php
// DatabaseSetup.php - Versión optimizada para estructura de proyecto

// Dependencia compartida localizada en core/.
require_once dirname(__DIR__) . "/mainModel.php";

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

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbUser)) {
                throw new Exception('Nombre de base o usuario MySQL no válido.');
            }
            $credentials = tempnam(sys_get_temp_dir(), 'izzy_mysql_');
            if ($credentials === false) throw new Exception('No se pudo crear archivo temporal de autenticación.');
            chmod($credentials, 0600);
            $escape = static function ($value) {
                return str_replace(["\\", "\n", "\r"], ["\\\\", "\\n", "\\r"], (string)$value);
            };
            $contents = "[client]\nuser=".$escape($dbUser)."\npassword=".$escape($dbPassword)."\nhost=".$escape($this->dbHost)."\n";
            if (file_put_contents($credentials, $contents) === false) {
                @unlink($credentials);
                throw new Exception('No se pudieron escribir las credenciales temporales MySQL.');
            }
            try {
                $command = 'mysql --defaults-extra-file='.escapeshellarg($credentials).' '.escapeshellarg($dbName).' < '.escapeshellarg($sqlFile).' 2>&1';
                $output = [];$returnVar = 1;
                exec($command, $output, $returnVar);
                if ($returnVar !== 0) throw new Exception('Importación SQL fallida (código '.$returnVar.').');
            } finally { @unlink($credentials); }

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