<?php
include '../../app/config/session.php';
include '../../app/config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Error: No se proporcionó un ID de proveedor válido.");
}

$errores = [];
$ok = null;

// Consultar proveedor actual
$sqlProveedor = "SELECT * FROM proveedores WHERE id = ?";
$stmtProveedor = sqlsrv_query($conn, $sqlProveedor, [$id]);
$proveedor = sqlsrv_fetch_array($stmtProveedor, SQLSRV_FETCH_ASSOC);

if (!$proveedor) {
    die("Error: Proveedor no encontrado.");
}

$nombre    = $proveedor['nombre'];
$telefono  = $proveedor['telefono'];
$correo    = $proveedor['correo'];
$direccion = $proveedor['direccion'];

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '') $errores[] = 'El nombre del proveedor es obligatorio.';
    if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo inválido.';

    if (!$errores) {
        $sql = "UPDATE proveedores 
                SET nombre = ?, telefono = ?, correo = ?, direccion = ?, actualizado_en = SYSDATETIME()
                WHERE id = ?";
        $params = [$nombre, $telefono, $correo, $direccion, $id];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $ok = 'Proveedor actualizado correctamente.';
            // Refrescar datos del proveedor
            $stmtProveedor = sqlsrv_query($conn, $sqlProveedor, [$id]);
            $proveedor = sqlsrv_fetch_array($stmtProveedor, SQLSRV_FETCH_ASSOC);
        } else {
            $errores[] = 'Error al actualizar: ' . print_r(sqlsrv_errors(), true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Proveedor</title>
    <style>
        body {
            font-family: Arial;
            background: #f7f7f7;
            margin: 40px;
        }

        form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            background: #0e86ffff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 15px;
        }

        button:hover {
            background: #1a252f;
        }

        .alert {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .ok {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .volver {
            display: inline-block;
            text-decoration: none;
            padding: 10px 20px;
            background: #6c757d;
            color: #fff;
            border-radius: 6px;
            margin-left: 10px;
        }

        .volver:hover {
            background: #5a6268;
        }
    </style>
</head>

<body>
    <h1>Editar Proveedor</h1>

    <?php if ($ok): ?><div class="ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($errores): ?><div class="alert">
            <ul><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
        </div><?php endif; ?>

    <form method="POST">
        <label>Nombre del Proveedor:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>">

        <label>Correo:</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($correo) ?>">

        <label>Dirección:</label>
        <textarea name="direccion" rows="3"><?= htmlspecialchars($direccion) ?></textarea>

        <button type="submit">Actualizar Proveedor</button>
        <a class="volver" href="../compras/registro_compras.php">Volver</a>
    </form>
</body>

</html>
