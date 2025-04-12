<?php
if($peticionAjax){
    require_once "../core/configAPP.php";
    require_once "../core/mainModel.php";
}else{
    require_once "./core/configAPP.php";
    require_once "./core/mainModel.php";    
}

/**
 * Clase para interactuar con la API de cPanel
 * 
 * Proporciona métodos para gestionar bases de datos, usuarios MySQL y subdominios
 * a través de la API de cPanel, incluyendo operaciones CRUD seguras.
 */
class cPanelAPI {
    private $token;
    private $baseUrl;
    private $username;
    private $password;
    private $cpanelUser;
    private $timeout;
    private $verifySSL;

    /**
     * Constructor de la clase cPanelAPI
     * 
     * Inicializa la conexión con la API de cPanel usando las constantes definidas en config.php
     */
    public function __construct() {
        $this->token = CPANEL_TOKEN;
        $this->cpanelUser = CPANEL_USERNAME;
        $this->baseUrl = "https://".CPANEL_HOST.":".CPANEL_PORT."/cpsess{$this->token}/execute/";
        $this->username = CPANEL_USERNAME;
        $this->password = CPANEL_PASSWORD;
        $this->timeout = API_TIMEOUT;
        $this->verifySSL = SSL_VERIFICATION;
    }

    /**
     * Ejecuta una llamada a la API de cPanel
     * 
     * @param string $module Módulo de cPanel a utilizar (ej: 'Mysql', 'SubDomain')
     * @param string $function Función específica del módulo a ejecutar
     * @param array $params Parámetros para la llamada API
     * @return array Respuesta de la API decodificada
     * @throws Exception Si ocurre un error en la conexión, HTTP o formato JSON
     */
    private function executeApiCall($module, $function, $params = []) {
        $url = $this->baseUrl . $module . '/' . $function;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "$this->username:$this->password",
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error $httpCode");
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Error: " . json_last_error_msg());
        }

        return $data;
    }

    /**************************************/
    /* MÉTODOS DE CREACIÓN/CONFIGURACIÓN  */
    /**************************************/

    /**
     * Crea una nueva base de datos MySQL
     * 
     * @param string $dbName Nombre de la base de datos (con prefijo de usuario)
     * @return array Resultado de la operación
     * @throws Exception Si falla la creación
     * 
     * @example 
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->createDatabase('esmultiservicios_mibase');
     */
    public function createDatabase($dbName) {
        try {
            error_log("Intentando crear BD: ".$dbName);
            
            $result = $this->executeApiCall('Mysql', 'create_database', [
                'name' => $dbName
            ]);
            
            error_log("Respuesta de cPanel: ".json_encode($result));
            
            if ($result['status'] == 1) {
                return [
                    'success' => true,
                    'db_name' => $dbName,
                    'message' => 'Database created successfully'
                ];
            }
            
            throw new Exception($this->parseError($result));
            
        } catch (Exception $e) {
            error_log("Error al crear BD: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Crea un usuario de MySQL
     * 
     * @param string $username Nombre de usuario (sin prefijo)
     * @param string $password Contraseña para el usuario
     * @return array Resultado de la operación
     * @throws Exception Si falla la creación
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->createDatabaseUser('miusuario', 'micontraseñaSegura123');
     */
    public function createDatabaseUser($username, $password) {
        try {
            $result = $this->executeApiCall('Mysql', 'create_user', [
                'name' => $username,
                'password' => $password
            ]);
            
            if ($result['status'] == 1) {
                return [
                    'success' => true,
                    'db_user' => $this->cpanelUser . '_' . $username,
                    'message' => 'User created successfully'
                ];
            }
            
            throw new Exception($this->parseError($result));
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                return [
                    'success' => true,
                    'db_user' => $this->cpanelUser . '_' . $username,
                    'message' => 'User already exists'
                ];
            }
            throw new Exception("Create User Error: " . $e->getMessage());
        }
    }

    /**
     * Asigna todos los privilegios a un usuario sobre una base de datos
     * 
     * @param string $dbName Nombre de la base de datos (sin prefijo)
     * @param string $username Nombre de usuario (sin prefijo)
     * @return array Resultado de la operación
     * @throws Exception Si falla la asignación
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->grantAllPrivileges('mibase', 'miusuario');
     */
    public function grantAllPrivileges($dbName, $username) {
        try {
            $result = $this->executeApiCall('Mysql', 'set_privileges_on_database', [
                'user' => $username,
                'database' => $dbName,
                'privileges' => 'ALL PRIVILEGES'
            ]);
            
            if ($result['status'] != 1) {
                throw new Exception($this->parseError($result));
            }
            
            return ['success' => true, 'message' => 'Privileges granted successfully'];
            
        } catch (Exception $e) {
            error_log("Error en grantAllPrivileges: " . $e->getMessage());
            throw new Exception("Grant Privileges Error: " . $e->getMessage());
        }
    }

    /**
     * Crea un subdominio en cPanel
     * 
     * @param string $subdomain Nombre del subdominio (sin el dominio principal)
     * @param string $rootDir Directorio raíz (relativo al home del usuario)
     * @return array Resultado de la operación
     * @throws Exception Si falla la creación
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->createSubdomain('misdemo', 'public_html/demo');
     */
    public function createSubdomain($subdomain, $rootDir = 'public_html') {
        try {
            $result = $this->executeApiCall('SubDomain', 'addsubdomain', [
                'domain' => $subdomain,
                'rootdomain' => CPANEL_DOMINIO,
                'dir' => $rootDir
            ]);
            
            if ($result['status'] == 1) {
                return [
                    'success' => true,
                    'subdomain' => $subdomain . '.' . CPANEL_DOMINIO,
                    'message' => 'Subdomain created successfully',
                    'full_response' => $result
                ];
            }
            
            throw new Exception($this->parseError($result));
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                return [
                    'success' => true,
                    'subdomain' => $subdomain . '.' . CPANEL_DOMINIO,
                    'message' => 'Subdomain already exists'
                ];
            }
            throw new Exception("Create Subdomain Error: " . $e->getMessage());
        }
    }

    /**
     * Configuración completa de base de datos
     * 
     * @param array $dbConfig Configuración de la base de datos
     * @return array Resultado completo
     * @throws Exception Si falla cualquier paso
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->setupCompleteDatabase([
     *     'db_name' => 'esmultiservicios_mibase',
     *     'db_user' => 'miusuario',
     *     'db_password' => 'micontraseñaSegura123'
     * ]);
     */
    public function setupCompleteDatabase($dbConfig) {
        try {
            // 1. Crear base de datos
            $dbResult = $this->createDatabase($dbConfig['db_name']);
            // 2. Crear usuario
            $userResult = $this->createDatabaseUser($dbConfig['db_user'], $dbConfig['db_password']);
            // 3. Asignar privilegios
            $privilegesResult = $this->grantAllPrivileges($dbConfig['db_name'], $dbConfig['db_user']);
            // 4. Actualizar privilegios
            $updateResult = $this->executeApiCall('Mysql', 'update_privileges');
            if ($updateResult['status'] != 1) {
                throw new Exception("Failed to update privileges: " . $this->parseError($updateResult));
            }
    
            return [
                'success' => true,
                'database' => $dbResult,
                'user' => $userResult,
                'privileges' => $privilegesResult,
                'connection' => [
                    'host' => 'localhost',
                    'database' => $dbConfig['db_name'],
                    'username' => $dbConfig['db_user'],
                    'password' => $dbConfig['db_password']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error en setupCompleteDatabase: " . $e->getMessage());
            throw new Exception("Database setup error: " . $e->getMessage());
        }
    }

    /**************************************/
    /*  MÉTODOS DE ELIMINACIÓN SEGUROS    */
    /**************************************/

    /**
     * Elimina una base de datos de forma segura
     * 
     * @param string $fullDbName Nombre completo de la BD (con prefijo)
     * @param bool $confirm Requiere confirmación explícita
     * @return array Resultado de la operación
     * @throws Exception Si falla la eliminación
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->deleteDatabase('esmultiservicios_mibase');
     */
    public function deleteDatabase($fullDbName, $confirm = true) {
        try {
            if ($confirm !== true) {
                throw new Exception("Se requiere confirmación explícita para eliminar una base de datos");
            }

            $requiredPrefix = $this->cpanelUser . '_';
            
            if (strpos($fullDbName, $requiredPrefix) !== 0) {
                throw new Exception("El nombre de la BD debe comenzar con '{$requiredPrefix}'");
            }
            
            $dbNameForApi = substr($fullDbName, strlen($requiredPrefix));
            
            $result = $this->executeApiCall('Mysql', 'delete_database', [
                'name' => $dbNameForApi
            ]);
            
            if ($result['status'] == 1) {
                error_log("Base de datos eliminada: {$fullDbName}");
                return [
                    'success' => true,
                    'message' => 'Base de datos eliminada correctamente'
                ];
            }
            
            throw new Exception($this->parseError($result));
            
        } catch (Exception $e) {
            error_log("Error al eliminar BD {$fullDbName}: " . $e->getMessage());
            throw new Exception("Error al eliminar base de datos: " . $e->getMessage());
        }
    }

    /**
     * Elimina un usuario MySQL de forma segura
     * 
     * @param string $fullUsername Nombre completo del usuario (con prefijo)
     * @param bool $confirm Requiere confirmación explícita
     * @return array Resultado de la operación
     * @throws Exception Si falla la eliminación
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->deleteUser('esmultiservicios_miusuario');
     */
    public function deleteUser($fullUsername, $confirm = true) {
        try {
            if ($confirm !== true) {
                throw new Exception("Se requiere confirmación explícita para eliminar un usuario");
            }

            $requiredPrefix = $this->cpanelUser . '_';
            
            if (strpos($fullUsername, $requiredPrefix) !== 0) {
                throw new Exception("El nombre de usuario debe comenzar con '{$requiredPrefix}'");
            }
            
            $usernameForApi = substr($fullUsername, strlen($requiredPrefix));
            
            $result = $this->executeApiCall('Mysql', 'delete_user', [
                'name' => $usernameForApi
            ]);
            
            if ($result['status'] == 1) {
                error_log("Usuario eliminado: {$fullUsername}");
                return [
                    'success' => true,
                    'message' => 'Usuario eliminado correctamente'
                ];
            }
            
            throw new Exception($this->parseError($result));
            
        } catch (Exception $e) {
            error_log("Error al eliminar usuario {$fullUsername}: " . $e->getMessage());
            throw new Exception("Error al eliminar usuario: " . $e->getMessage());
        }
    }

    /**
     * Elimina un subdominio de forma segura
     * 
     * @param string $subdomain Nombre del subdominio (sin dominio principal)
     * @param bool $confirm Requiere confirmación explícita
     * @return array Resultado de la operación
     * @throws Exception Si falla la eliminación
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->deleteSubdomain('misdemo');
     */
    public function deleteSubdomain($subdomain, $confirm = true) {
        try {
            if ($confirm !== true) {
                throw new Exception("Se requiere confirmación explícita para eliminar un subdominio");
            }

            $result = $this->executeApiCall('SubDomain', 'delsubdomain', [
                'domain' => $subdomain,
                'rootdomain' => CPANEL_DOMINIO
            ]);
            
            if ($result['status'] == 1) {
                error_log("Subdominio eliminado: {$subdomain}.".CPANEL_DOMINIO);
                return [
                    'success' => true,
                    'message' => 'Subdominio eliminado correctamente'
                ];
            }
            
            throw new Exception($this->parseError($result));
            
        } catch (Exception $e) {
            error_log("Error al eliminar subdominio {$subdomain}: " . $e->getMessage());
            throw new Exception("Error al eliminar subdominio: " . $e->getMessage());
        }
    }

    /**
     * Parsea errores de la API de cPanel
     * 
     * @param array $response Respuesta de la API
     * @return string Mensaje de error formateado
     */
    private function parseError($response) {
        if (isset($response['errors'])) {
            return is_array($response['errors']) 
                ? implode('; ', $response['errors']) 
                : $response['errors'];
        }
        return $response['message'] ?? 'Unknown cPanel API error';
    }
}

/**
 * Función auxiliar para limpiar cadenas y convertirlas en nombres válidos para subdominios
 * 
 * @param string $string Cadena original a convertir
 * @return string Cadena convertida a formato válido para subdominio:
 *                - Solo letras minúsculas, números y guiones
 *                - Sin guiones consecutivos o al inicio/final
 *                - No comienza con número (agrega prefijo 's-' si es necesario)
 *                - Longitud máxima de 63 caracteres (límite DNS)
 * 
 * @example
 * $subdomain = cleanStringForSubdomain('Mi Sitio Web 2023');
 * // Devuelve: 'mi-sitio-web-2023'
 */
function cleanStringForSubdomain($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    
    if (is_numeric(substr($string, 0, 1))) {
        $string = 's-' . $string;
    }
    
    return substr($string, 0, 63);
}