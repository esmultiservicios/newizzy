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
    <div id="page_pdf">
        <table id="factura_head">
            <tr>
                <td class="textcenter">
                    <span class="h2"><?php echo nl2br($consulta_registro['empresa']); ?></span>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p><?php echo nl2br($consulta_registro['direccion_empresa']); ?></p>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p><?php echo nl2br($consulta_registro['otra_informacion']); ?></p>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p>PBX: <?php echo $consulta_registro['empresa_telefono']; ?></p>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p>WhatsApp: <?php echo $consulta_registro['empresa_celular']; ?></p>
                </td>
            </tr>

            <tr>
                <td class="textcenter">
                    <p>Correo: <?php echo $consulta_registro['empresa_correo']; ?></p>
                </td>
            </tr>
            <tr>
                <td colspan="4"><span>&nbsp;&nbsp;&nbsp;</span></td>
                <td colspan="3"><span>&nbsp;&nbsp;&nbsp;</span></td>
            </tr>
            <tr class="">
                <td class="textcenter">
                    <span>
                        <h2>Comprobante de Entrega</h2>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p><b>N° Comprobante:</b>
                        <?php echo $consulta_registro['prefijo'].''.str_pad($consulta_registro['numero_factura'], $consulta_registro['relleno'], "0", STR_PAD_LEFT);?>CBE
                    </p>
                </td>
            </tr>
            <tr>
                <td class="textcenter">
                    <p><b>Fecha:</b>
                        <b><?php echo $consulta_registro['fecha'].' '.date('g:i a',strtotime($consulta_registro['hora'])); ?></b>
                    </p>
                </td>
            </tr>
        </table>
        <table id="factura_cliente">
            <tr>
                <td class="info_cliente">
                    <div class="">
                        <table class="datos_cliente">
                            <tr>
                                <td><label>RTN:</label>
                                    <p><?php 
									if(strlen($consulta_registro['rtn_cliente'])<10){
										echo "";
									}else{
										echo $consulta_registro['rtn_cliente'];
									}
							
							?></p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><label>Teléfono:</label>
                                    <p><?php echo $consulta_registro['telefono']; ?></p>
                                </td>
                            </tr>
                            <tr class="datos-cliente">
                                <td colspan="2"><label>Cliente:</label>
                                    <p><?php echo $consulta_registro['cliente']; ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>

            </tr>
        </table>

        <table>
            <thead class="bordertr">
                <tr>
                    <th width="" class="textleft">Cantidad</th>
                    <th width="" class="textright">Precio</th>
                    <th width="" class="textright">Descuento</th>
                    <th width="" class="textright">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php
					$total_despues_isv = 0;
					$importe_gravado = 0;
					$importe_excento = 0;
					$subtotal = 0;
					$isv_neto = 0;
					$descuentos_neto = 0;
					$total = 0;
					$i = 1;
					$totalHNL = 0;
					$tasaCambioHNL = 0;
					$descuentos = 0;
					$producto_name = '';
					
					while($registro_detalles = $result_factura_detalle->fetch_assoc()){																
						$total_ = 0;
						$importe = 0;

						$total += ($registro_detalles["precio"] * $registro_detalles["cantidad"]);
						$total_ = ($registro_detalles["precio"] * $registro_detalles["cantidad"]);
						$importe += ($registro_detalles["precio"] * $registro_detalles["cantidad"] - $registro_detalles["descuento"]);
						$subtotal += $importe;
						$descuentos += $registro_detalles["descuento"];
						$descuentos_neto += $descuentos;
						$isv_neto += $registro_detalles["isv_valor"];
						
						if($registro_detalles["isv_valor"] > 0){
							$importe_gravado += ($registro_detalles["precio"] * $registro_detalles["cantidad"] - $registro_detalles["descuento"]);
						}else{
							$importe_excento += ($registro_detalles["precio"] * $registro_detalles["cantidad"] - $registro_detalles["descuento"]);
						}					

						$producto_name = $registro_detalles["producto"];

						echo
                        '<tr>
                        <td colspan="4" class="nombre-producto">'.$producto_name.'</td>
                        </tr>
                        <tr>
                        <td align="center">'.$registro_detalles["cantidad"].'</td>
                        <td class="textright">L. '.number_format($registro_detalles["precio"],2).'</td>
                        <td class="textright">L. '.number_format($descuentos,2).'</td>
                        <td class="textright">L. '.number_format($importe,2).'</td>
                        </tr>';
						$i++;
					}

					$total_despues_isv = ($total + $isv_neto) - $descuentos_neto;				
				?>
            </tbody>
            <tfoot id="detalle_totales">
                <tr>
                    <td colspan="3"><span>&nbsp;&nbsp;&nbsp;</span></td>
                    <td><span>&nbsp;&nbsp;&nbsp;</span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Importe</span></td>
                    <td><span>L. <?php echo number_format($total,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Descuentos y Rebajas Otorgados</span></td>
                    <td><span>L. <?php echo number_format($descuentos_neto,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Sub-Total</span></td>
                    <td><span>L. <?php echo number_format($subtotal,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Importe Exonerado</span></td>
                    <td><span>L. <?php echo number_format(0,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Importe Excento</span></td>
                    <td><span>L. <?php echo number_format($importe_excento,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Importe Gravado 15%</span></td>
                    <td><span>L. <?php echo number_format($importe_gravado,2); ?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Importe Gravado 18%</span></td>
                    <td><span>L. <?php echo number_format(0,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>ISV 15%</span></td>
                    <td><span>L. <?php echo number_format($isv_neto,2); ?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>ISV 18%</span></td>
                    <td><span>L. <?php echo number_format(0,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="3"><span>Total</span></td>
                    <td><span>L. <?php echo number_format($total_despues_isv,2); ?></span></td>
                </tr>
            </tfoot>
        </table>
        <div>
            <p class="nota" style="word-wrap: break-word;"><?php 
			if($consulta_registro["notas"] != ""){
				echo "<p class='h2'><b>Nota:</b> ".nl2br($consulta_registro["notas"])."</p>";
			}		
		?></p>
            <p class="nota"><br /><br /></p>
            <p class="nota" style="word-wrap: break-word;">
                <center><?php echo $insMainModel->convertir($total_despues_isv).' LEMPIRAS';?></center>
            </p>

            <table style="width:100%; padding-top: 2rem; padding-bottom: 2rem;">
                <tr style="text-align: center;">
                    <td>Entregado por:</td>
                    <td>______________________________________</td>
                </tr>
                <tr>
                    <td style="width:100%; padding-top: 6rem;"></td>
                </tr>
                <tr style="text-align: center;">
                    <td>Recibido por:</td>
                    <td>______________________________________</td>
                </tr>
            </table>

            <p class="nota">
                <center><b>Original:</b> Cliente</center>
            </p>
            <p class="nota">
                <center><b>Copia:</b> Emisor</center>
            </p>
            <h4 class="label_gracias" style="word-wrap: break-word;">
                <?php  echo nl2br($consulta_registro["eslogan"]); ?></h4>
        </div>
    </div>
</body>

</html>