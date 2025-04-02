<?php
   require_once "mainModel.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Ingresos</title>
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
					<span class="h3">Registro de Ingresos</span>
					<p><b>N° Ingreso:</b> <?php echo $consulta_registro['ingresos_id']; ?></p>
					<p><b>Fecha Factura:</b> <?php echo $consulta_registro['fecha']; ?></p>
					<p><b>RTN:</b> <?php echo $consulta_registro['rtn_empresa']; ?></p>
					<p><b>Fecha Registro:</b> <?php echo $consulta_registro['fecha_registro_consulta'].' '.date('g:i a',strtotime($consulta_registro['fecha_registro'])); ?></p>
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
							<td><label>RTN:</label><p><?php 
									if(strlen($consulta_registro['rtn_cliente'])<10){
										echo $consulta_registro['rtn_cliente'];
									}else{
										echo $consulta_registro['rtn_cliente'];
									}
							
							?></p></td>							
							<td><label>Teléfono:</label> <p><?php echo $consulta_registro['telefono']; ?></p></td>
							<td><label>Usuario:</label> <p><?php 
									$nombre_ = explode(" ", trim(ucwords($consulta_registro['colaborador_nombre']), " "));
									$nombre_usuario = $nombre_[0];
									$apellido_ = explode(" ", trim(ucwords($consulta_registro['colaborador_apellido']), " "));	
									$nombre_apellido = $apellido_[0];	
									
									$vendedor = $nombre_usuario." ".$nombre_apellido;
	
									echo $vendedor;  
							?></p></td>
						</tr>
						<tr>
							<td colspan="2"><label>Cliente:</label><p><?php echo $consulta_registro['cliente']; ?></p></td>
						</tr>
					</table>
				</div>
			</td>

		</tr>
	</table>
	<table id="factura_detalle">
			<thead>
				<tr>
					<th width="2.28%">N°</th>
					<th width="26.28%" class="textleft">Factura</th>
					<th width="14.28%" class="textright">Subtotal</th>
					<th width="14.28%" class="textright">ISV</th>
					<th width="14.28%" class="textright">Descuento</th>
					<th width="14.28%" class="textright">NC</th>
					<th width="14.28%" class="textright">Total</th>					
				</tr>
			</thead>
			<tbody id="detalle_productos">
				<?php
						echo '
						<tr>
						  <td>1</td>
						  <td>'.$consulta_registro["factura"].'</td>
						  <td align="center">L. '.number_format($consulta_registro["subtotal"],2).'</td>
						  <td class="textright">L. '.number_format($consulta_registro["impuesto"],2).'</td>
						  <td class="textright">L. '.number_format($consulta_registro["descuento"],2).'</td>
						  <td class="textright">L. '.number_format($consulta_registro["nc"],2).'</td>
						  <td class="textright">L. '.number_format($consulta_registro["total"],2).'</td>						  						  
						</tr>
					  ';
				?>
			</tbody>
	</table>
	<div>
		<p class="nota"><?php 
			if($consulta_registro["observacion"] != ""){
				echo "<p class='h2'>Nota:</b></p>";
				echo "<p class='h2'>".nl2br($consulta_registro["observacion"])."</p>";
			}	
		?></p>
		<p class="nota"><center><?php echo $insMainModel->convertir($consulta_registro["total"]).' LEMPIRAS';?></center></p>
		<p class="nota"></p>
		<p class="nota"><br/><br/><br/><br/></p>		
		<h4 class="label_gracias"><?php  echo nl2br($consulta_registro["eslogan"]); ?></h4>
		<p class="nota"><br/><br/><br/><br/></p>
		<p class="nota"><br/><br/><br/><br/></p>											
	</div>

</div>
</body>
</html>
