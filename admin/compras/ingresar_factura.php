<?php
include '../../app/config/session.php';
include '../../app/config/database.php';

$errores = [];
$ok = null;

// Cargar proveedores
$proveedores = [];
$pq = sqlsrv_query($conn, "SELECT id, nombre FROM proveedores ORDER BY nombre");
while ($r = sqlsrv_fetch_array($pq, SQLSRV_FETCH_ASSOC)) $proveedores[] = $r;

// Cargar productos
$productos = [];
$pq2 = sqlsrv_query($conn, "SELECT id, titulo FROM productos ORDER BY titulo");
while ($r2 = sqlsrv_fetch_array($pq2, SQLSRV_FETCH_ASSOC)) $productos[] = $r2;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor = trim($_POST['proveedor'] ?? '');
    $numero_factura = trim($_POST['numero_factura'] ?? '');
    $fecha_compra = trim($_POST['fecha_compra'] ?? date('Y-m-d'));
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $precio = (float)($_POST['precio'] ?? 0);
    $obs = trim($_POST['observacion'] ?? '');

    if ($proveedor === '') $errores[] = 'Proveedor es obligatorio.';
    if ($numero_factura === '') $errores[] = 'Número de factura es obligatorio.';
    if ($producto_id <= 0) $errores[] = 'Debe seleccionar un producto.';
    if ($cantidad <= 0) $errores[] = 'Cantidad inválida.';
    if ($precio <= 0) $errores[] = 'Precio inválido.';

    if (!$errores) {
        $total = $cantidad * $precio;
        sqlsrv_begin_transaction($conn);

        $compra_sql = "INSERT INTO compras (proveedor, numero_factura, fecha_compra, total, observacion)
                       OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?)";
        $stmt = sqlsrv_query($conn, $compra_sql, [$proveedor, $numero_factura, $fecha_compra, $total, $obs]);
        if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            $compra_id = $row['id'];

            $detalle_sql = "INSERT INTO compras_detalle (compra_id, producto_id, cantidad, precio_compra)
                            VALUES (?, ?, ?, ?)";
            sqlsrv_query($conn, $detalle_sql, [$compra_id, $producto_id, $cantidad, $precio]);

            // Actualiza inventario
            $update = "UPDATE productos SET cantidad = cantidad + ? WHERE id = ?";
            sqlsrv_query($conn, $update, [$cantidad, $producto_id]);

            sqlsrv_commit($conn);
            $ok = 'Factura registrada correctamente.';
        } else {
            sqlsrv_rollback($conn);
            $errores[] = 'Error al guardar la factura: ' . print_r(sqlsrv_errors(), true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingresar Factura de Proveedor</title>
    <style>
        body { font-family: Arial; background: #f7f7f7; margin: 40px; }
        form { background: #fff; padding: 20px; border-radius: 8px; max-width: 600px; margin: auto; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #178bff; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin-top: 15px; }
        .alert { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 10px; }
        .ok { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Ingresar Factura de Proveedor</h1>

    <?php if ($ok): ?><div class="ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($errores): ?><div class="alert"><ul><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul></div><?php endif; ?>

    <form method="POST">
        <label>Proveedor:</label>
        <select name="proveedor" required>
            <option value="">Seleccione...</option>
            <?php foreach ($proveedores as $prov): ?>
                <option value="<?= htmlspecialchars($prov['nombre']) ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Número de Factura:</label>
        <input type="text" name="numero_factura" required>

        <label>Fecha de Compra:</label>
        <input type="date" name="fecha_compra" value="<?= date('Y-m-d') ?>">

        <label>Producto:</label>
        <select name="producto_id" required>
            <option value="">Seleccione...</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['titulo']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Cantidad:</label>
        <input type="number" name="cantidad" required>

        <label>Precio Unitario (Bs):</label>
        <input type="number" step="0.01" name="precio" required>

        <label>Observación:</label>
        <textarea name="observacion" rows="3"></textarea>

        <button type="submit">Guardar Factura</button>
        <a href="lista.php" style="margin-left:10px;">Volver</a>
    </form>
</body>
</html>
