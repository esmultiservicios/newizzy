<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("America/Tegucigalpa");

$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";
require_once "sendEmail.php";

$sendEmail = new sendEmail();
$insMainModel = new mainModel();

//CONSULTAMOS LA DB DE LOS CLIENTES
$resultDbClientes = $insMainModel->consultarDBClientes();

// Verifica si hay resultados en la consulta de clientes
if($resultDbClientes->num_rows>0){
    // Itera sobre los resultados de clientes
    while($consulta2 = $resultDbClientes->fetch_assoc()){
        // Obtiene el nombre de la base de datos del cliente
        $db = $consulta2['db'];
        
        //NOS CONECTAMOS A LA DB DEL CLIENTE PARA CONSULTAR
        $mysqli = $insMainModel->connectionDBLocal($db);

        // QUERY PARA OBTENER FACTURAS PENDIENTES
        $query = "SELECT c.compras_id, c.importe, c.tipo_compra, c.recordatorio,
            COALESCE(SUM(pc.importe), 0) AS total_pagado, 
            COALESCE(c.importe - SUM(pc.importe), c.importe) AS saldo_pendiente, e.empresa_id, e.nombre AS empresa, e.correo, p.nombre AS proveedor, c.number
        FROM compras AS c
        LEFT JOIN pagoscompras pc ON c.compras_id = pc.compras_id
        INNER JOIN empresa AS e ON c.empresa_id = e.empresa_id
        INNER JOIN proveedores AS p ON c.proveedores_id = p.proveedores_id
        WHERE c.tipo_compra = 2 -- 2 indica compra a crédito
            AND c.recordatorio IS NOT NULL 
            AND c.fecha < CURDATE()
        GROUP BY c.compras_id, c.importe, c.tipo_compra, c.recordatorio;";
			
         // Ejecuta la consulta o muestra un mensaje de error
		$resultPendienteCompras = $mysqli->query($query) or die($mysqli->error);

        // Verifica si hay facturas pendientes
        if($resultPendienteCompras->num_rows>0){
            // Itera sobre los resultados de facturas pendientes
            while($consulta2Pendientes = $resultPendienteCompras->fetch_assoc()){
                $compras_id = $consulta2Pendientes['compras_id'];
                $importe = $consulta2Pendientes['importe'];
                $recordatorio = $consulta2Pendientes['recordatorio'];
                $total_pagado = $consulta2Pendientes['total_pagado'];
                $saldoPendiente = $consulta2Pendientes['saldo_pendiente'];
                $nombreEmpresa = $consulta2Pendientes['empresa'];
                $correo_usuario = $consulta2Pendientes['correo'];
                $nombreProveedor = $consulta2Pendientes['proveedor'];
                $numeroFactura = $consulta2Pendientes['number'];
                $empresa_id = $consulta2Pendientes['empresa_id'];

                // Verifica si el saldo pendiente es mayor que cero
                if($saldoPendiente > 0){
                    //CONSULTAR CORREOS PARA ENVIAR LA NOTIFICACIONES
                    $query_correos = "SELECT correo, nombre
                        FROM notificaciones;";
			
                    // Ejecuta la consulta o muestra un mensaje de error
                    $resultCorreos = $mysqli->query($query_correos) or die($mysqli->error);

                    $bccDestinatarios = []; 
                    // Verifica si hay resultados antes de procesar
                    if ($resultCorreos->num_rows > 0) {
                        // Recorre los resultados y agrega cada correo al array
                        while ($row = $resultCorreos->fetch_assoc()) {
                            $bccDestinatarios[$row['correo']] = $row['nombre'];
                        }
                    }

                    // Configura destinatarios y asunto del correo
                    $destinatarios = array($correo_usuario => $nombreEmpresa);
   
                    $asunto = "Recordatorio de Pago - Factura #{$numeroFactura}";

                    // Construye el mensaje del correo
                    $mensaje = '
                        <div style="padding: 20px;">
                            <p style="margin-bottom: 10px;">
                            ¡Estimado Cliente, '.$nombreEmpresa.'!
                            </p>
                            
                            <p style="margin-bottom: 10px;">
                                Este es un recordatorio amistoso sobre la factura pendiente con el número #'.$numeroFactura.'. Aún tiene un saldo pendiente de L'.number_format($saldoPendiente, 2, '.', ',').' por pagar al proveedor '.$nombreProveedor.'. Por favor, realice el pago a la brevedad posible para evitar posibles inconvenientes.
                            </p>							
                            
                            <ul style="margin-bottom: 12px;">
                                <li><b>Número de Factura</b>: #'.$numeroFactura.'</li>
                                <li><b>Saldo Pendiente</b>: L.'.number_format($saldoPendiente, 2, '.', ',').'</li>
                                <li><b>Proveedor</b>: '.$nombreProveedor.'</li>
                            </ul>
                            
                            <p style="margin-bottom: 10px;">
                                Le agradecemos por tu pronta atención a este asunto y quedamos a su disposición para cualquier pregunta o asistencia adicional que pueda necesitar.
                            </p>
                            
                            <p style="margin-bottom: 10px;">
                                ¡Gracias por confiar en nosotros y por su preferencia!
                            </p>
                            
                            <p style="margin-bottom: 10px;">
                                Saludos cordiales,
                            </p>
                            
                            <p>
                                <b>El Equipo de '.$nombreEmpresa.'</b>
                            </p>                
                        </div>
                    ';
    
                    // Tipo de correo para notificaciones
                    $correo_tipo_id = "1";//Notificaciones
                        
                    // Archivos adjuntos (vacío en este caso)
                    $archivos_adjuntos = [];
                    
                    // Envía el correo
                    $sendEmail->enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa_id, $archivos_adjuntos);
                }                
            }
        }
   }
}
?>