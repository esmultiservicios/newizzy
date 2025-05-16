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
    <?php if (SISTEMA_PRUEBA=="SI"): ?>
        <span class="container-fluid prueba-sistema">SISTEMA DE PRUEBA</span>
    <?php endif; ?>
    
    <div id="page_pdf">
        <table id="factura_head">
            <tr>
                <td class="logo_factura">
                    <div>
                        <img src="<?php 
                            echo SERVERURLLOGO; 
                            echo (SISTEMA_PRUEBA === "SI") ? "esmultiservicios_logo.png" : (isset($logotipo) ? $logotipo : 'esmultiservicios_logo.png');
                        ?>" width="150px" height="95px">
                    </div>
                </td>
                <td class="info_empresa">
                    <div>
                        <span class="h2"><?php echo $compra['empresa']; ?></span>
                        <p><?php echo nl2br($compra['direccion_empresa']); ?></p>
                        <p>PBX: <?php echo $compra['empresa_telefono']; ?></p>
                        <p>WhatsApp: <?php echo $compra['empresa_celular']; ?></p>
                        <p>Correo: <?php echo $compra['empresa_correo']; ?></p>
                        <p><?php echo $compra['otra_informacion']; ?></p>
                    </div>
                </td>
                <td class="info_factura">
                    <div class="round">
                        <span class="h3">Confirmación de Compra</span>
                        <p><b>N° Factura:</b> <?php echo $compra['numero_factura']; ?></p>
                        <p><b>Fecha:</b> <?php echo $compra['fecha'].' '.date('g:i a', strtotime($compra['hora'])); ?></p>
                        <p><b>RTN:</b> <?php echo $compra['rtn_empresa']; ?></p>
                        <p><b>Factura:</b> <?php echo $compra['tipo_documento']; ?></p>
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
                                    <p><?php echo strlen($compra['rtn_proveedor']) < 10 ? $compra['rtn_proveedor'] : $compra['rtn_proveedor']; ?></p>
                                </td>
                                <td><label>Teléfono:</label>
                                    <p><?php echo $compra['telefono']; ?></p>
                                </td>
                                <td><label>Usuario:</label>
                                    <p>
                                        <?php 
                                            $nombre_completo = trim(ucwords($compra['colaborador_nombre']));
                                            $partes = explode(" ", $nombre_completo);
                                            $primer_nombre = $partes[0];
                                            $primer_apellido = isset($partes[2]) ? $partes[2] : (isset($partes[1]) ? $partes[1] : "");
                                            echo $primer_nombre . " " . $primer_apellido;
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><label>Proveedor:</label>
                                    <p><?php echo $compra['proveedor']; ?></p>
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
                    
                    foreach($compra['detalles'] as $detalle){
                        $descuentos = 0;                                
                        $total_ = 0;
                        $importe = 0;

                        $total += ($detalle["precio"] * $detalle["cantidad"]);
                        $total_ = ($detalle["precio"] * $detalle["cantidad"]);
                        $importe += ($detalle["precio"] * $detalle["cantidad"] - $detalle["descuento"]);
                        $subtotal += $importe;
                        $descuentos += $detalle["descuento"];
                        $descuentos_neto += $descuentos;
                        $isv_neto += $detalle["isv_valor"];
                        
                        if($detalle["isv_valor"] > 0){
                            $importe_gravado += ($detalle["precio"] * $detalle["cantidad"] - $detalle["descuento"]);
                        }else{
                            $importe_excento += ($detalle["precio"] * $detalle["cantidad"] - $detalle["descuento"]);
                        }                        
                        
                        echo '
                          <tr>
                            <td>'.$i.'</td>
                            <td>'.$detalle["producto"].'</td>
                            <td align="center">'.$detalle["cantidad"].'</td>
                            <td align="center">'.$detalle["medida"].'</td>
                            <td class="textright">L. '.number_format($detalle["precio"],2).'</td>
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
            <?php if(!empty($compra["notas"])): ?>
                <p class='h2'>Nota:</b></p>
                <p class='h2'><?php echo nl2br($compra["notas"]); ?></p>
            <?php endif; ?>
            
            <p class="nota textcenter"><?php echo $insMainModel->convertir($total_despues_isv).' LEMPIRAS';?></p>
            <p class="nota"></p>
            <p class="nota"><br /><br /><br /></p>
            <h4 class="label_gracias"><?php echo nl2br($compra["eslogan"]); ?></h4>
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