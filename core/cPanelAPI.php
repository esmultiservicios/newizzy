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

    /**
     * Métodos adicionales para WHM/cPanel Accounts
     * (Agregar al final de la clase, antes del cierre })
     */

    /**
     * Crea una nueva cuenta de cPanel (requiere acceso WHM)
     * 
     * @param array $accountParams Parámetros de la cuenta
     * @return array Resultado de la operación
     * @throws Exception Si falla la creación
     * 
     * @example
     * $cpanel = new cPanelAPI();
     * $result = $cpanel->createAccount([
     *     'username' => 'nuevocliente',
     *     'password' => 'contraseñaSegura123',
     *     'domain' => 'midominio.com',
     *     'plan' => 'default'
     * ]);
     */
    public function createAccount($accountParams) {
        try {
            $defaultParams = [
                'api.version' => 1,
                'plan' => 'default',
                'featurelist' => 'default',
                'quota' => 1000, // MB
                'ip' => 'y', // Usar IP dedicada si está disponible
                'cgi' => 1,
                'frontpage' => 0,
                'hasshell' => 0,
                'contactemail' => $accountParams['email'] ?? 'admin@'.$accountParams['domain'],
                'cpmod' => 'paper_lantern', // o 'x3' para el tema clásico
                'maxftp' => 10,
                'maxsql' => 10,
                'maxpop' => 10,
                'maxlst' => 10,
                'maxsub' => 10,
                'maxpark' => 10,
                'maxaddon' => 10,
                'bwlimit' => 1000, // MB
                'language' => 'es' // Español
            ];

            $params = array_merge($defaultParams, $accountParams);
            
            $result = $this->executeWhmApiCall('createacct', $params);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
                'message' => 'Account created successfully'
            ];
        } catch (Exception $e) {
            error_log("Error creating cPanel account: " . $e->getMessage());
            throw new Exception("Account creation failed: " . $e->getMessage());
        }
    }

    /**
     * Suspende una cuenta de cPanel
     * 
     * @param string $username Nombre de usuario de la cuenta
     * @param string $reason Motivo de la suspensión
     * @return array Resultado de la operación
     */
    public function suspendAccount($username, $reason = '') {
        try {
            $result = $this->executeWhmApiCall('suspendacct', [
                'user' => $username,
                'reason' => $reason
            ]);
            
            return [
                'success' => true,
                'message' => 'Account suspended successfully'
            ];
        } catch (Exception $e) {
            throw new Exception("Suspend failed: " . $e->getMessage());
        }
    }

    /**
     * Reactiva una cuenta de cPanel suspendida
     * 
     * @param string $username Nombre de usuario de la cuenta
     * @return array Resultado de la operación
     */
    public function unsuspendAccount($username) {
        try {
            $result = $this->executeWhmApiCall('unsuspendacct', [
                'user' => $username
            ]);
            
            return [
                'success' => true,
                'message' => 'Account unsuspended successfully'
            ];
        } catch (Exception $e) {
            throw new Exception("Unsuspend failed: " . $e->getMessage());
        }
    }

    /**
     * Cambia el paquete/plan de una cuenta
     * 
     * @param string $username Nombre de usuario
     * @param string $newPlan Nombre del nuevo plan
     * @return array Resultado de la operación
     */
    public function changeAccountPlan($username, $newPlan) {
        try {
            $result = $this->executeWhmApiCall('changepackage', [
                'user' => $username,
                'pkg' => $newPlan
            ]);
            
            return [
                'success' => true,
                'message' => 'Plan changed successfully'
            ];
        } catch (Exception $e) {
            throw new Exception("Plan change failed: " . $e->getMessage());
        }
    }

    /**
     * Obtiene información detallada de una cuenta
     * 
     * @param string $username Nombre de usuario
     * @return array Información de la cuenta
     */
    public function getAccountInfo($username) {
        try {
            $result = $this->executeWhmApiCall('accountsummary', [
                'user' => $username
            ]);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
                'message' => 'Account info retrieved'
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get account info: " . $e->getMessage());
        }
    }

    /**
     * Lista todas las cuentas en el servidor
     * 
     * @return array Listado de cuentas
     */
    public function listAccounts() {
        try {
            $result = $this->executeWhmApiCall('listaccts');
            return [
                'success' => true,
                'accounts' => $result['data'] ?? $result,
                'message' => 'Accounts listed successfully'
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to list accounts: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta llamadas a la API de WHM
     * (Método privado para uso interno)
     */
    private function executeWhmApiCall($function, $params = []) {
        $url = "https://".WHM_HOST.":".WHM_PORT."/json-api/$function";
        
        // Usar token si está configurado, de lo contrario usar autenticación básica
        $headers = [
            'Authorization: whm '.WHM_USERNAME.':'.WHM_TOKEN,
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => WHM_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("WHM cURL Error: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("WHM HTTP Error $httpCode");
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("WHM JSON Error: " . json_last_error_msg());
        }

        // Verificar si la API devolvió un error
        if (isset($data['metadata']['result']) && $data['metadata']['result'] == 0) {
            throw new Exception("WHM API Error: " . ($data['metadata']['reason'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Verifica disponibilidad de un nombre de usuario para cuentas
     * 
     * @param string $username Nombre de usuario a verificar
     * @return bool True si está disponible
     */
    public function checkUsernameAvailability($username) {
        try {
            $result = $this->executeWhmApiCall('verify_new_username', [
                'user' => $username
            ]);
            
            return $result['data']['available'] ?? false;
        } catch (Exception $e) {
            error_log("Username check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los planes/paquetes disponibles en WHM
     * 
     * @return array Listado de planes
     */
    public function getAvailablePlans() {
        try {
            $result = $this->executeWhmApiCall('listpkgs');
            return [
                'success' => true,
                'plans' => $result['data'] ?? $result,
                'message' => 'Plans retrieved successfully'
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get plans: " . $e->getMessage());
        }
    }

    /**
     * Genera enlace de inicio de sesión a cPanel (Single Sign-On)
     * 
     * @param string $username Usuario cPanel
     * @param string $service Servicio a abrir (ej: 'cpanel', 'webmail')
     * @return string URL de acceso directo
     */
    public function generateLoginLink($username, $service = 'cpanel') {
        try {
            $result = $this->executeWhmApiCall('create_user_session', [
                'user' => $username,
                'service' => $service
            ]);
            
            return $result['data']['url'] ?? '';
        } catch (Exception $e) {
            error_log("Login link generation failed: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Obtiene el uso de recursos de una cuenta
     * 
     * @param string $username Usuario cPanel
     * @return array Uso de recursos (CPU, memoria, disco, etc.)
     */
    public function getResourceUsage($username) {
        try {
            $result = $this->executeWhmApiCall('showbw', ['user' => $username]);
            return [
                'success' => true,
                'usage' => $result['data'] ?? $result,
                'message' => 'Resource usage retrieved'
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get resource usage: " . $e->getMessage());
        }
    }

    /**
     * Cambia la contraseña de una cuenta cPanel
     * 
     * @param string $username Usuario cPanel
     * @param string $newPassword Nueva contraseña
     * @return array Resultado de la operación
     */
    public function changeAccountPassword($username, $newPassword) {
        try {
            $result = $this->executeWhmApiCall('passwd', [
                'user' => $username,
                'pass' => $newPassword
            ]);
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        } catch (Exception $e) {
            throw new Exception("Password change failed: " . $e->getMessage());
        }
    }

    /**
     * Lista dominios adicionales de una cuenta
     * 
     * @param string $username Usuario cPanel
     * @return array Listado de dominios
     */
    public function listAccountDomains($username) {
        try {
            $result = $this->executeWhmApiCall('listaddondomains', ['user' => $username]);
            return [
                'success' => true,
                'domains' => $result['data'] ?? $result,
                'message' => 'Domains listed successfully'
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to list domains: " . $e->getMessage());
        }
    }    

    /**
     * Métodos específicos para integración con WHMCS
     */

    /**
     * Crea una cuenta en cPanel y registra los datos en WHMCS
     * 
     * @param array $clientData Datos del cliente
     * @param array $serviceData Datos del servicio
     * @return array Resultado completo
     */
    public function whmcsCreateAccount($clientData, $serviceData) {
        try {
            // 1. Verificar disponibilidad
            if (!$this->checkUsernameAvailability($serviceData['username'])) {
                throw new Exception("Username not available");
            }
            
            // 2. Crear cuenta
            $accountParams = [
                'username' => $serviceData['username'],
                'password' => $serviceData['password'],
                'domain' => $serviceData['domain'],
                'plan' => $serviceData['plan'],
                'email' => $clientData['email']
            ];
            
            $accountResult = $this->createAccount($accountParams);
            
            // 3. Crear base de datos (opcional)
            $dbResult = [];
            if ($serviceData['create_database'] ?? false) {
                $dbResult = $this->setupCompleteDatabase([
                    'db_name' => $serviceData['username'] . '_db',
                    'db_user' => $serviceData['username'] . '_user',
                    'db_password' => bin2hex(random_bytes(8))
                ]);
            }
            
            // 4. Generar enlace de acceso
            $loginUrl = $this->generateLoginLink($serviceData['username']);
            
            return [
                'success' => true,
                'account' => $accountResult,
                'database' => $dbResult,
                'login_url' => $loginUrl,
                'whmcs_data' => [
                    'service_id' => $serviceData['service_id'],
                    'client_id' => $clientData['client_id']
                ]
            ];
        } catch (Exception $e) {
            error_log("WHMCS Account Creation Error: " . $e->getMessage());
            
            // Revertir cambios si es posible
            if (isset($accountResult) && $accountResult['success']) {
                $this->suspendAccount($serviceData['username'], "Error durante creación: " . $e->getMessage());
            }
            
            throw new Exception("WHMCS integration error: " . $e->getMessage());
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


/*
#####################################################################################################################
Ejemplo 1: Crear cuenta cPanel
$cpanel = new cPanelAPI();
$result = $cpanel->createAccount([
    'username' => 'nuevocliente',
    'password' => 'P@ssw0rd123!',
    'domain' => 'clientedemo.com',
    'plan' => 'starter',
    'email' => 'cliente@email.com'
]);

Ejemplo 2: Integración con WHMCS
$cpanel = new cPanelAPI();
$result = $cpanel->whmcsCreateAccount(
    [
        'client_id' => 1001,
        'email' => 'cliente@email.com'
    ],
    [
        'service_id' => 'SVC-5001',
        'username' => 'cliente5001',
        'password' => 'P@ssw0rd123!',
        'domain' => 'cliente5001.tudominio.com',
        'plan' => 'business',
        'create_database' => true
    ]
);

Ejemplo 3: Obtener información de cuenta
$cpanel = new cPanelAPI();
$info = $cpanel->getAccountInfo('usuarioexistente');
*/