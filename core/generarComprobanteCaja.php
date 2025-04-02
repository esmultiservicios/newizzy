<?php
	header("Content-Type: text/html;charset=utf-8");
	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	include_once "dompdf/vendor/autoload.php";

	use Dompdf\Dompdf;
	use Dompdf\Options;

	$apertura_id = $_GET['apertura_id'];

	//OBTENEMOS LOS DATOS DEL ENCABEZADO DE LA FACTURA
	$result = $insMainModel->getComprobanteCaja($apertura_id);	
	$resultCaja = $insMainModel->getComprobanteCaja($apertura_id);
	$resulFacturasCaja = $insMainModel->getFacturasCaja($apertura_id);	
	
	$anulada = '';
	$logotipo = '';
	$firma_documento = '';	
	$empresa_id = '';
	$montoApertura = 0;	
	$saldoCredito = 0;						

	if($result->num_rows>0){
		$consulta_registro = $result->fetch_assoc();

		$empresa_id = $consulta_registro['empresa_id'];

		//CONSULTAMOS EL MONTO DE LA APERTURA

		$empresa_id = $consulta_registro['empresa_id'];

		//CONSULTAMOS EL MONTO DE APERTURA
		$resultAperturaCaja = $insMainModel->getMontoAperturaCaja($apertura_id);
		$consulta_AperturaCaja = $resultAperturaCaja->fetch_assoc();
		$montoApertura = $consulta_AperturaCaja['apertura'];

		$resultMostrarDetalleFactura = $insMainModel->getAcciones("Mostrar detalle facturas - Caja");
		$consulta_MostrarDetalleFactura = $resultMostrarDetalleFactura->fetch_assoc();
		$activar = $consulta_MostrarDetalleFactura['activar'];
		
		//CONSULTAMOS EL TOTAL DE FACTURAS AL CREDITO

		//OBTENEMOS LOS DATOS DEL LA EMPRESA
		$result_empesa_caja = $insMainModel->getEmpresaConsulta($empresa_id);
		$consulta_empreasa_caja = $result_empesa_caja->fetch_assoc();

		ob_start();
		include(dirname('__FILE__').'/plantilla_comprobante_arqueo_caja_ticket.php');
		$html = ob_get_clean();

		// Configurar Dompdf
		$options = new Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);
		$options->set('margin-bottom', 0); // Establecer margen inferior
		$options->set('margin-left', 0);  // Establecer margen izquierdo

		// instantiate and use the dompdf class
		$dompdf = new Dompdf();

		$dompdf->loadHtml($html);
		
		$dompdf->setPaper(array(0, 0, 210, 300), 'portrait');
		
		// Render the HTML as PDF
		$dompdf->render();
			
		// Output the generated PDF to Browser
		$dompdf->stream('cierreCaja_'.$apertura_id.'.pdf',array('Attachment'=>0));
		
		exit;	
	}