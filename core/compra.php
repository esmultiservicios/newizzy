<?php
   require_once "mainModel.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Confirmación de Compra</title>
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
                    <img src="<?php 
                        echo SERVERURLLOGO; 
                        if (SISTEMA_PRUEBA === "SI") {
                            echo "esmultiservicios_logo.png"; 
                        } else {
                            echo isset($logotipo) ? $logotipo : 'esmultiservicios_logo.png'; // Asegura que si no existe $logotipo, se use un logo por defecto
                        }   
                    ?>" width="150px" height="95px">
                </div>
                </td>
                <td class="info_empresa">
                    <div>
                        <span class="h2"><?php echo $consulta_registro['empresa']; ?></span>
                        <p><?php echo nl2br($consulta_registro['direccion_empresa']); ?></p>
                        <p>PBX: <?php echo $consulta_registro['empresa_telefono']; ?></p>
                        <p>WhatsApp: <?php echo $consulta_registro['empresa_celular']; ?></p>
                        <p>Correo: <?php echo $consulta_registro['empresa_correo']; ?></p>
                        <p><?php echo $consulta_registro['otra_informacion']; ?></p>
                    </div>
                </td>
                <td class="info_factura">
                    <div class="round">
                        <span class="h3">Confirmación de Compra</span>
                        <p><b>N° Factura:</b> <?php echo $consulta_registro['numero_factura']; ?></p>
                        <p><b>Fecha:</b>
                            <?php echo $consulta_registro['fecha'].' '.date('g:i a',strtotime($consulta_registro['hora'])); ?>
                        </p>
                        <p><b>RTN:</b> <?php echo $consulta_registro['rtn_empresa']; ?></p>
                        <p><b>Factura:</b> <?php echo $consulta_registro['tipo_documento']; ?></p>
                    </div>
                </td>
            </tr>
        </table>
        <table id="factura_cliente">
            <tr>
                <td class="info_cliente">
                    <div class="round">
                        <span class="h3">Proveedor</span>
                        <table class="datos_cliente">
                            <tr>
                                <td><label>RTN:</label>
                                    <p><?php 
									if(strlen($consulta_registro['rtn_proveedor'])<10){
										echo $consulta_registro['rtn_proveedor'];
									}else{
										echo $consulta_registro['rtn_proveedor'];
									}
							
							?></p>
                                </td>
                                <td><label>Teléfono:</label>
                                    <p><?php echo $consulta_registro['telefono']; ?></p>
                                </td>
                                <td><label>Usuario:</label>
                                    <p><?php 
                                        $nombre_completo = trim(ucwords($consulta_registro['colaborador_nombre']));
                                        $partes = explode(" ", $nombre_completo);

                                        $primer_nombre = $partes[0];
                                        $primer_apellido = isset($partes[2]) ? $partes[2] : (isset($partes[1]) ? $partes[1] : "");

                                        $vendedor = $primer_nombre . " " . $primer_apellido;

                                        echo $vendedor;
							?></p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><label>Proveedor:</label>
                                    <p><?php echo $consulta_registro['proveedor']; ?></p>
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
                    <th width="3%">N°</th>
                    <th width="40%">Nombre Producto</th>
                    <th width="6%" class="textCenter">Cantidad</th>
                    <th width="6%" class="textCenter">Medida</th>
                    <th width="15%" class="textright">Precio</th>
                    <th width="15%" class="textright">Descuento</th>
                    <th width="15%" class="textright">Importe</th>
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
					
					while($registro_detalles = $result_cotizacion_detalle->fetch_assoc()){
						$descuentos = 0;																
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
						
						echo '
						  <tr>
							<td>'.$i.'</td>
							<td>'.$registro_detalles["producto"].'</td>
							<td align="center">'.$registro_detalles["cantidad"].'</td>
							<td align="center">'.$registro_detalles["medida"].'</td>
							<td class="textright">L. '.number_format($registro_detalles["precio"],2).'</td>
							<td class="textright">L. '.number_format($descuentos,2).'</td>
							<td class="textright">L. '.number_format($importe,2).'</td>
						  </tr>
						';
						$i++;
					}
					$total_despues_isv = ($total + $isv_neto) - $descuentos;
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
				echo "<p class='h2'>Nota:</b></p>";
				echo "<p class='h2'>".nl2br($consulta_registro["notas"])."</p>";
			}		
		?></p>
            <p class="nota textcenter"><?php echo $insMainModel->convertir($total_despues_isv).' LEMPIRAS';?></p>
            <p class="nota"></p>
            <p class="nota"><br /><br /><br /></p>
            <h4 class="label_gracias"><?php  echo nl2br($consulta_registro["eslogan"]); ?></h4>
            <p class="nota"><br /><br /></p>
            <p class="nota"><b>__________________________</p>
            <p class="nota"><b>Elaborado por:</p>
            <p class="nota"><br /><br /><br /><br /><br /><br /></p>
            <p class="nota"><b>__________________________</p>
            <p class="nota"><b>Autorizado por:</p>
        </div>
    </div>
</body>

</html>