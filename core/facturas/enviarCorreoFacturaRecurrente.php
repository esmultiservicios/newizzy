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

    $sendEmail = new sendEmail();

    /* =========================================================
       1. ENVÍO AL CLIENTE
       El envío al cliente NUNCA debe impedir la notificación
       interna. Si no tiene correo o falla, se registra el estado
       y el proceso continúa.
       ========================================================= */

    $correoCliente = strtolower(trim((string)$factura['correo']));
    $clienteEnviado = false;
    $clienteEstado = 'sin_correo';
    $clienteDetalle = 'El cliente no tiene un correo electrónico válido registrado.';

    if (filter_var($correoCliente, FILTER_VALIDATE_EMAIL)) {
        $mensajeCliente = '<div style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#2d3748">'
            .'<p>¡Hola '.$nombreCliente.'!</p>'
            .'<p>Su factura recurrente <b>'.$numeroHtml.'</b> fue generada correctamente y ya está disponible.</p>'
            .'<div style="text-align:center;margin:25px 0">'
            .'<a href="'.$urlHtml.'" target="_blank" style="display:inline-block;background:#198754;color:#fff;padding:13px 24px;text-decoration:none;border-radius:8px">Ver factura</a>'
            .'</div><p>Gracias por su confianza.</p><p><b>El Equipo de '.$nombreEmpresa.'</b></p></div>';

        try {
            ob_start();
            $respuestaCliente = $sendEmail->enviarCorreo(
                [$correoCliente => (string)$factura['cliente']],
                [],
                'Factura recurrente '.$numeroDocumento,
                $mensajeCliente,
                3,
                $empresaId,
                [],
                'billing'
            );
            $salidaCliente = trim((string)ob_get_clean());

            if ((int)$respuestaCliente === 1) {
                $clienteEnviado = true;
                $clienteEstado = 'enviado';
                $clienteDetalle = 'Enviado correctamente a '.$correoCliente.'.';
            } else {
                $clienteEstado = 'error';
                $clienteDetalle = $salidaCliente !== ''
                    ? $salidaCliente
                    : 'El servicio de correo no confirmó el envío al cliente.';
            }
        } catch (Throwable $clienteError) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $clienteEstado = 'error';
            $clienteDetalle = $clienteError->getMessage();
        }
    }

    /* =========================================================
       2. DESTINATARIOS INTERNOS
       Esta notificación es OBLIGATORIA e independiente del
       resultado del correo del cliente.
       ========================================================= */

    $destinatariosInternos = [];
    $resultadoDestinatarios = $cn->query(
        "SELECT correo, nombre
         FROM notificaciones
         WHERE activo = 1
         ORDER BY nombre ASC, correo ASC"
    );

    if ($resultadoDestinatarios === false) {
        throw new Exception('No se pudieron consultar los destinatarios internos: '.$cn->error);
    }

    while ($destinatario = $resultadoDestinatarios->fetch_assoc()) {
        $correoInterno = strtolower(trim((string)$destinatario['correo']));

        if (!filter_var($correoInterno, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $nombreInterno = trim((string)$destinatario['nombre']);
        $destinatariosInternos[$correoInterno] = $nombreInterno !== ''
            ? $nombreInterno
            : $correoInterno;
    }

    $cantidadInternos = count($destinatariosInternos);

    if ($cantidadInternos <= 0) {
        throw new Exception('No hay destinatarios internos activos con un correo válido en la tabla notificaciones.');
    }

    $stmtDetalle = $cn->prepare(
        "SELECT COUNT(*) AS cantidad_productos
         FROM facturas_detalles
         WHERE facturas_id = ?"
    );

    if (!$stmtDetalle) {
        throw new Exception('No se pudo preparar el resumen interno: '.$cn->error);
    }

    $stmtDetalle->bind_param('i', $facturasId);
    $stmtDetalle->execute();
    $resultadoDetalle = $stmtDetalle->get_result();
    $resumenDetalle = $resultadoDetalle ? $resultadoDetalle->fetch_assoc() : null;
    $cantidadProductos = $resumenDetalle ? (int)$resumenDetalle['cantidad_productos'] : 0;
    $stmtDetalle->close();

    $tipoDocumento = ((int)$factura['es_proforma'] === 1)
        ? 'Factura proforma'
        : 'Factura normal';

    if ($clienteEstado === 'enviado') {
        $estadoClienteHtml = '<span style="color:#14804A;font-weight:700">Enviado correctamente a '.htmlspecialchars($correoCliente, ENT_QUOTES, 'UTF-8').'.</span>';
    } elseif ($clienteEstado === 'sin_correo') {
        $estadoClienteHtml = '<span style="color:#B26A00;font-weight:700">No enviado: el cliente no tiene un correo electrónico válido registrado.</span>';
    } else {
        $estadoClienteHtml = '<span style="color:#B42318;font-weight:700">No enviado: '.htmlspecialchars($clienteDetalle, ENT_QUOTES, 'UTF-8').'</span>';
    }

    $mensajeInterno = '<div style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#2d3748">'
        .'<h2 style="margin:0 0 8px;color:#1a365d">Factura recurrente generada</h2>'
        .'<p style="margin:0 0 16px;color:#4a5568">El proceso automático creó correctamente el siguiente documento:</p>'
        .'<table style="width:100%;border-collapse:collapse;background:#f7fafc;border-radius:8px">'
        .'<tr><td style="padding:8px"><b>Factura:</b></td><td style="padding:8px">'.$numeroHtml.'</td></tr>'
        .'<tr><td style="padding:8px"><b>Cliente:</b></td><td style="padding:8px">'.$nombreCliente.'</td></tr>'
        .'<tr><td style="padding:8px"><b>Total:</b></td><td style="padding:8px"><b>L. '.number_format((float)$factura['importe'], 2).'</b></td></tr>'
        .'<tr><td style="padding:8px"><b>Productos:</b></td><td style="padding:8px">'.$cantidadProductos.' producto(s)</td></tr>'
        .'<tr><td style="padding:8px"><b>Correo al cliente:</b></td><td style="padding:8px">'.$estadoClienteHtml.'</td></tr>'
        .'</table>'
        .'<p style="margin-top:16px;color:#718096">Aviso interno automático de IZZY.</p>'
        .'</div>';

    try {
        ob_start();
        $respuestaInterna = $sendEmail->enviarCorreo(
            $destinatariosInternos,
            [],
            'Resumen interno: '.$tipoDocumento.' '.$numeroDocumento.' generada',
            $mensajeInterno,
            3,
            $empresaId,
            [],
            'billing'
        );
        $salidaInterna = trim((string)ob_get_clean());
    } catch (Throwable $internoError) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        throw new Exception('La factura fue generada, pero falló la notificación interna: '.$internoError->getMessage());
    }

    if ((int)$respuestaInterna !== 1) {
        $detalleErrorInterno = $salidaInterna !== ''
            ? $salidaInterna
            : 'El servicio de correo no confirmó la notificación interna.';

        throw new Exception('La factura fue generada, pero falló la notificación interna: '.$detalleErrorInterno);
    }

    return [
        'cliente_enviado' => $clienteEnviado,
        'cliente_estado' => $clienteEstado,
        'cliente_detalle' => $clienteDetalle,
        'destinatarios_internos' => $cantidadInternos,
        'interno_enviado' => true
    ];
}
