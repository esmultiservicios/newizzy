<?php

if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";
}

class DatabaseSetup {
    private $dbHost;
    private $dbUser;
    private $dbPassword;
    private $dbName;

    /**
     * Constructor de la clase
     *
     * @param string $host Host de la base de datos
     * @param string $user Usuario de la base de datos
     * @param string $password Contraseña del usuario
     * @param string $name Nombre de la base de datos
     */
    public function __construct($host, $user, $password, $name) {
        $this->dbHost = $host;
        $this->dbUser = $user;
        $this->dbPassword = $password;
        $this->dbName = $name;
    }

    /**
     * Verifica si la base de datos existe y es accesible
     */
    public function verifyDatabaseAccess() {
        $connection = @new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        
        if ($connection->connect_error) {
            return [
                'success' => false,
                'error' => $connection->connect_error,
                'errno' => $connection->connect_errno
            ];
        }
        
        $connection->close();
        return ['success' => true];
    }

    /**
     * Otorga privilegios a un usuario EXISTENTE en MySQL (para cPanel o entornos restringidos)
     * 
     * @param string $user Usuario existente al que se le darán permisos (ej: 'usuario_existente')
     * @param string $database Base de datos objetivo (ej: 'basedatos')
     * @param array $privileges Privilegios a otorgar (ej: ['SELECT', 'INSERT'])
     * @return bool|array True si tuvo éxito, array con error si falló
     */
    public function grantPrivilegesToExistingUser($user, $database, $privileges = ['ALL PRIVILEGES']) {
        // Conectar con las credenciales disponibles (MYSQL_USER y MYSQL_PASS)
        $connection = new mysqli(SERVER, MYSQL_USER, MYSQL_PASS);
        
        if ($connection->connect_error) {
            return ['error' => "Error de conexión: {$connection->connect_error}"];
        }

        // Escapar nombres para evitar SQL injection (aunque en cPanel a veces tienen prefijos)
        $escapedUser = $connection->real_escape_string($user);
        $escapedDb = $connection->real_escape_string($database);

        // Consulta GRANT (sin crear usuario, pues ya existe)
        $grantQuery = "GRANT " . implode(', ', $privileges) . " ON `{$escapedDb}`.* TO '{$escapedUser}'@'localhost'";
        $flushQuery = "FLUSH PRIVILEGES";

        try {
            $connection->autocommit(false);
            
            if (!$connection->query($grantQuery)) {
                throw new Exception("Error al otorgar privilegios: {$connection->error}");
            }
            
            if (!$connection->query($flushQuery)) {
                throw new Exception("Error al actualizar privilegios: {$connection->error}");
            }
            
            $connection->commit();
            $connection->close();
            return true;
        } catch (Exception $e) {
            $connection->rollback();
            $connection->close();
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Método para importar un archivo SQL en la base de datos
     *
     * @param string $filePath Ruta completa al archivo SQL
     * @return bool|array True si la importación fue exitosa, o un array con errores si falló
     */
    public function importSQL($filePath) {
        // Verificar si el archivo existe
        if (!file_exists($filePath)) {
            return ['error' => "El archivo SQL no existe: {$filePath}"];
        }

        // Leer el contenido del archivo SQL
        $sqlContent = file_get_contents($filePath);
        if ($sqlContent === false) {
            return ['error' => "No se pudo leer el archivo SQL: {$filePath}"];
        }

        // Dividir el contenido en consultas individuales
        $queries = $this->splitSQLQueries($sqlContent);

        // Conectar a la base de datos
        $connection = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        if ($connection->connect_error) {
            return ['error' => "Error de conexión a la base de datos: {$connection->connect_error}"];
        }

        // Desactivar autocommit para iniciar una transacción
        $connection->autocommit(false);

        try {
            // Ejecutar cada consulta
            foreach ($queries as $query) {
                if (!empty(trim($query))) {
                    if (!$connection->query($query)) {
                        throw new Exception("Error al ejecutar la consulta: {$connection->error}");
                    }
                }
            }

            // Confirmar la transacción
            $connection->commit();
            $connection->close();
            return true;
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $connection->rollback();
            $connection->close();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Divide el contenido de un archivo SQL en consultas individuales
     *
     * @param string $sqlContent Contenido del archivo SQL
     * @return array Array de consultas SQL
     */
    private function splitSQLQueries($sqlContent) {
        // Eliminar comentarios y dividir por punto y coma (;)
        $sqlContent = preg_replace('/--.*$/m', '', $sqlContent); // Eliminar comentarios de una línea
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent); // Eliminar comentarios multilínea
        $queries = explode(';', $sqlContent);
        return array_map('trim', $queries);
    }
}