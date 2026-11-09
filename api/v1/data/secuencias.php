<?php
/** IZZY API v1: secuencias fiscales autorizadas. Solo consulta; no reserva correlativos. */
declare(strict_types=1);
require_once dirname(__DIR__,3).'/core/api/ApiBusinessEndpoint.php';
izzyEjecutarCatalogo('secuencias');
