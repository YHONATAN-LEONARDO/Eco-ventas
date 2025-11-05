<?php
include '../../app/config/session.php';
include '../../app/config/database.php';

// --- Eliminar proveedor si se envió una acción GET ---
if (isset($_GET['eliminar'])) {
    $idEliminar = (int)$_GET['eliminar'];
    if ($idEliminar > 0) {
        $sqlDelete = "DELETE FROM proveedores WHERE id = ?";
        sqlsrv_query($conn, $sqlDelete, [$idEliminar]);
        header('Location: ./l-proveedor.php');
        exit;
    }
}

// --- Consultar todos los proveedores ---
$sql = "SELECT id, nombre, telefono, correo, direccion, creado_en, actualizado_en 
        FROM proveedores 
        ORDER BY nombre ASC";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Proveedores</title>
    <style>
        body {
            font-family: Arial;
            background: #f7f7f7;
            margin: 40px;
        }

        h1 {
            text-align: center;
        }

        .acciones {
            text-align: center;
            margin-bottom: 20px;
        }

        .acciones a {
            display: inline-block;
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            margin: 0 5px;
        }

        .acciones a:hover {
            background: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px 8px;
            text-align: left;
        }

        th {
            background: #e9ecef;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
        }

        .editar {
            background: #28a745;
        }

        .editar:hover {
            background: #218838;
        }

        .eliminar {
            background: #dc3545;
        }

        .eliminar:hover {
            background: #c82333;
        }
    </style>
</head>
<body>

    <h1>Lista de Proveedores</h1>

    <div class="acciones">
        <a href="./lista.php">Volver</a>
        <a href="./crear_proveedor.php">Crear Proveedor</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Creado</th>
                <th>Actualizado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= htmlspecialchars($row['nombre']); ?></td>
                    <td><?= htmlspecialchars($row['telefono']); ?></td>
                    <td><?= htmlspecialchars($row['correo']); ?></td>
                    <td><?= htmlspecialchars($row['direccion']); ?></td>
                    <td><?= $row['creado_en'] ? $row['creado_en']->format('Y-m-d H:i') : ''; ?></td>
                    <td><?= $row['actualizado_en'] ? $row['actualizado_en']->format('Y-m-d H:i') : ''; ?></td>
                    <td>
                        <a class="btn editar" href="editar.php?id=<?= $row['id']; ?>">Actualizar</a>
                        <a class="btn eliminar" href="?eliminar=<?= $row['id']; ?>">Eliminar</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

</body>
</html>
