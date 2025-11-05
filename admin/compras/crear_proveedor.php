<?php
include '../../app/config/session.php';
include '../../app/config/database.php';

$errores = [];
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '') $errores[] = 'El nombre del proveedor es obligatorio.';
    if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo inválido.';

    if (!$errores) {
        $sql = "INSERT INTO proveedores (nombre, telefono, correo, direccion, creado_en)
                VALUES (?, ?, ?, ?, SYSDATETIME())";
        $stmt = sqlsrv_query($conn, $sql, [$nombre, $telefono, $correo, $direccion]);
        if ($stmt) {
            $ok = 'Proveedor registrado correctamente.';
        } else {
            $errores[] = 'Error al guardar: ' . print_r(sqlsrv_errors(), true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Crear Proveedor</title>
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
    </style>
</head>

<body>
    <h1>Crear Proveedor</h1>

    <?php if ($ok): ?><div class="ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($errores): ?><div class="alert">
            <ul><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
        </div><?php endif; ?>

    <form method="POST">
        <label>Nombre del Proveedor:</label>
        <input type="text" name="nombre" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono">

        <label>Correo:</label>
        <input type="email" name="correo">

        <label>Dirección:</label>
        <textarea name="direccion" rows="3"></textarea>

        <button type="submit">Guardar Proveedor</button>
        <a class="volver" href="lista.php" style="margin-left:10px;">Volver</a>
    </form>
</body>

</html>