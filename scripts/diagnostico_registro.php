<?php
/** Diagnóstico local SIN conexión a cPanel, sin escribir bases ni enviar correos. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo CLI'); }
$root=dirname(__DIR__);
$checks=[
    'PHP >= 8.2'=>version_compare(PHP_VERSION,'8.2','>='),
    'Extensión mysqli'=>extension_loaded('mysqli'),
    'Extensión curl'=>extension_loaded('curl'),
    'Clase cPanelAPI'=>is_file($root.'/core/registro/cPanelAPI.php'),
    'Clase DatabaseSetup'=>is_file($root.'/core/registro/DatabaseSetup.php'),
    'Clase Database'=>is_file($root.'/core/registro/Database.php'),
    'Sincronización cliente'=>is_file($root.'/core/registro/ProvisioningSync.php'),
    'Archivo plantilla'=>is_file($root.'/plantilla/plantilla_izzy.sql'),
];
$mysql='';
if (function_exists('shell_exec')) $mysql=trim((string)@shell_exec('mysql --version 2>&1'));
$checks['Cliente mysql CLI instalado'] = $mysql!=='' && stripos($mysql,'mysql')!==false;
foreach ($checks as $name=>$ok) echo ($ok?'[OK]  ':'[FALTA] ').$name.PHP_EOL;
echo PHP_EOL.'No se ha creado ni modificado ninguna base de datos.'.PHP_EOL;
exit(in_array(false,$checks,true)?1:0);
