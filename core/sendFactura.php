<?php
	header("Content-Type: text/html;charset=utf-8");
	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	require_once "Database.php";
	require_once "sendEmail.php";
	
	// Instanciar mainModel
	$insMainModel = new mainModel();

	// Validar sesión primero
	$validacion = $insMainModel->validarSesion();
	if($validacion['error']) {
		return $insMainModel->showNotification([
			"title" => "Error de sesión",
			"text" => $validacion['mensaje'],
			"type" => "error",
			"funcion" => "window.location.href = '".$validacion['redireccion']."'"
		]);
	}


	$database = new Database();
	$sendEmail = new sendEmail();

	date_default_timezone_set('America/Tegucigalpa');
	$facturas_id = $_POST['facturas_id'];

	//CONSULTAR DATOS DE FACTURA
	$result_factura = $insMainModel->geFacturaCorreo($facturas_id);	

	$nombre = "";
	$para = "";
	$no_factura = "";
	$prefijo = "";
	 
	if($result_factura->num_rows>=0){
		$factura = $result_factura->fetch_assoc();
		$nombre = $factura['cliente'];
		$para = $factura['correo'];
		$no_factura = str_pad($factura['numero'], $factura['relleno'], "0", STR_PAD_LEFT);
		$prefijo = $factura['prefijo'];
	}

	$numero_documento = $prefijo.$no_factura;

	$users_id = $_SESSION['users_id_sd'];
	$empresa_id = $_SESSION['empresa_id_sd'];

	//OBTENEMOS EL NOMBRE DE LA EMPRESA
	$tablaEmpresa = "empresa";
	$camposEmpresa = ["nombre"];
	$condicionesEmpresa = ["empresa_id" => $empresa_id];
	$orderBy = "";
	$tablaJoin = "";
	$condicionesJoin = [];
	$resultadoEmpresa = $database->consultarTabla($tablaEmpresa, $camposEmpresa, $condicionesEmpresa, $orderBy, $tablaJoin, $condicionesJoin);

	$empresa_nombre = "";

	if (!empty($resultadoEmpresa)) {
		$empresa_nombre = strtoupper(trim($resultadoEmpresa[0]['nombre']));
	}

	$urlFactura = SERVERURL.'core/generaFactura.php?facturas_id='.$facturas_id;
	$factura_documento = "factura_".$no_factura;
	$URL = dirname('__FILE__').'/facturas/'.$factura_documento.'.pdf';	

	$correo_tipo_id = "3";//Facturas
	$destinatarios = array($para => $nombre);

	// Destinatarios en copia oculta (Bcc)
	$bccDestinatarios = [];

	$asunto = "Envío de Factura ".$numero_documento;
	$mensaje = '
		<div style="padding: 20px;">
			<p style="margin-bottom: 10px;">
				¡Hola '.$nombre.'!
			</p>
			
			<p style="margin-bottom: 10px;">
				Espero que esté teniendo un día excelente. Queremos comunicarle que hemos procedido a enviarle su factura <b>'.$numero_documento.'</b>. Esta factura contiene los detalles de su reciente compra y estamos seguros de que será de su interés.
			</p>								
			
			<p style="margin-bottom: 10px;">
				Para revisar minuciosamente los detalles de la factura, le instamos a descargar el archivo adjunto a este correo electrónico. Además, para mayor comodidad, puede hacer uso del siguiente enlace para verificar su factura: <a href='.$urlFactura.'>Mi Factura '.$numero_documento.'<a>.
			</p>
			
			<p style="margin-bottom: 10px;">
				Si requiere aclaraciones adicionales respecto a la factura o cualquier otra consulta, no dude en contactarnos. Estamos aquí para garantizar su completa satisfacción.
			</p>
			
			<p style="margin-bottom: 10px;">
				Agradecemos enormemente su continua confianza en '.$empresa_nombre.'. Esperamos seguir siendo su elección preferida en futuras oportunidades.
			</p>
			
			<p style="margin-bottom: 10px;">
				Saludos cordiales,
			</p>
			
			<p>
				<b>El Equipo de '.$empresa_nombre.'</b>
			</p>                
		</div>
	';

	$archivos_adjuntos = [$URL];
	
	echo $sendEmail->enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $users_id, $archivos_adjuntos);	