<?php
include '../../app/config/session.php';
include '../../app/config/database.php';

require_once('../../vendor/autoload.php');
use Dompdf\Dompdf;
use Dompdf\Options;

// Obtener todas las compras (con detalles extra)
$sql = "
SELECT 
    c.id,
    c.proveedor,
    c.numero_factura,
    c.fecha_compra,
    c.total,
    c.observacion,
    COUNT(cd.id) AS cantidad_productos,
    SUM(cd.cantidad) AS cantidad_ropa
FROM compras c
LEFT JOIN compras_detalle cd ON cd.compra_id = c.id
GROUP BY c.id, c.proveedor, c.numero_factura, c.fecha_compra, c.total, c.observacion
ORDER BY c.fecha_compra DESC";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Construir el HTML del reporte
$html = '
<h2 style="text-align:center;">Reporte General de Compras</h2>
<table border="1" cellspacing="0" cellpadding="6" width="100%">
<thead>
<tr style="background:#e9ecef;">
    <th>ID</th>
    <th>Proveedor</th>
    <th>N° Factura</th>
    <th>Fecha de Compra</th>
    <th>Productos Distintos</th>
    <th>Cantidad Total de Ropa</th>
    <th>Total (Bs)</th>
    <th>Observación</th>
</tr>
</thead>
<tbody>';

$totalGeneral = 0;
$totalRopa = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $fecha = $row['fecha_compra'] ? $row['fecha_compra']->format('Y-m-d') : '';
    $html .= '<tr>
        <td>'.htmlspecialchars($row['id']).'</td>
        <td>'.htmlspecialchars($row['proveedor']).'</td>
        <td>'.htmlspecialchars($row['numero_factura']).'</td>
        <td>'.$fecha.'</td>
        <td align="center">'.(int)$row['cantidad_productos'].'</td>
        <td align="center">'.(int)$row['cantidad_ropa'].'</td>
        <td align="right">'.number_format($row['total'], 2).'</td>
        <td>'.htmlspecialchars($row['observacion']).'</td>
    </tr>';

    $totalGeneral += $row['total'];
    $totalRopa += (int)$row['cantidad_ropa'];
}

$html .= '
</tbody>
<tfoot>
<tr style="font-weight:bold; background:#f0f0f0;">
    <td colspan="5" align="right">Totales generales:</td>
    <td align="center">'.number_format($totalRopa, 0).' prendas</td>
    <td align="right">'.number_format($totalGeneral, 2).' Bs</td>
    <td></td>
</tr>
</tfoot>
</table>
<p style="text-align:right; font-size:12px; margin-top:15px;">Generado el '.date('d/m/Y H:i:s').'</p>
';

// Configurar opciones de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Horizontal para más columnas
$dompdf->render();

// Forzar descarga del archivo PDF
$dompdf->stream('reporte_compras.pdf', ['Attachment' => true]);
exit;
?>
