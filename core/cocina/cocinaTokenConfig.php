<?php
// core/cocina/cocinaTokenConfig.php

/*
 * IZZY - Configuración de cifrado para accesos de Cocina
 *
 * La clave privada NO se almacena en este archivo.
 * Se obtiene desde el .env externo cargado por core/configAPP.php.
 *
 * Variable requerida:
 * IZZY_COCINA_TOKEN_CIPHER_KEY
 *
 * IMPORTANTE:
 * No cambiar la clave si ya existen accesos generados, ya que los enlaces
 * existentes podrían dejar de mostrarse desde Configuración y tendrían
 * que regenerarse.
 */

if (!function_exists('izzyEnv')) {
    throw new RuntimeException(
        'La configuración principal de IZZY debe cargarse antes de cocinaTokenConfig.php.'
    );
}

define(
    'IZZY_COCINA_TOKEN_CIPHER_KEY',
    izzyEnv('IZZY_COCINA_TOKEN_CIPHER_KEY')
);

/*
 * Tamaño utilizado por la generación de tokens.
 * No es una credencial y puede permanecer versionado.
 */
define('IZZY_COCINA_TOKEN_BYTES', 32);