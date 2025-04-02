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

	//OBTENEMOS LOS DATOS DEL ENCABEZADO DE LA FACTURA
	$result = $insMainModel->getCompra($noFactura);
	
	$anulada = '';
	$logotipo = '';
	$firma_documento = '';

	//OBTENEMOS LOS DATOS DEL DETALLE DE FACTURA
	$result_cotizacion_detalle = $insMainModel->getDetalleCompras($noFactura);								

	if($result->num_rows>0){
		$consulta_registro = $result->fetch_assoc();
		
		$logotipo = $consulta_registro['logotipo'];
		$firma_documento = $consulta_registro['firma_documento'];
		$no_factura = $consulta_registro['numero_factura'];

		if($consulta_registro['estado'] == 4){
			$anulada = '<img class="anulada" src="'.SERVERURL.'vistas/plantilla/img/anulado.png" alt="Anulada">';
		}

		ob_start();
		include(dirname('__FILE__').'/compra.php');
		$html = ob_get_clean();

		// Configurar Dompdf
		$options = new Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		// instantiate and use the dompdf class
		$dompdf = new Dompdf($options);
	
		$dompdf->loadHtml($html);
		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('letter', 'portrait');
		// Render the HTML as PDF
		$dompdf->render();
			
		// Output the generated PDF to Browser
		$dompdf->stream('compra_'.$no_factura.'.pdf',array('Attachment'=>0));	
					
		exit;	
	}