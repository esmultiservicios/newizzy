<?php
	header("Content-Type: text/html;charset=utf-8");
	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	include_once "dompdf/vendor/autoload.php";

	use Dompdf\Dompdf;
	use Dompdf\Options;

	$nomina_id = $_GET['nomina_id'];

	//OBTENEMOS LOS DATOS DEL ENCABEZADO DE LA FACTURA
	$result = $insMainModel->getNominaComprobante($nomina_id);
	
	$anulada = '';
	$logotipo = '';

	//OBTENEMOS LOS DATOS DEL DETALLE DE FACTURA
	$result_voucher_detalle = $insMainModel->getNominaComprobanteDetalles($nomina_id);								

	if($result->num_rows>0){
		$consulta_registro = $result->fetch_assoc();	
		
		$no_factura = $consulta_registro['nomina_id'];
		$logotipo = $consulta_registro['logotipo'];

		if($consulta_registro['estado'] == 2){
			$anulada = '<img class="anulada" src="'.SERVERURL.'vistas/plantilla/img/anulado.png" alt="Anulada">';
		}

		ob_start();
		include(dirname('__FILE__').'/plantilla_consolidado_nominas_legal.php');
		$html = ob_get_clean();

		// Configurar Dompdf
		$options = new Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		// instantiate and use the dompdf class
		$dompdf = new Dompdf($options);
		
		$dompdf->loadHtml($html);

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('legal', 'landscape');
		// Render the HTML as PDF
		$dompdf->render();
		
		file_put_contents(dirname('__FILE__').'/nomina/consolidadoNominas_'.$no_factura.'.pdf', $dompdf->output());
		
		// Output the generated PDF to Browser
		$dompdf->stream('consolidadoNominas_'.$no_factura.'.pdf',array('Attachment'=>0));
		
		exit;	
	}
?>