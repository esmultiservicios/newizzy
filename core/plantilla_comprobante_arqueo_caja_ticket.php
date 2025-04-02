<?php
   require_once "mainModel.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Factura</title>
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/style_factura.css">
    <link rel="shortcut icon" href="<?php echo SERVERURL; ?>vistas/plantilla/img/icono.png">

    <style>
    .bordertr th {
        border: 1px solid #000;
        /* Establece un borde de 1 píxel de ancho con color negro para las celdas th */
        padding: 8px;
        /* Añade un espacio interno alrededor del contenido */
        text-align: left;
        /* Alinea el texto a la izquierda */
    }

    body {
        margin: 0;
        padding: 0;
        border: 0;
    }

    p {
        margin: 0;
    }

    .nota {
        margin: 0;
    }

    div {
        margin: 0;
    }

    .datos-cliente p {
        overflow-wrap: break-word;
        word-wrap: break-word;
        white-space: pre-line;
        /* Esta propiedad puede ayudar a mantener los saltos de línea */
    }

    #detalle_totales {
        border-top: 1px solid black;
    }
    </style>
</head>

<body>
    <?php echo $anulada; ?>
    <?php
  if (SISTEMA_PRUEBA=="SI"){ //CAJA
?>
    <span class="container-fluid prueba-sistema">SISTEMA DE PRUEBA</span>
    <?php
  }
?>
    <div id="page_pdf">
        <table id="factura_head">
            <tr>
                <td class="textcenter">
                    <span class="h1"><?php echo nl2br($consulta_empreasa_caja['nombre']); ?></span>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p><?php echo nl2br($consulta_empreasa_caja['ubicacion']); ?></p>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p><?php echo nl2br($consulta_empreasa_caja['otra_informacion']); ?></p>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p>PBX: <?php echo $consulta_empreasa_caja['telefono']; ?></p>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p>WhatsApp: <?php echo $consulta_empreasa_caja['celular']; ?></p>
                </td>
            </tr>

            <tr>
                <td class="textcenter">
                    <p>Correo: <?php echo $consulta_empreasa_caja['correo']; ?></p>
                </td>
            </tr>
            <tr>
                <td colspan="4"><span>&nbsp;&nbsp;&nbsp;</span></td>
                <td colspan="3"><span>&nbsp;&nbsp;&nbsp;</span></td>
            </tr>
            <tr class="">
                <td class="textcenter">
                    <span>
                        <h2>Cierre de Caja</h2>
                    </span>
                </td>
            </tr>
        </table>

        <table>
            <tbody>
                <?php		
                    $total = 0;	
                    echo                      
                    '   <tr>
                            <td colspan="2"><center><b>Contado</b></center></td>
                        </tr>                    
                    '; 
                    
                    while ($consulta_registro2 = $resultCaja->fetch_assoc()) {
                        echo
                        '   <tr>
                                <td>'.$consulta_registro2["tipo_pago_nombre"].' </td>
                                <td>L. '.number_format(floor($consulta_registro2["total_efectivo"] * 100) / 100, 2).'</td>
                            </tr>';

                            $total += $consulta_registro2["total_efectivo"];
                    }
                    
                    $total += $montoApertura;
                                       
                   echo                      
                    '   <tr>
                            <td>Apertura </td>
                            <td>L. '.number_format($montoApertura,2).'</td>
                        </tr>                    
                        <tr>
                            <td><b>Total: </b></td>
                            <td>L. '.number_format(floor($total * 100) / 100, 2).'</td>
                        </tr>
                    ';

                    echo                      
                    '   
                        <tr>
                            <td>&nbsp;&nbsp;&nbsp; </td>
                            <td>&nbsp;&nbsp;&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="2"><center><b>Crédito</b></center></td>
                        </tr>                    
                        <tr>
                            <td><b>Total: </b></td>
                            <td>L. '.number_format(floor($saldoCredito * 100) / 100, 2).'</td>
                        </tr>                                                 
                    ';

                    if($activar === "1"){
                        echo                      
                        '   
                            <tr>
                                <td>&nbsp;&nbsp;&nbsp; </td>
                                <td>&nbsp;&nbsp;&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="2"><center><b>Detalle de Facturas</b></center></td>
                            </tr> 
                            <tr>
                                <td><b>Factura</b></td>
                                <td><b>Importe</b></td>
                            </tr>                                                 
                        ';
    
                        while ($consulta_registro2 = $resulFacturasCaja->fetch_assoc()) {
                            $no_factura = $consulta_registro2['prefijo'].str_pad($consulta_registro2['number'], $consulta_registro2['relleno'], "0", STR_PAD_LEFT);
    
                            echo
                            '   <tr>
                                    <td>'.$no_factura.' </td>
                                    <td>L. '.number_format(floor($consulta_registro2["importe"] * 100) / 100, 2).'</td>                            
                                </tr>                            
                                ';
                        }
                    }
				?>
            </tbody>
        </table>
    </div>
</body>

</html>