<?php
$peticionAjax = true;
require_once "DynamicNavbar.php";

// Crear una instancia de la clase
$navbar = new DynamicNavbar();

// Llamar al método generarNavbar() para obtener el HTML del menú
$menu_html = $navbar->generarNavbar();

// Imprimir el HTML generado
echo $menu_html;