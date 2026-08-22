<?php
// Ubicación: core/facturas/enviarCorreoFacturaRecurrente.php

require_once dirname(__DIR__).'/mainModel.php';
require_once dirname(__DIR__).'/correo/sendEmail.php';

function enviarCorreoFacturaRecurrente($facturasId, $empresaId)
{
    $facturasId = (int)$facturasId;
    $empresaId = (int)$empresaId;
    if ($facturasId <= 0 || $empresaId <= 0) {
        throw new Exception('Factura o empresa inválida para enviar el correo.');
    }

    $mainModelCorreo = new mainModel();
    $cn = $mainModelCorreo->connection();
    if (!$cn) {
        throw new Exception('No se pudo establecer la conexión para enviar el correo recurrente.');
    }
    $stmt = $cn->prepare(
        "SELECT c.nombre AS cliente, c.correo, f.number AS numero,
                f.importe,
                sf.relleno, sf.prefijo, e.nombre AS empresa,
                CASE WHEN fp.facturas_id IS NULL THEN 0 ELSE 1 END AS es_proforma
         FROM facturas f
         INNER JOIN clientes c ON c.clientes_id = f.clientes_id
         INNER JOIN secuencia_facturacion sf ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
         INNER JOIN empresa e ON e.empresa_id = f.empresa_id
         LEFT JOIN facturas_proforma fp ON fp.facturas_id = f.facturas_id
         WHERE f.facturas_id = ? AND f.empresa_id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        throw new Exception('No se pudo preparar el correo recurrente: '.$cn->error);
    }
    $stmt->bind_param('ii', $facturasId, $empresaId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $factura = $resultado ? $resultado->fetch_assoc() : null;
    $stmt->close();

    if (!$factura) {
        throw new Exception('No se encontró la factura recurrente generada.');
    }

    $correo = trim((string)$factura['correo']);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El cliente no tiene un correo válido registrado.');
    }

    $relleno = max(1, (int)$factura['relleno']);
    $numeroDocumento = trim((string)$factura['prefijo'])
        .str_pad((string)$factura['numero'], $relleno, '0', STR_PAD_LEFT);

    $resultadoDb = $cn->query('SELECT DATABASE() AS db_actual');
    $dbActual = $resultadoDb ? (string)$resultadoDb->fetch_assoc()['db_actual'] : '';
    if ($dbActual === '') {
        throw new Exception('No se pudo identificar la base de datos para el enlace de la factura.');
    }

    $urlFactura = SERVERURLWINDOWS.'?'.http_build_query([
        'id' => $facturasId,
        'type' => 'Factura_carta_izzy',
        'db' => $dbActual,
        'demo_sistema' => 'NO'
    ]);

    $nombreCliente = htmlspecialchars((string)$factura['cliente'], ENT_QUOTES, 'UTF-8');
    $nombreEmpresa = htmlspecialchars(strtoupper((string)$factura['empresa']), ENT_QUOTES, 'UTF-8');
    $numeroHtml = htmlspecialchars($numeroDocumento, ENT_QUOTES, 'UTF-8');
    $urlHtml = htmlspecialchars($urlFactura, ENT_QUOTES, 'UTF-8');

    $mensaje = '<div style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#2d3748">'
        .'<p>¡Hola '.$nombreCliente.'!</p>'
        .'<p>Su factura recurrente <b>'.$numeroHtml.'</b> fue generada correctamente y ya está disponible.</p>'
        .'<div style="text-align:center;margin:25px 0">'
        .'<a href="'.$urlHtml.'" target="_blank" style="display:inline-block;background:#198754;color:#fff;padding:13px 24px;text-decoration:none;border-radius:8px">Ver factura</a>'
        .'</div><p>Gracias por su confianza.</p><p><b>El Equipo de '.$nombreEmpresa.'</b></p></div>';

    $sendEmail = new sendEmail();
    $respuestaCliente = $sendEmail->enviarCorreo(
        [$correo => (string)$factura['cliente']],
        [],
        'Factura recurrente '.$numeroDocumento,
        $mensaje,
        3,
        $empresaId,
        []
    );

    if ($respuestaCliente === false || $respuestaCliente === null || $respuestaCliente === '') {
        throw new Exception('El servicio de correo no confirmó el envío al cliente.');
    }

    /*
     * El correo administrativo es independiente del enviado al cliente.
     * Solo utiliza destinatarios activos configurados en Configurar Correos.
     */
    $destinatariosInternos = [];
    $resultadoDestinatarios = $cn->query(
        "SELECT correo, nombre
         FROM notificaciones
         WHERE activo = 1
         ORDER BY nombre ASC, correo ASC"
    );
    if ($resultadoDestinatarios === false) {
        throw new Exception('La factura fue enviada al cliente, pero no se pudieron consultar los destinatarios internos: '.$cn->error);
    }

    while ($destinatario = $resultadoDestinatarios->fetch_assoc()) {
        $correoInterno = strtolower(trim((string)$destinatario['correo']));
        if (!filter_var($correoInterno, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        $nombreInterno = trim((string)$destinatario['nombre']);
        $destinatariosInternos[$correoInterno] = $nombreInterno !== '' ? $nombreInterno : $correoInterno;
    }

    $cantidadInternos = count($destinatariosInternos);
    if ($cantidadInternos > 0) {
        $stmtDetalle = $cn->prepare(
            "SELECT COUNT(*) AS cantidad_productos
             FROM facturas_detalles
             WHERE facturas_id = ?"
        );
        if (!$stmtDetalle) {
            throw new Exception('La factura fue enviada al cliente, pero no se pudo preparar su resumen interno: '.$cn->error);
        }
        $stmtDetalle->bind_param('i', $facturasId);
        $stmtDetalle->execute();
        $resultadoDetalle = $stmtDetalle->get_result();
        $resumenDetalle = $resultadoDetalle ? $resultadoDetalle->fetch_assoc() : null;
        $cantidadProductos = $resumenDetalle ? (int)$resumenDetalle['cantidad_productos'] : 0;
        $stmtDetalle->close();

        $tipoDocumento = ((int)$factura['es_proforma'] === 1) ? 'Factura proforma' : 'Factura normal';
        $mensajeInterno = '<div style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#2d3748">'
            .'<h2 style="margin:0 0 8px;color:#1a365d">Factura recurrente generada</h2>'
            .'<p style="margin:0 0 16px;color:#4a5568">El proceso automático creó correctamente el siguiente documento:</p>'
            .'<table style="width:100%;border-collapse:collapse;background:#f7fafc;border-radius:8px">'
            .'<tr><td style="padding:8px"><b>Factura:</b></td><td style="padding:8px">'.$numeroHtml.'</td></tr>'
            .'<tr><td style="padding:8px"><b>Cliente:</b></td><td style="padding:8px">'.$nombreCliente.'</td></tr>'
            .'<tr><td style="padding:8px"><b>Total:</b></td><td style="padding:8px"><b>L. '.number_format((float)$factura['importe'], 2).'</b></td></tr>'
            .'<tr><td style="padding:8px"><b>Productos:</b></td><td style="padding:8px">'.$cantidadProductos.' producto(s)</td></tr>'
            .'</table>'
            .'<p style="margin-top:16px;color:#718096">Aviso interno automático. El cliente recibió su factura por separado.</p>'
            .'</div>';

        $respuestaInterna = $sendEmail->enviarCorreo(
            $destinatariosInternos,
            [],
            'Resumen interno: '.$tipoDocumento.' '.$numeroDocumento.' generada',
            $mensajeInterno,
            3,
            $empresaId,
            []
        );
        if ($respuestaInterna === false || $respuestaInterna === null || $respuestaInterna === '') {
            throw new Exception('La factura fue enviada al cliente, pero el servicio no confirmó la notificación interna.');
        }
    }

    return [
        'cliente_enviado' => true,
        'destinatarios_internos' => $cantidadInternos
    ];
}