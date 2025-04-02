<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

// Convertir el valor del mes a un entero
$mes = (int)date('m');  // Convierte "01", "02", ... a 1, 2, ...

echo $insMainModel->nombremes($mes);