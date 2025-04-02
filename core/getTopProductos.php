<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

$ano = date('Y');

$sql = "
SELECT 
    sub.mes,
    sub.producto,
    sub.total_vendido
FROM (
    SELECT 
        DATE_FORMAT(m.fecha_registro, '%Y-%m') AS mes,
        CASE
            WHEN p.nombre LIKE '%dama%' THEN 'Dama'
            WHEN p.nombre LIKE '%caballero%' OR p.nombre LIKE '%hombre%' THEN 'Caballero'
            WHEN p.nombre LIKE '%NIÑO%' THEN 'NIÑO'
            WHEN p.nombre LIKE '%ROPA%' THEN 'ROPA'
            ELSE p.nombre
        END AS producto,
        SUM(m.cantidad_salida) AS total_vendido,
        RANK() OVER (
            PARTITION BY DATE_FORMAT(m.fecha_registro, '%Y-%m') 
            ORDER BY SUM(m.cantidad_salida) DESC
        ) AS ranking
    FROM 
        movimientos m
    JOIN 
        productos p ON m.productos_id = p.productos_id
    WHERE 
        m.cantidad_salida > 0
        AND YEAR(m.fecha_registro) = '$ano'
    GROUP BY 
        mes, producto
) AS sub
WHERE 
    sub.ranking <= 6
ORDER BY 
    sub.mes DESC;
";

$result = $insMainModel->ejecutarConsultaSimple($sql);

$arreglo = array();

while ($row = $result->fetch_assoc()) {
    // Verificar que el total vendido sea mayor a cero
    $total_vendido = (int)$row['total_vendido'];
    if ($total_vendido > 0) {
        $mes = (int) date('n', strtotime($row['mes']));
        $año = (int) date('Y', strtotime($row['mes']));

        $arreglo[] = array(
            'mes' => $insMainModel->nombremes($mes, $año),
            'producto' => $row['producto'],
            'total_vendido' => $total_vendido, // Asegurarse de que sea un número
        );
    }
}

echo json_encode($arreglo);