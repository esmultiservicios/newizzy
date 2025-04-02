<?php

/*
 * Parametros de conexión a la DB
 */

if (!isset($_SESSION['user_sd'])) {
    session_start(['name' => 'SD']);
}

// DATOS DE CONEXION DEL CLIENTE
const SERVER = 'localhost';
const USER = 'esmultiservicios_root';
const PASS = 'o8lXA0gtIO$@';

// DATOS DE CONEXION SERVIDOR PRINCIPAL
const SERVER_MAIN = 'localhost';
const DB_MAIN = 'esmultiservicios_izzy';

$GLOBALS['DB_MAIN'] = DB_MAIN;

const USER_MAIN = 'esmultiservicios_root';
const PASS_MAIN = 'o8lXA0gtIO$@';

// cPanel
// const tokencPanel = 'cpsessCPBCU71RXAL9R3908OM444JE0OECS6LM';
const tokencPanel = 'EGUW3PINSFSEVRMMBP7BU6D4DJ78OP0B';
const usernamecPanel = 'esmultiservicios';
const passwordcPanel = 'CEdwin82003%*';

// BASE DE DATOS EXCEPTION LOGIN CONTROLADOR
const DB_MAIN_LOGIN_CONTROLADOR = DB_MAIN;  // LA BASE DE DATOS QUE ESTE AQUÍ SE EXCEPTÚA EN EL LOGIN CONTROLADOR

/*
 * Para encrptar y Desencriptar
 * Nota: Estos valores no se deben cambiar, si hay datos en la DB
 */
const METHOD = 'AES-256-CBC';
const SECRET_KEY = '$DP_@2020';
const SECRET_IV = '10172';
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
