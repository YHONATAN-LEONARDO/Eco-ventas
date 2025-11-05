<?php
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

// --- 1️⃣ Verificar sesión ---
if (empty($_SESSION['usuario_id'])) {
    header('Location: /views/usuarios/login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol = strtolower($_SESSION['usuario_rol'] ?? 'cliente');
$mensaje = "";

// --- 2️⃣ Obtener datos del usuario ---
$sql = "SELECT id, nombre, correo, password_hash, rol, creado_en
        FROM usuarios WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [$usuario_id]);
$usuario = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$usuario) {
    die("❌ No se encontró el perfil del usuario con ID: $usuario_id");
}

// --- 3️⃣ Procesar formularios ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // 🔹 Actualizar nombre y correo
    if ($accion === 'actualizar') {
        $nombre = trim($_POST['nombre']);
        $correo = trim($_POST['correo']);

        if ($nombre === '' || $correo === '') {
            $mensaje = "❌ Nombre y correo son obligatorios.";
        } else {
            $sqlUp = "UPDATE usuarios SET nombre=?, correo=?, creado_en=creado_en WHERE id=?";
            $stmtUp = sqlsrv_query($conn, $sqlUp, [$nombre, $correo, $usuario_id]);
            if ($stmtUp) {
                $_SESSION['usuario_nombre'] = $nombre;
                $usuario['nombre'] = $nombre;
                $usuario['correo'] = $correo;
                $mensaje = "✅ Datos actualizados correctamente.";
            } else {
                $mensaje = "❌ Error al actualizar los datos.";
            }
        }
    }

    // 🔹 Cambiar contraseña
    if ($accion === 'password') {
        $actual = $_POST['actual'];
        $nueva = $_POST['nueva'];
        $confirmar = $_POST['confirmar'];

        if ($nueva !== $confirmar) {
            $mensaje = "❌ Las contraseñas nuevas no coinciden.";
        } elseif (!password_verify($actual, $usuario['password_hash'])) {
            $mensaje = "❌ La contraseña actual no es correcta.";
        } else {
            $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
            $sqlPass = "UPDATE usuarios SET password_hash=? WHERE id=?";
            $stmtPass = sqlsrv_query($conn, $sqlPass, [$nuevoHash, $usuario_id]);

            $mensaje = $stmtPass
                ? "✅ Contraseña actualizada correctamente."
                : "❌ Error al cambiar la contraseña.";
        }
    }
}

sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <style>
        /* ==========================
   Perfil - Estilo general
   ========================== */
        body {
            font-family: Arial, sans-serif;
            background: url('public/img/icons/fondo2.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
        }

        .perfil {
            background: rgba(255, 255, 255, 0.92);
            max-width: 700px;
            margin: 80px auto;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        h1 {
            text-align: center;
            color: #b82b2b;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #aaa;
            border-radius: 6px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 12px 20px;
            border: none;
            background: #000;
            /* boton negro */
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #333;
        }

        .mensaje {
            background: #27ae60;
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .acciones {
            text-align: center;
            margin-top: 30px;
        }

        .acciones a {
            text-decoration: none;
            background: #000;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            margin: 5px 10px;
            display: inline-block;
            transition: background 0.3s ease;

        }

        .acciones a:hover {
            background: #333;
        }

        /* ==========================
   Adaptación para móviles
   ========================== */
        @media (max-width: 768px) {
            .perfil {
                margin: 40px 20px;
                /* menos margen superior y lateral */
                padding: 25px;
            }

            h1 {
                margin-bottom: 15px;
                text-align: center;
            }

            /* Botones del formulario */
            button {
                width: 100%;
                text-align: center;
                padding: 12px;
                margin: 10px 0;
                display: block;
            }

            /* Enlaces en la sección .acciones */
            .acciones a {
                width: 80%;
                text-align: center;
                padding: 12px;
                margin: 10px 0;
                display: block;
                margin: 2rem auto;
            }
        }
    </style>
</head>

<body>
    <div class="perfil">
        <h1>Mi Perfil</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <!-- Datos personales -->
        <form method="POST">
            <input type="hidden" name="accion" value="actualizar">

            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>

            <label>Correo:</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($usuario['correo']) ?>" required>

            <button type="submit">Guardar Cambios</button>
        </form>

        <!-- Cambiar contraseña -->
        <form method="POST">
            <input type="hidden" name="accion" value="password">

            <label>Contraseña actual:</label>
            <input type="password" name="actual" required>

            <label>Nueva contraseña:</label>
            <input type="password" name="nueva" required>

            <label>Confirmar nueva contraseña:</label>
            <input type="password" name="confirmar" required>

            <button type="submit">Cambiar Contraseña</button>
        </form>

        <div class="acciones">
            <a href="/">Volver al inicio</a>
            <?php if (in_array($rol, ['admin', 'vendedor', 'almacen'])): ?>
                <a href="/admin/index.php">Panel Admin</a>
            <?php endif; ?>
            <a href="/views/usuarios/cerrar-sesion.php">Cerrar Sesión</a>
        </div>
    </div>
</body>

</html>