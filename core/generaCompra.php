<?php
header("Content-Type: text/html;charset=utf-8");

$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

include_once "dompdf/vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$noFactura = $_GET['compras_id'];

// OBTENEMOS TODOS LOS DATOS DE LA COMPRA (ENCABEZADO Y DETALLE)
$compra = $insMainModel->getCompraUnificada($noFactura);

if($compra){
    $anulada = '';
    $logotipo = '';
    $firma_documento = '';
    
    $logotipo = $compra['logotipo'];
    $firma_documento = $compra['firma_documento'];
    $no_factura = $compra['numero_factura'];

    if($compra['estado'] == 4){
        $anulada = '<img class="anulada" src="'.SERVERURL.'vistas/plantilla/img/anulado.png" alt="Anulada">';
    }

    ob_start();
    include(dirname('__FILE__').'/compra.php');
    $html = ob_get_clean();

    // Configurar Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
        
    $dompdf->stream('compra_'.$no_factura.'.pdf', array('Attachment'=>0));    
    exit;
}