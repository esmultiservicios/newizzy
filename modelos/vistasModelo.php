<?php
	$peticionAjax = false;
	
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
    class vistasModelo extends mainModel{
        protected function getVistasModelo($vistas){
            $listaBlanca = ["transferencia","dashboard","clientes", "facturas", "cotizacion", "cajas", "proveedores",
			 "facturaCompras", "productos", "inventario", "cuentasContabilidad", "movimientosContabilidad", "ingresosContabilidad",
			 "gastosContabilidad", "chequesContabilidad", "confCtaContabilidad", 'confTipoPago', "confBancos", "confImpuestos",
			 "historialAccesos", "bitacora", "colaboradores", "programaPuntos", "puestos", "users", "secuencia", "empresa", "privilegio", "tipoUser",
			 "cobrarClientes", "pagarProveedores", "reporteCompras", "reporteVentas", "reporteCotizacion", "confAlmacen", "confUbicacion",
			 "confCategoria", "confMedida", "confEmail", "confHost", "confPlanes", "confHostProductos","confImpresora","contrato","nomina","asistencia"];

            if(in_array($vistas, $listaBlanca)){
				if(is_file("./vistas/contenido/".$vistas."-view.php")){
					//VERIFICAMOS QUE EL USUARIO TENGA PERMISOS AL MENU A CONSULTAR
					if($vistas == 'dashboard'){						
						$resultVarios = mainModel::getMenuAccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);

						if($resultVarios->num_rows==0){
							$vistas ="401";
						}
						//AGREGAR ESTOS VALORES DE LOS SUBMENUS PARA QUE SE PUEDAN VISUALIZAR, CON ESTO ASIGNAMOS LOS PERMISOS REQUERIDOS
					}else if($vistas == 'historialAccesos' || $vistas == 'bitacora' || $vistas == 'reporteVentas' || $vistas == 'reporteCotizacion' || $vistas == 'cobrarClientes' || $vistas == 'reporteCompras' || $vistas == 'pagarProveedores'){
						$resultVarios = mainModel::getSubMenu1AccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);

						if($resultVarios->num_rows==0){
							$vistas ="401";
						}
					}else{
						$resultVarios = mainModel::getSubMenuAccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);

						if($resultVarios->num_rows==0){
							$vistas ="401";
						}						
					}
					
					$contenido="./vistas/contenido/".$vistas."-view.php";
				}else{
					$contenido="login";
				}
			}elseif($vistas=="login"){
				$contenido="login";
			}elseif($vistas=="index"){
				$contenido="login";
			}else{
				$contenido="404";
			}
			return $contenido;
        }
    }
?>	