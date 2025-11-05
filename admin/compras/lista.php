<?php
include '../../app/config/session.php';
include '../../app/config/database.php';

// Consulta de todas las compras con cantidad total de ropa
$sql = "
SELECT 
    c.id,
    c.proveedor,
    c.numero_factura,
    c.fecha_compra,
    c.total,
    c.observacion,
    c.creado_en,
    COUNT(cd.id) AS cantidad_productos,
    SUM(cd.cantidad) AS cantidad_ropa
FROM compras c
LEFT JOIN compras_detalle cd ON cd.compra_id = c.id
GROUP BY c.id, c.proveedor, c.numero_factura, c.fecha_compra, c.total, c.observacion, c.creado_en
ORDER BY c.fecha_compra DESC;
";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Compras</title>
    <link rel="stylesheet" href="/admin/panel.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f8f8; }
        h1 { text-align: center; }
        .acciones { margin-bottom: 20px; text-align: center; }
        .acciones a {
            text-decoration: none;
            display: inline-block;
            background: #0080ff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 0 5px;
        }
        .acciones a:hover { background: #005fba; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: left;
        }
        th { background: #e9ecef; }
        tr:hover { background: #f1f1f1; }
        .total { font-weight: bold; color: #006400; }
    </style>
</head>
<body>
    <h1>Registro de Compras</h1>

    <div class="acciones">
        <a href="../index.php">Volver</a>
        <!-- <a href="./crear_proveedor.php">Crear Proveedor</a>     -->
        <a href="./l-proveedor.php">Lista de Proveedor</a>
        <a href="./ingresar_factura.php">Ingresar Factura de Compra</a>
        <a href="reporte_compras.php">Generar Reporte de Compras (PDF)</a>
        
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Proveedor</th>
                <th>N° Factura</th>
                <th>Fecha Compra</th>
                <th>Productos Distintos</th>
                <th>Cantidad Total de Ropa</th>
                <th>Total (Bs)</th>
                <th>Observación</th>
                <th>Registrado En</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']); ?></td>
                    <td><?= htmlspecialchars($row['proveedor']); ?></td>
                    <td><?= htmlspecialchars($row['numero_factura']); ?></td>
                    <td><?= $row['fecha_compra'] ? $row['fecha_compra']->format('Y-m-d') : ''; ?></td>
                    <td><?= (int)$row['cantidad_productos']; ?></td>
                    <td><?= (int)$row['cantidad_ropa']; ?> unidades</td>
                    <td class="total"><?= number_format($row['total'], 2); ?></td>
                    <td><?= htmlspecialchars($row['observacion']); ?></td>
                    <td><?= $row['creado_en'] ? $row['creado_en']->format('Y-m-d H:i') : ''; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>
