<?php
   require_once "mainModel.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Libro de Salarios</title>
	<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/style_factura.css">
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
					<img src="<?php echo SERVERURL; ?>vistas/plantilla/img/logo.png" width="150px" height="95px">
				</div>
			</td>			
			<td class="info_empresa" colspan="3">
				<div>
					<span class="h3">Libro de Salarios <?php echo " Año: ".$consulta_registro['ano_registro']; ?></span>								
				</div>												
			</td>		
		</tr>
		<tr>
			<td class="info_empresa">
				<div>
					<span class="h3">Compañia</span>
					<span class="h2"><?php echo $consulta_registro['empresa']; ?></span>									
				</div>												
			</td>
			<td class="info_empresa">
				<div>
					<span class="h3">RTN</span>
					<span class="h2"><?php echo $consulta_registro['rtn_empresa']; ?></span>									
				</div>				
			</td>	
			<td class="info_empresa">
				<div>	
					<span class="h3">Fecha Registro</span>
					<span class="h2"><?php echo $consulta_registro['fecha_registro']; ?></span>								
				</div>				
			</td>						
		</tr>
	</table>
	<table id="factura_detalle">
		<tr>
		</tr>		
	</table>

	<table id="factura_detalle">
				<thead>
					<tr>
						<th align="center" rowspan="2" width="5%">Puesto</th>
						<th align="center" rowspan="2" width="5%">Empleado</th>
						<th align="center" rowspan="2" width="5%">Fecha Ingreso</th>
						<th align="center" rowspan="2" width="5%">Salario Base</th>
						<th align="center" rowspan="2" width="5%">Días Trabajados</th>
						<th align="center" colspan="6" width="30%">Ingresos</th>
						<th align="center" rowspan="2" width="5%">Total Ingresos</th>
						<th align="center" colspan="6" width="30%">Egresos</th>
						<th align="center" rowspan="2" width="5%">Total Egresos</th>
						<th align="center" rowspan="2" width="5%">Neto</th>				
					</tr>
					<tr>
						<th align="center" width="5%">Hrs 25%</th>
						<th align="center" width="5%">Hrs 50%</th>
						<th align="center" width="5%">Hrs 75%</th>
						<th align="center" width="5%">Hrs 100%</th>
						<th align="center" width="5%">Bono</th>
						<th align="center" width="5%">Otros Ingresos</th>
						<th align="center" width="5%">Deducciones</th>
						<th align="center" width="5%">Prestamos</th>
						<th align="center" width="5%">IHSS</th>
						<th align="center" width="5%">RAP</th>
						<th align="center" width="5%">ISR</th>
						<th align="center" width="5%">Incapcidad IHSS</th>
					</tr>											
				</thead>
				<tbody id="detalle_productos">
					<?php
						while($registro_detalles = $result_voucher_detalle->fetch_assoc()){
							echo '
								<tr>
									<th align="center" width="5%">'.$registro_detalles["puesto"].'</th>
									<th align="center" width="5%">'.$registro_detalles["empleado"].'</th>
									<th align="center" width="5%">'.$registro_detalles["fecha_ingreso"].'</th>
									<th align="center" width="5%">'.$registro_detalles["salario"].'</th>
									<th align="center" width="5%">'.$registro_detalles["dias_trabajados"].'</th>
									<th align="center" width="5%">'.$registro_detalles["horas_25"].'</th>
									<th align="center" width="5%">'.$registro_detalles["horas_50"].'</th>	
									<th align="center" width="5%">'.$registro_detalles["horas_75"].'</th>
									<th align="center" width="5%">'.$registro_detalles["horas_100"].'</th>
									<th align="center" width="5%">'.$registro_detalles["bono"].'</th>
									<th align="center" width="5%">'.$registro_detalles["otros_ingresos"].'</th>
									<th align="center" width="5%">'.$registro_detalles["neto_ingresos"].'</th>
									<th align="center" width="5%">'.$registro_detalles["deducciones"].'</th
									<th align="center" width="5%">'.$registro_detalles["prestamo"].'</th>
									<th align="center" width="5%">'.$registro_detalles["ihss"].'</th>
									<th align="center" width="5%">'.$registro_detalles["isr"].'</th>
									<th align="center" width="5%">'.$registro_detalles["rap"].'</th>
									<th align="center" width="5%">'.$registro_detalles["incapacidad_ihss"].'</th>
									<th align="center" width="5%">'.$registro_detalles["neto_egresos"].'</th>
									<th align="center" width="5%">'.$registro_detalles["neto"].'</th>
								</tr>							
							';
						}
					?>	
				</tbody>
			</table>		
</div>
</body>
</html>