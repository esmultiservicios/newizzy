<?php
	header("Content-Type: text/html;charset=utf-8");
	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	include_once "dompdf/vendor/autoload.php";

	use Dompdf\Dompdf;
	use Dompdf\Options;

	$noFactura = $_GET['facturas_id'];
	$formato = $_GET['formato'];  // Recibimos el formato (Carta, Ticket, Media Carta)

	//OBTENEMOS LOS DATOS DEL ENCABEZADO DE LA FACTURA
	$result = $insMainModel->getFactura($noFactura);	
	
	$anulada = '';
	$logotipo = '';
	$firma_documento = '';

	//OBTENEMOS LOS DATOS DEL DETALLE DE FACTURA
	$result_factura_detalle = $insMainModel->getDetalleFactura($noFactura);								

	if($result->num_rows>0){
		$consulta_registro = $result->fetch_assoc();	
		
		$logotipo = $consulta_registro['logotipo'];
		$firma_documento = $consulta_registro['firma_documento'];
		$no_factura = str_pad($consulta_registro['numero_factura'], $consulta_registro['relleno'], "0", STR_PAD_LEFT);

		if($consulta_registro['estado'] == 4){
			$anulada = '<img class="anulada" src="'.SERVERURL.'vistas/plantilla/img/anulado.png" alt="Anulada">';
		}

		ob_start();

		// Verificamos el formato y incluimos el archivo correspondiente
		if ($formato == 'Carta') {
			include(dirname('__FILE__') . '/plantilla_comprobante_entrega_carta.php');
			$formatoArchivo = 'carta';
		} elseif ($formato == 'Ticket') {
			include(dirname('__FILE__') . '/plantilla_comprobante_entrega_ticket.php');
			$formatoArchivo = 'ticket';
		} elseif ($formato == 'Media Carta') {
			include(dirname('__FILE__') . '/plantilla_comprobante_entrega_carta.php');
			$formatoArchivo = 'media_carta';
		}

		$html = ob_get_clean();

		// Configurar Dompdf
		$options = new Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		// Verificar el formato y configurar el tamaño del papel
		if ($formato == 'Ticket') {
			$options->set('margin-bottom', 0); // Establecer margen inferior
			$options->set('margin-left', 0);  // Establecer margen izquierdo

			$dompdf = new Dompdf($options);
			// Establecer tamaño personalizado para el ticket
			$dompdf->setPaper(array(0, 0, 210, 1000), 'portrait'); // Tamaño personalizado para el ticket
		} elseif ($formato == 'Media Carta') {
			$dompdf = new Dompdf($options);
			// Establecer tamaño para Media Carta: 5.5 x 8.5 pulgadas
			$dompdf->setPaper(array(0, 0, 612, 396), 'portrait'); // Media Carta: Ancho igual, alto dividido entre 2
		} else {
			// Configuración predeterminada para tamaño Carta
			$dompdf = new Dompdf($options);
			$dompdf->setPaper('letter', 'portrait');
		}

		$dompdf->loadHtml($html);
		// Renderizar el HTML como PDF
		$dompdf->render();

		// Concatenar el formato al nombre del archivo
		$dompdf->stream('comprobante_'.$no_factura.'_'.$consulta_registro['cliente'].'_'.$formatoArchivo.'.pdf', array('Attachment'=>0));

		exit;

	}