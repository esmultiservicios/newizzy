<?php

/*
 * Parametros de conexión a la DB
 */

// Reemplaza esto:
if (!isset($_SESSION['user_sd'])) {
    session_start(['name' => 'SD']);
}

// Por esto:
if (!isset($_SESSION['user_sd'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(['name' => 'SD']);
    }
    // Si la sesión está activa pero no tiene 'user_sd'
    $_SESSION['user_sd'] = null; // O el valor inicial que necesites
}

// DATOS DE CONEXION DEL CLIENTE
const SERVER = 'localhost';
const USER = '***REMOVED***';
const PASS = '***REMOVED***';

// DATOS DE CONEXION SERVIDOR PRINCIPAL
const SERVER_MAIN = 'localhost';
const DB_MAIN = 'esmultiservicios_izzy';

// Versión mejorada para definir constantes de nombre de BD
define('DB_PREFIX', 'smultiservicios_');
define('DB_SUFFIX', '_izzy');
define('DB_MAX_LENGTH', 10); // Longitud máxima para el identificador único

$GLOBALS['DB_MAIN'] = DB_MAIN;

const USER_MAIN = '***REMOVED***';
const PASS_MAIN = '***REMOVED***';

// cPanel
// const tokencPanel = '***REMOVED***';
const tokencPanel = '***REMOVED***';
const usernamecPanel = '***REMOVED***';
const passwordcPanel = '***REMOVED***';

// BASE DE DATOS EXCEPTION LOGIN CONTROLADOR
const DB_MAIN_LOGIN_CONTROLADOR = DB_MAIN;  // LA BASE DE DATOS QUE ESTE AQUÍ SE EXCEPTÚA EN EL LOGIN CONTROLADOR

/*
 * Para encrptar y Desencriptar
 * Nota: Estos valores no se deben cambiar, si hay datos en la DB
 */
const METHOD = 'AES-256-CBC';
const SECRET_KEY = '***REMOVED***';
const SECRET_IV = '***REMOVED***';
const SISTEMA_PRUEBA = 'NO';  // SI o NO

initConfig();  // Llamar a la función para inicializar la configuración

function initConfig()
{
    // Verificar si la sesión está activa y no ha expirado
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Verificar si $_SESSION['db_cliente'] está definido y no está vacío
        if (isset($_SESSION['db_cliente']) && $_SESSION['db_cliente'] !== '') {
            $db_cliente = $_SESSION['db_cliente'];
        } else {
            $db_cliente = $GLOBALS['DB_MAIN'];  // Valor predeterminado si $_SESSION['db_cliente'] no está definido o está vacío
        }

        // DATOS DE CONEXIÓN DEL CLIENTE
        $GLOBALS['db'] = $db_cliente;
    } else {
        // La sesión ha expirado, puedes manejar esto de alguna manera, por ejemplo, redirigiendo al usuario a una página de inicio de sesión.
        // Aquí puedes decidir qué hacer en caso de sesión expirada.
        // Por ejemplo, puedes redirigir al usuario a una página de inicio de sesión.
        header('Location: ' . SERVERURL);
        exit;
    }
}
