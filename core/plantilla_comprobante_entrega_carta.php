<?php
   require_once "mainModel.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>comprobante</title>
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/style_factura_carta.css">
    <link rel="shortcut icon" href="<?php echo SERVERURL; ?>vistas/plantilla/img/icono.png">
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
                <td class="logo_factura">
                    <div>
                        <img src="<?php echo SERVERURL; ?>vistas/plantilla/img/logos/<?php echo $logotipo; ?>"
                            width="150px" height="95px">
                    </div>
                </td>
                <td class="info_empresa">
                    <div>
                        <span class="h2"><?php echo $consulta_registro['empresa']; ?></span>
                        <p><?php echo nl2br($consulta_registro['direccion_empresa']); ?></p>
                        <p>PBX: <?php echo $consulta_registro['empresa_telefono']; ?></p>
                        <p>WhatsApp: <?php echo $consulta_registro['empresa_celular']; ?></p>
                        <p><?php echo nl2br($consulta_registro['otra_informacion']); ?></p>
                        <p>Correo: <?php echo $consulta_registro['empresa_correo']; ?></p>
                    </div>
                </td>
                <td class="info_factura">
                    <div class="round">
                        <span class="h3">Comprobante de Entrega</span>
                        <p><b>N° Comprobante:</b>
                            <?php echo $consulta_registro['prefijo'].''.str_pad($consulta_registro['numero_factura'], $consulta_registro['relleno'], "0", STR_PAD_LEFT);?>CBE
                        </p>
                        <p><b>Fecha de Entrega:</b>
                            <?php echo $consulta_registro['fecha'].' '.date('g:i a',strtotime($consulta_registro['hora'])); ?>
                        </p>

                    </div>
                </td>
            </tr>
        </table>
        <table id="factura_cliente">
            <tr>
                <td class="info_cliente">
                    <div class="round">
                        <span class="h3">Cliente</span>
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
                                <td><label>Teléfono:</label>
                                    <p><?php echo $consulta_registro['telefono']; ?></p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><label>Cliente:</label>
                                    <p><?php echo $consulta_registro['cliente']; ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>

            </tr>
        </table>
        <table id="factura_detalle">
            <thead>
                <tr>
                    <th width="2.66%">N°</th>
                    <th width="40.66%">Nombre Producto</th>
                    <th width="6.66%" class="textleft">Cantidad</th>
                    <th width="6%" class="textleft">Medida</th>
                    <th width="16.66%" class="textright">Precio</th>
                    <th width="16.66%" class="textright">Descuento</th>
                    <th width="16.66%" class="textright">Importe</th>
                </tr>
            </thead>
            <tbody id="detalle_productos">
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
						
						if($registro_detalles["barCode"] != "" || $registro_detalles["barCode"] != null){
							$producto_name = '['.$registro_detalles["barCode"].'] '.$registro_detalles["producto"];
						}else{
							$producto_name = $registro_detalles["producto"];
						}

						echo '
						  <tr>
							<td>'.$i.'</td>
							<td>'.$producto_name.'</td>
							<td align="center">'.$registro_detalles["cantidad"].'</td>
							<td align="center">'.$registro_detalles["medida"].'</td>
							<td class="textright">L. '.number_format($registro_detalles["precio"],2).'</td>
							<td class="textright">L. '.number_format($descuentos,2).'</td>
							<td class="textright">L. '.number_format($importe,2).'</td>
						  </tr>
						';
						$i++;
					}

					$total_despues_isv = ($total + $isv_neto) - $descuentos_neto;				
				?>
            </tbody>
            <tfoot id="detalle_totales">
                <tr>
                    <td colspan="6" class="textright"><span>&nbsp;</span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Importe</span></td>
                    <td class="textright"><span>L. <?php echo number_format($total,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Descuentos y Rebajas Otorgados</span></td>
                    <td class="textright"><span>L. <?php echo number_format($descuentos_neto,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Sub-Total</span></td>
                    <td class="textright"><span>L. <?php echo number_format($subtotal,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Importe Exonerado</span></td>
                    <td class="textright"><span>L. <?php echo number_format(0,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Importe Excento</span></td>
                    <td class="textright"><span>L. <?php echo number_format($importe_excento,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Importe Gravado 15%</span></td>
                    <td class="textright"><span>L. <?php echo number_format($importe_gravado,2); ?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Importe Gravado 18%</span></td>
                    <td class="textright"><span>L. <?php echo number_format(0,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>ISV 15%</span></td>
                    <td class="textright"><span>L. <?php echo number_format($isv_neto,2); ?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>ISV 18%</span></td>
                    <td class="textright"><span>L. <?php echo number_format(0,2);?></span></td>
                </tr>
                <tr>
                    <td colspan="6" class="textright"><span>Total</span></td>
                    <td class="textright"><span>L. <?php echo number_format($total_despues_isv,2); ?></span></td>
                </tr>
            </tfoot>
        </table>
        <div>
            <p class="nota"><?php 
			if($consulta_registro["notas"] != ""){
				echo "<p class='h2'><b>Nota:</b> ".nl2br($consulta_registro["notas"])."</p>";
			}		
		?></p>
            <p class="nota"><br /><br /></p>
            <p class="nota">
                <center><?php echo $insMainModel->convertir($total_despues_isv).' LEMPIRAS';?></center>
            </p>

            <table style="width:100%; padding-top: 6rem; padding-bottom: 4rem;">
                <tr style="text-align: center;">
                    <td>______________________________________</td>
                    <td>______________________________________</td>
                </tr>
                <tr style="text-align: center;">
                    <td>Entregado por:</td>
                    <td>Recibido por:</td>
                </tr>
            </table>

            <?php
			if($consulta_registro["estado"] == 2){
				if($firma_documento !== "") {
		?>
            <p class="nota">
                <center><img src="<?php echo SERVERURL; ?>vistas/plantilla/img/logos/<?php echo $firma_documento; ?>"
                        width="150px" height="130px">
            </p>
            <?php
				}
			}
		?>

            <p class="nota">
                <center><b>Original:</b> Cliente</center>
            </p>
            <p class="nota">
                <center><b>Copia:</b> Emisor</center>
            </p>
            <h4 class="label_gracias"><?php  echo nl2br($consulta_registro["eslogan"]); ?></h4>
        </div>

    </div>
</body>

</html>