<?php
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Función para mostrar errores amigables
function mostrarError($mensaje)
{
  echo '<!DOCTYPE html>
    <html lang="es">
    <head>
    <meta charset="UTF-8">
    <title>Error</title>
    <style>
        body { font-family: Arial,sans-serif; background:#f7f7f7; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
        .error-box { background:white; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); text-align:center; max-width:400px; }
        h1 { color:#e74c3c; margin-bottom:15px; }
        p { margin:10px 0; }
        a { display:inline-block; margin-top:15px; padding:10px 20px; background:#27ae60; color:white; text-decoration:none; border-radius:5px; }
        a:hover { background:#219150; }
    </style>
    </head>
    <body>
    <div class="error-box">
    <h1>❌ Error</h1>
    <p>' . htmlspecialchars($mensaje) . '</p>
    <a href="index.php">Volver al inicio</a>
    </div>
    </body>
    </html>';
  exit;
}

// --- Validar parámetro factura ---
$factura = $_GET['factura'] ?? null;
if (!$factura) {
  mostrarError('No se especificó número de factura.');
}

// --- Buscar datos de la factura ---
$sql = "
SELECT f.id, f.numero, f.fecha_emision, f.total, f.razon_social, f.nit_cliente,
       v.id AS venta_id, c.nombre AS cliente
FROM facturas f
LEFT JOIN ventas v ON v.id = f.venta_id
LEFT JOIN clientes c ON c.id = v.cliente_id
WHERE f.numero = ?
";
$stmt = sqlsrv_query($conn, $sql, [$factura]);
if ($stmt === false) {
  mostrarError('Error al consultar la base de datos: ' . print_r(sqlsrv_errors(), true));
}

$facturaData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$facturaData) {
  mostrarError('Factura no encontrada.');
}

$venta_id = $facturaData['venta_id'];

// --- Buscar detalle de productos ---
$sql2 = "
SELECT p.titulo, d.cantidad, d.precio_unitario
FROM ventas_detalle d
JOIN productos p ON p.id = d.producto_id
WHERE d.venta_id = ?
";
$stmt2 = sqlsrv_query($conn, $sql2, [$venta_id]);
if ($stmt2 === false) {
  mostrarError('Error al consultar los detalles de la venta: ' . print_r(sqlsrv_errors(), true));
}

$detalles = [];
while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
  $detalles[] = $row;
}

// --- Crear carpeta facturas si no existe ---
$dir = __DIR__ . '/facturas';
if (!is_dir($dir)) {
  if (!mkdir($dir, 0775, true)) {
    mostrarError('No se pudo crear la carpeta de facturas. Verifique permisos.');
  }
}

// --- Formatear fecha correctamente ---
$fechaStr = '-';
if (!empty($facturaData['fecha_emision'])) {
  if ($facturaData['fecha_emision'] instanceof DateTime) {
    $fechaStr = $facturaData['fecha_emision']->format('Y-m-d H:i');
  } else {
    try {
      $fechaEmision = new DateTime($facturaData['fecha_emision']);
      $fechaStr = $fechaEmision->format('Y-m-d H:i');
    } catch (Exception $e) {
      $fechaStr = $facturaData['fecha_emision'];
    }
  }
}

// --- Generar contenido HTML del PDF ---
$html = '<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 13px; margin:20px; color:#333; }
h1 { text-align:center; color:#2c3e50; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { border:1px solid #888; padding:8px; text-align:left; }
th { background:#f2f2f2; }
.total { text-align:right; font-weight:bold; }
.footer { text-align:center; margin-top:30px; font-style:italic; color:#555; }
</style>
<h1>Factura N° ' . htmlspecialchars($facturaData['numero']) . '</h1>
<p><strong>Fecha de emisión:</strong> ' . $fechaStr . '</p>
<p><strong>Cliente:</strong> ' . htmlspecialchars($facturaData['razon_social'] ?? $facturaData['cliente'] ?? 'Consumidor Final') . '</p>
<p><strong>NIT/CI:</strong> ' . htmlspecialchars($facturaData['nit_cliente'] ?? '-') . '</p>
<table>
<thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario (Bs)</th><th>Subtotal (Bs)</th></tr></thead><tbody>';

$total = 0;
foreach ($detalles as $d) {
  $subtotal = $d['cantidad'] * $d['precio_unitario'];
  $html .= '<tr>
        <td>' . htmlspecialchars($d['titulo']) . '</td>
        <td>' . $d['cantidad'] . '</td>
        <td>' . number_format($d['precio_unitario'], 2) . '</td>
        <td>' . number_format($subtotal, 2) . '</td>
    </tr>';
  $total += $subtotal;
}

$html .= '<tr><td colspan="3" class="total">TOTAL</td><td class="total">' . number_format($total, 2) . ' Bs</td></tr></tbody></table>
<p class="footer">¡Gracias por su compra!</p>';

// --- Generar PDF con Dompdf ---
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// --- Guardar PDF ---
$pdfFile = $dir . '/factura_' . $factura . '.pdf';
if (file_put_contents($pdfFile, $dompdf->output()) === false) {
  mostrarError('Error al guardar el PDF. Verifique permisos en la carpeta facturas.');
}

// --- Página de éxito ---
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Compra exitosa</title>
  <link rel="stylesheet" href="public/css/estilos.css">

</head>

<body class="esx">
  <div class="exito-compra">
    <h1>¡Compra completada!</h1>
    <p>Tu factura ha sido generada correctamente.</p>
    <p><strong>Número de factura:</strong> <?php echo htmlspecialchars($factura); ?></p>
    <a href="facturas/factura_<?php echo htmlspecialchars($factura); ?>.pdf" target="_blank" class="btn">Ver / Imprimir PDF</a>
    <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
  </div>
</body>

</html>